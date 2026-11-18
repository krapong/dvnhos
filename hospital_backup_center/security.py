"""Audit records and TOTP MFA for the single administrator account."""
import csv
import hashlib
import io
import json
import secrets
import time
from datetime import datetime, timezone

import pyotp
import qrcode
import qrcode.image.svg
from flask import request, session, render_template, redirect, url_for, flash, Response, abort, has_request_context
from markupsafe import Markup
from werkzeug.security import check_password_hash, generate_password_hash


def init_security_db(connect):
    with connect() as db:
        db.execute('''CREATE TABLE IF NOT EXISTS audit_log(
            id INTEGER PRIMARY KEY, occurred_at TEXT NOT NULL, actor TEXT NOT NULL,
            ip TEXT NOT NULL, event TEXT NOT NULL, endpoint TEXT NOT NULL,
            method TEXT NOT NULL, status INTEGER, detail TEXT NOT NULL)''')
        db.execute('CREATE INDEX IF NOT EXISTS audit_time ON audit_log(occurred_at)')
        db.execute('''CREATE TABLE IF NOT EXISTS admin_security(
            id INTEGER PRIMARY KEY CHECK(id=1), enabled INTEGER NOT NULL DEFAULT 0,
            secret_encrypted TEXT NOT NULL DEFAULT '', last_counter INTEGER NOT NULL DEFAULT -1,
            pending_encrypted TEXT NOT NULL DEFAULT '', pending_owner TEXT NOT NULL DEFAULT '',
            pending_until REAL NOT NULL DEFAULT 0, session_version INTEGER NOT NULL DEFAULT 0)''')
        db.execute('INSERT OR IGNORE INTO admin_security(id) VALUES (1)')
        db.execute('CREATE TABLE IF NOT EXISTS recovery_codes(hash TEXT PRIMARY KEY, used_at TEXT)')
        db.execute('CREATE TABLE IF NOT EXISTS auth_failures(ip TEXT NOT NULL, kind TEXT NOT NULL, attempted REAL NOT NULL)')
        db.execute('CREATE INDEX IF NOT EXISTS auth_failures_lookup ON auth_failures(ip,kind,attempted)')


def register_security(app, ctx):
    def connect(): return ctx['connect_db']()
    def state():
        with connect() as db:return dict(db.execute('SELECT * FROM admin_security WHERE id=1').fetchone())
    def audit(event, detail=None, status=None):
        active=has_request_context()
        actor=('admin' if session.get('authed') else 'anonymous') if active else 'system'
        ip=(request.remote_addr or 'unknown') if active else ''
        with connect() as db:
            db.execute('INSERT INTO audit_log(occurred_at,actor,ip,event,endpoint,method,status,detail) VALUES (?,?,?,?,?,?,?,?)',
                       (datetime.now(timezone.utc).isoformat(timespec='seconds'),actor,ip,event,request.endpoint or '' if active else '',request.method if active else '',status,json.dumps(detail or {},ensure_ascii=False)))
    def limited(kind):
        with connect() as db:
            count=db.execute('SELECT count(*) FROM auth_failures WHERE ip=? AND kind=? AND attempted>?',(request.remote_addr or 'unknown',kind,time.time()-300)).fetchone()[0]
        return count>=8
    def failure(kind):
        with connect() as db:db.execute('INSERT INTO auth_failures(ip,kind,attempted) VALUES (?,?,?)',(request.remote_addr or 'unknown',kind,time.time()))
    def verify_factor(code):
        code=code.strip()
        current=state()
        if not current['enabled']:return False
        secret=ctx['password_cipher']().decrypt(current['secret_encrypted'].encode()).decode()
        try:
            counter=pyotp.TOTP(secret).verify_and_get_timecode(code,valid_window=1)
        except pyotp.OTPVerificationError:counter=None
        if counter is not None:
            with connect() as db:
                changed=db.execute('UPDATE admin_security SET last_counter=? WHERE id=1 AND enabled=1 AND secret_encrypted=? AND last_counter<?',(counter,current['secret_encrypted'],counter)).rowcount
            return changed==1
        # Recovery codes are high-entropy, hashed, and single-use.
        digest=hashlib.sha256(code.encode()).hexdigest()
        with connect() as db:
            changed=db.execute('UPDATE recovery_codes SET used_at=? WHERE hash=? AND used_at IS NULL',(datetime.now(timezone.utc).isoformat(),digest)).rowcount
        if changed:audit('mfa.recovery_used')
        return changed==1
    def finish_login():
        session.clear();session.update(authed=True,csrf=secrets.token_hex(24),auth_version=state()['session_version'])
        audit('login.success',status=200)
    def change_admin_password(new):
        new_hash=generate_password_hash(new)
        path=ctx['ROOT']/'.env'
        lines=path.read_text(encoding='utf-8').splitlines()
        lines=[line for line in lines if not line.startswith('ADMIN_PASSWORD_HASH=')]
        lines.append('ADMIN_PASSWORD_HASH='+new_hash)
        temp=path.with_suffix('.env.tmp');temp.write_text('\n'.join(lines)+'\n',encoding='utf-8');temp.replace(path)
        ctx['PASSWORD_HASH']=new_hash
        with connect() as db:db.execute('UPDATE admin_security SET session_version=session_version+1 WHERE id=1')
        if has_request_context() and session.get('authed'):session['auth_version']=state()['session_version']
        audit('password.changed')
    ctx['change_admin_password']=change_admin_password
    ctx.update(audit=audit, security_state=state, auth_limited=limited, auth_failure=failure, finish_login=finish_login, verify_factor=verify_factor)

    @app.before_request
    def validate_session():
        if request.endpoint=='static':return
        current=state()
        if session.get('authed') and (session.get('auth_version',0)!=current['session_version'] or (current['enabled'] and not session.get('mfa_verified'))):
            session.clear()
            if request.path.startswith('/api/'):abort(401)
            return redirect(url_for('login'))

    @app.after_request
    def request_audit(response):
        if request.endpoint not in ('static','status','server_health',None):
            # Do not capture request bodies, query strings, passwords, OTPs, or secrets.
            detail={}
            for key in ['host','job_id','name','target','day','hour','minute','enabled']:
                value=(request.view_args or {}).get(key)
                if value is None and request.method=='POST':value=request.form.get(key)
                if value is not None:
                    value=str(value)[:100]
                    if all(ch.isalnum() or ch in '._:-' for ch in value):detail[key]=value
            audit('web.'+request.endpoint,detail,response.status_code)
        if request.endpoint in ['login','login_mfa','mfa_setup','mfa_confirm','mfa_disable','settings','audit_history','audit_export']:
            response.headers['Cache-Control']='no-store'
            response.headers['Pragma']='no-cache'
            response.headers['Referrer-Policy']='no-referrer'
        return response

    @app.route('/login/mfa',methods=['GET','POST'])
    def login_mfa():
        if not session.get('pending_login') or session.get('pending_until',0)<time.time() or session.get('pending_version')!=state()['session_version']:
            session.clear();return redirect(url_for('login'))
        if request.method=='POST':
            if limited('mfa'):flash('ลองหลายครั้งเกินไป กรุณารอ 5 นาที')
            elif verify_factor(request.form.get('code','')):
                finish_login();session['mfa_verified']=True
                return redirect(url_for('dashboard'))
            else:
                failure('mfa');audit('mfa.login_failed',status=401);flash('รหัส MFA ไม่ถูกต้องหรือใช้แล้ว')
        return render_template('mfa_login.html',csrf=session['csrf'])

    @app.route('/security/mfa',methods=['GET','POST'])
    def mfa_setup():
        current=state()
        if request.method=='POST':
            if limited('security'):flash('ลองหลายครั้งเกินไป กรุณารอ 5 นาที')
            elif not check_password_hash(ctx['PASSWORD_HASH'],request.form.get('current_password','')):
                failure('security');flash('รหัสผ่านปัจจุบันไม่ถูกต้อง')
            elif current['enabled']:
                flash('MFA เปิดใช้งานอยู่แล้ว')
            else:
                secret=pyotp.random_base32()
                owner=secrets.token_hex(24);session['mfa_owner']=owner
                encrypted=ctx['password_cipher']().encrypt(secret.encode()).decode()
                with connect() as db:db.execute('UPDATE admin_security SET pending_encrypted=?,pending_owner=?,pending_until=? WHERE id=1',(encrypted,hashlib.sha256(owner.encode()).hexdigest(),time.time()+600))
                audit('mfa.setup_started');return redirect(url_for('mfa_setup'))
        current=state();secret='';qr=''
        owner=session.get('mfa_owner','')
        if not current['enabled'] and owner and secrets.compare_digest(hashlib.sha256(owner.encode()).hexdigest(),current['pending_owner']) and current['pending_until']>time.time():
            secret=ctx['password_cipher']().decrypt(current['pending_encrypted'].encode()).decode()
            uri=pyotp.TOTP(secret).provisioning_uri(name='admin',issuer_name='Hospital Backup Center')
            image=qrcode.make(uri,image_factory=qrcode.image.svg.SvgPathImage)
            output=io.BytesIO();image.save(output);qr=Markup(output.getvalue().decode())
        return render_template('security.html',mode='setup',enabled=bool(current['enabled']),secret=secret,qr=qr,csrf=session['csrf'])

    @app.post('/security/mfa/confirm')
    def mfa_confirm():
        current=state();owner=session.get('mfa_owner','')
        if limited('security'):abort(429)
        if current['enabled'] or not owner or current['pending_until']<time.time() or not secrets.compare_digest(hashlib.sha256(owner.encode()).hexdigest(),current['pending_owner']):abort(400)
        secret=ctx['password_cipher']().decrypt(current['pending_encrypted'].encode()).decode()
        try:counter=pyotp.TOTP(secret).verify_and_get_timecode(request.form.get('code','').strip(),valid_window=1)
        except pyotp.OTPVerificationError:
            failure('security');flash('รหัส MFA ไม่ถูกต้อง');return redirect(url_for('mfa_setup'))
        codes=[secrets.token_hex(8) for _ in range(10)]
        with connect() as db:
            changed=db.execute("UPDATE admin_security SET enabled=1,secret_encrypted=pending_encrypted,last_counter=?,pending_encrypted='',pending_owner='',pending_until=0,session_version=session_version+1 WHERE id=1 AND enabled=0 AND pending_owner=? AND pending_until>?",(counter,current['pending_owner'],time.time())).rowcount
            if changed!=1:abort(409)
            db.execute('DELETE FROM recovery_codes')
            db.executemany('INSERT INTO recovery_codes(hash) VALUES (?)',[(hashlib.sha256(code.encode()).hexdigest(),) for code in codes])
        session['auth_version']=state()['session_version'];session['mfa_verified']=True;session.pop('mfa_owner',None)
        audit('mfa.enabled')
        return render_template('security.html',mode='recovery',codes=codes,csrf=session['csrf'])

    @app.post('/security/mfa/disable')
    def mfa_disable():
        if limited('security'):abort(429)
        if not check_password_hash(ctx['PASSWORD_HASH'],request.form.get('current_password','')) or not verify_factor(request.form.get('code','')):
            failure('security');flash('รหัสผ่านหรือ MFA ไม่ถูกต้อง');return redirect(url_for('mfa_setup'))
        with connect() as db:
            db.execute("UPDATE admin_security SET enabled=0,secret_encrypted='',last_counter=-1,pending_encrypted='',pending_owner='',pending_until=0,session_version=session_version+1 WHERE id=1")
            db.execute('DELETE FROM recovery_codes')
        session['auth_version']=state()['session_version'];session.pop('mfa_verified',None)
        audit('mfa.disabled');flash('ปิด MFA แล้ว');return redirect(url_for('mfa_setup'))

    def audit_rows():
        event=request.args.get('event','').strip()[:100]
        page=max(1,int(request.args.get('page','1'))) if request.args.get('page','1').isdigit() else 1
        with connect() as db:
            rows=[dict(r) for r in db.execute("SELECT * FROM audit_log WHERE (?='' OR event=?) ORDER BY id DESC LIMIT 100 OFFSET ?",(event,event,(page-1)*100))]
        return rows,event,page

    @app.get('/audit')
    def audit_history():
        rows,event,page=audit_rows()
        return render_template('security.html',mode='audit',rows=rows,event=event,page=page,csrf=session['csrf'])

    @app.get('/audit/export')
    def audit_export():
        # Export all matching records, not just the page, without including sensitive payloads.
        event=request.args.get('event','').strip()[:100]
        with connect() as db:rows=[dict(r) for r in db.execute("SELECT * FROM audit_log WHERE (?='' OR event=?) ORDER BY id DESC",(event,event))]
        output=io.StringIO();writer=csv.writer(output)
        writer.writerow(['id','occurred_at_utc','actor','ip','event','endpoint','method','status','detail'])
        for row in rows:
            values=[row[key] for key in ['id','occurred_at','actor','ip','event','endpoint','method','status','detail']]
            writer.writerow(["'"+v if isinstance(v,str) and v.startswith(('=','+','-','@')) else v for v in values])
        return Response('\ufeff'+output.getvalue(),mimetype='text/csv',headers={'Content-Disposition':'attachment; filename="audit-log.csv"'})
