"""Hospital Backup Center: two fixed hosts, SSH key-based execution, audit history."""
import os
import socket
import ipaddress
import hashlib
import base64
import sys
import json
import time
import sqlite3
import secrets
import threading
from contextlib import contextmanager
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime

from cryptography.fernet import Fernet
import paramiko
from dotenv import load_dotenv
from flask import Flask, request, redirect, url_for, session, render_template, jsonify, abort, flash
from werkzeug.security import check_password_hash
from security import init_security_db, register_security

ROOT = Path(__file__).resolve().parent
load_dotenv(ROOT / '.env')
DEFAULT_HOSTS = {'10.0.2.16': {'name': 'HOSxP Slave 01', 'databases': ['hospital_db','hms','drugalert','drugstock_alert','lr_supplement','medical_request_db','pd_dashboard_db','bed-monitor']},
         '10.0.2.14': {'name': 'Application Server', 'databases': ['lr_supplement','ncdappo','nursing_center']}}
SECRET_KEY = os.environ.get('SECRET_KEY', '')
PASSWORD_HASH = os.environ.get('ADMIN_PASSWORD_HASH', '')
if len(SECRET_KEY) < 32 or PASSWORD_HASH.startswith('REPLACE') or not PASSWORD_HASH:
    raise RuntimeError('Configure SECRET_KEY and ADMIN_PASSWORD_HASH in .env first')
app = Flask(__name__)
app.secret_key = SECRET_KEY
app.config.update(SESSION_COOKIE_HTTPONLY=True, SESSION_COOKIE_SAMESITE='Lax', SESSION_COOKIE_SECURE=os.getenv('HTTPS', '0') == '1', MAX_CONTENT_LENGTH=1024*1024)
DB = ROOT / 'data' / 'history.sqlite3'
DB.parent.mkdir(exist_ok=True)
POOL = ThreadPoolExecutor(max_workers=2)
LOCK = threading.Lock()
RUNNING = set()
ATTEMPTS = {}


@contextmanager
def connect_db():
    c = sqlite3.connect(DB, timeout=15)
    c.row_factory = sqlite3.Row
    try:
        with c:
            yield c
    finally:
        c.close()


def init_db():
    with connect_db() as db:
        db.execute('''CREATE TABLE IF NOT EXISTS jobs (id INTEGER PRIMARY KEY AUTOINCREMENT,
          host TEXT NOT NULL, trigger_type TEXT NOT NULL, started_at TEXT NOT NULL,
          completed_at TEXT, status TEXT NOT NULL, folder TEXT, sql_bytes INTEGER,
          web_bytes INTEGER, output TEXT)''')
        db.execute("""CREATE TABLE IF NOT EXISTS servers (
            host TEXT PRIMARY KEY, name TEXT NOT NULL, databases TEXT NOT NULL,
            port INTEGER NOT NULL DEFAULT 22, ssh_user TEXT NOT NULL,
            private_key TEXT NOT NULL DEFAULT '', fingerprint TEXT NOT NULL DEFAULT '',
            test_result TEXT NOT NULL DEFAULT '', tested_at TEXT)""")
        columns = {row['name'] for row in db.execute('PRAGMA table_info(servers)')}
        for name, definition in [('auth_method', "TEXT NOT NULL DEFAULT 'key'"), ('password_encrypted', "TEXT NOT NULL DEFAULT ''"), ('restore_target', 'INTEGER NOT NULL DEFAULT 0')]:
            if name not in columns:
                db.execute(f'ALTER TABLE servers ADD COLUMN {name} {definition}')
        for host, info in DEFAULT_HOSTS.items():
            db.execute('INSERT OR IGNORE INTO servers(host,name,databases,ssh_user,private_key) VALUES (?,?,?,?,?)',
                       (host, info['name'], json.dumps(info['databases']), os.getenv('SSH_USER','root'), os.getenv('SSH_PRIVATE_KEY','')))
        db.commit()
    init_security_db(connect_db)


def configured_hosts():
    with connect_db() as db:
        return {r['host']: {**dict(r), 'databases': json.loads(r['databases'])} for r in db.execute("SELECT host,name,databases,port,ssh_user,private_key,fingerprint,test_result,tested_at,auth_method,restore_target,(length(password_encrypted) > 0) AS has_password FROM servers ORDER BY rowid")}


def password_cipher():
    return Fernet(base64.urlsafe_b64encode(hashlib.sha256((SECRET_KEY + ':ssh-password-v1').encode()).digest()))


def save_ssh_password(host, password):
    encrypted = password_cipher().encrypt(password.encode()).decode()
    with connect_db() as db:
        db.execute("UPDATE servers SET password_encrypted=?,auth_method='password' WHERE host=?", (encrypted, host))


def fingerprint(key):
    return 'SHA256:' + base64.b64encode(hashlib.sha256(key.asbytes()).digest()).decode().rstrip('=')


def local_path(value):
    p = Path(value).expanduser()
    return p if p.is_absolute() else ROOT / p


class VerifiedHostKey(paramiko.MissingHostKeyPolicy):
    def __init__(self, expected):
        self.expected = expected

    def missing_host_key(self, client, hostname, key):
        if not self.expected or not secrets.compare_digest(fingerprint(key), self.expected):
            raise paramiko.SSHException('Host fingerprint mismatch; verify fingerprint with the server administrator')


def ssh_connection(host):
    info = configured_hosts()[host]
    c = paramiko.SSHClient()
    known = local_path(os.getenv('SSH_KNOWN_HOSTS', 'keys/known_hosts'))
    if known.is_file():
        c.load_host_keys(str(known))
    c.set_missing_host_key_policy(VerifiedHostKey(info['fingerprint']))
    key = local_path(info['private_key']) if info['private_key'] else None
    credentials = {}
    if info['auth_method'] == 'password':
        with connect_db() as db:
            encrypted = db.execute('SELECT password_encrypted FROM servers WHERE host=?', (host,)).fetchone()[0]
        if not encrypted:
            raise RuntimeError('ยังไม่ได้ตั้งรหัสผ่าน SSH')
        credentials['password'] = password_cipher().decrypt(encrypted.encode()).decode()
    else:
        if not key or not key.is_file():
            raise RuntimeError('ยังไม่พบ SSH private key กรุณาตั้งค่าในหน้าจัดการเซิร์ฟเวอร์')
        credentials['key_filename'] = str(key)
    try:
        c.connect(hostname=host, port=info['port'], username=info['ssh_user'],
                  **credentials, allow_agent=False, look_for_keys=False,
                  timeout=8, auth_timeout=8, banner_timeout=8)
        return c
    except Exception:
        c.close()
        raise


def perform(host, trigger):
    started = datetime.now().astimezone().isoformat(timespec='seconds')
    with connect_db() as db:
        cur = db.execute('INSERT INTO jobs(host,trigger_type,started_at,status) VALUES (?,?,?,?)', (host, trigger, started, 'running'))
        job_id = cur.lastrowid
        db.commit()
    status = 'failed'; folder = None; sql_size = None; web_size = None; output = ''
    try:
        client = ssh_connection(host)
        try:
            # This is a fixed, administrator-installed command; never interpolates user input.
            command = os.getenv('BACKUP_COMMAND', '/usr/local/sbin/backup-agent.sh')
            if command != '/usr/local/sbin/backup-agent.sh':
                raise RuntimeError('Only fixed /usr/local/sbin/backup-agent.sh is permitted')
            # Refuse an older agent that would also archive website files.
            command = ("if grep -q '^# HBC_DB_ONLY_V1$' /usr/local/sbin/backup-agent.sh; "
                       "then /usr/local/sbin/backup-agent.sh --db-only; "
                       "else echo 'ERROR: Install the database-only backup-agent.sh first' >&2; exit 64; fi")
            _stdin, stdout, stderr = client.exec_command(command, timeout=15)
            stdout.channel.settimeout(7200)
            out = stdout.read().decode('utf-8', 'replace')
            err = stderr.read().decode('utf-8', 'replace')
            exit_code = stdout.channel.recv_exit_status()
            output = (out + '\n' + err)[-12000:]
            if exit_code == 0:
                line = next((x for x in out.splitlines() if x.startswith('SUCCESS|')), '')
                parts = line.split('|')
                if len(parts) != 3: raise RuntimeError('Expected database-only SUCCESS marker')
                folder, sql_size = parts[1], int(parts[2])
                if not folder or sql_size < 0: raise RuntimeError('Invalid database backup result')
                status = 'success'
            else:
                output += f'\nRemote exit status: {exit_code}'
        finally:
            client.close()
    except Exception as ex:
        output = (output + '\n' + type(ex).__name__ + ': ' + str(ex))[-12000:]
    finally:
        with connect_db() as db:
            db.execute('UPDATE jobs SET completed_at=?,status=?,folder=?,sql_bytes=?,web_bytes=?,output=? WHERE id=?',
                       (datetime.now().astimezone().isoformat(timespec='seconds'), status, folder, sql_size, web_size, output, job_id))
            db.commit()
        with LOCK:
            RUNNING.discard(host)
        audit('backup.completed', {'host':host,'job_id':job_id,'result':status}, 200 if status=='success' else 500)
    return status


def launch(host, trigger):
    if host not in configured_hosts(): return False
    with LOCK:
        if host in RUNNING: return False
        RUNNING.add(host)
    try:
        POOL.submit(perform, host, trigger)
    except Exception:
        with LOCK: RUNNING.discard(host)
        raise
    return True


@app.before_request
def csrf_and_auth():
    if request.endpoint == 'static': return
    if request.endpoint not in ('login','login_mfa') and not session.get('authed'):
        if request.path.startswith('/api/'):
            abort(401)
        return redirect(url_for('login'))
    if request.method == 'POST':
        token = session.get('csrf')
        posted = request.form.get('csrf') or request.headers.get('X-CSRF-Token')
        if not token or not posted or not secrets.compare_digest(token, posted):
            abort(403)


@app.route('/login', methods=['GET','POST'])
def login():
    if 'csrf' not in session: session['csrf'] = secrets.token_hex(24)
    if request.method == 'POST':
        if auth_limited('password'):
            flash('เข้าสู่ระบบผิดพลาดหลายครั้ง โปรดลองใหม่หลัง 5 นาที')
        elif check_password_hash(PASSWORD_HASH, request.form.get('password', '')):
            current=security_state()
            session.clear()
            session['csrf']=secrets.token_hex(24)
            if current['enabled']:
                session.update(pending_login=True,pending_until=time.time()+300,pending_version=current['session_version'])
                audit('login.password_verified')
                return redirect(url_for('login_mfa'))
            finish_login()
            return redirect(url_for('dashboard'))
        else:
            auth_failure('password')
            audit('login.failed',status=401)
            flash('รหัสผ่านไม่ถูกต้อง')
    return render_template('login.html', csrf=session['csrf'])


@app.post('/logout')
def logout():
    audit('logout')
    session.clear()
    return redirect(url_for('login'))


@app.get('/')
def dashboard():
    with connect_db() as db:
        jobs = [dict(r) for r in db.execute('SELECT * FROM jobs ORDER BY id DESC LIMIT 100')]
    schedule_path = ROOT / 'data' / 'schedule.json'
    schedules = json.loads(schedule_path.read_text(encoding='utf-8')) if schedule_path.exists() else {}
    return render_template('dashboard.html', schedules=schedules, hosts=configured_hosts(), jobs=jobs, running=RUNNING.copy(), csrf=session['csrf'], today=datetime.now().astimezone().date().isoformat())


@app.post('/backup/<host>')
def backup(host):
    if host not in configured_hosts(): abort(404)
    if launch(host, 'manual'):
        flash(f'เริ่มสำรองข้อมูล {host} แล้ว')
    else:
        flash(f'{host} กำลังสำรองข้อมูลอยู่')
    return redirect(url_for('dashboard'))


@app.post('/backup-all')
def backup_all():
    count = sum(launch(host, 'manual-all') for host in configured_hosts())
    flash(f'เริ่มสำรองข้อมูล {count} เครื่อง')
    return redirect(url_for('dashboard'))


@app.get('/api/status')
def status():
    with connect_db() as db:
        latest = {host: dict(r) if (r := db.execute('SELECT * FROM jobs WHERE host=? ORDER BY id DESC LIMIT 1', (host,)).fetchone()) else None for host in configured_hosts()}
    return jsonify({'running': list(RUNNING), 'latest': latest})


@app.get('/jobs/<int:job_id>')
def job_detail(job_id):
    with connect_db() as db:
        row = db.execute('SELECT * FROM jobs WHERE id=?', (job_id,)).fetchone()
    if not row: abort(404)
    return render_template('job.html', job=dict(row), csrf=session['csrf'])


@app.route('/servers', methods=['GET', 'POST'])
def servers():
    if request.method == 'POST':
        try:
            host = str(ipaddress.ip_address(request.form.get('host','').strip()))
            port = int(request.form.get('port', '22'))
            if not 1 <= port <= 65535:
                raise ValueError('พอร์ตต้องอยู่ระหว่าง 1–65535')
            name = request.form.get('name','').strip()
            user = request.form.get('ssh_user','').strip()
            databases = [v.strip() for v in request.form.get('databases','').split(',') if v.strip()]
            if not name or not user or not databases or len(name)>120 or len(user)>80:
                raise ValueError('กรอกชื่อเซิร์ฟเวอร์ บัญชี SSH และรายชื่อฐานข้อมูลให้ครบ')
            method = request.form.get('auth_method', 'key')
            if method not in ('key', 'password'):
                raise ValueError('เลือกวิธียืนยันตัวตน SSH ให้ถูกต้อง')
            password = request.form.get('ssh_password', '')
            if len(password) > 1024:
                raise ValueError('รหัสผ่านยาวเกินไป')
            old = configured_hosts().get(host)
            if method == 'password' and not password and not (old and old['has_password']):
                raise ValueError('กรอกรหัสผ่าน SSH ก่อนบันทึก')
            fp = request.form.get('fingerprint','').strip()
            if fp and (not fp.startswith('SHA256:') or len(fp)!=50):
                raise ValueError('รูปแบบ fingerprint ต้องเป็น SHA256: ตามด้วยค่า 43 ตัวอักษร')
            with LOCK:
                if host in RUNNING:
                    raise ValueError('กำลังสำรองข้อมูล กรุณารอให้งานเสร็จก่อนแก้ไข')
                with connect_db() as db:
                    exists = db.execute('SELECT 1 FROM servers WHERE host=?',(host,)).fetchone()
                    if exists and request.form.get('editing') != host:
                        raise ValueError('มี IP นี้แล้ว กรุณาเลือกแก้ไขรายการเดิม')
                    db.execute("""INSERT INTO servers(host,name,databases,port,ssh_user,private_key,fingerprint)
                        VALUES (?,?,?,?,?,?,?) ON CONFLICT(host) DO UPDATE SET name=excluded.name,
                        databases=excluded.databases,port=excluded.port,ssh_user=excluded.ssh_user,
                        private_key=excluded.private_key,fingerprint=excluded.fingerprint,
                        test_result='',tested_at=NULL""",
                        (host,name,json.dumps(databases),port,user,request.form.get('private_key','').strip(),fp))
                    db.execute('UPDATE servers SET auth_method=?,restore_target=? WHERE host=?', (method, int(request.form.get('restore_target') == '1'), host))
                    if password:
                        db.execute('UPDATE servers SET password_encrypted=? WHERE host=?',
                                   (password_cipher().encrypt(password.encode()).decode(), host))
            flash('บันทึกเซิร์ฟเวอร์แล้ว')
            return redirect(url_for('servers'))
        except (ValueError, TypeError) as ex:
            flash(str(ex))
            return render_template('servers.html', hosts=configured_hosts(), editing={k:v for k,v in request.form.items() if k != 'ssh_password'}, csrf=session['csrf']), 400
    host = request.args.get('edit')
    info = configured_hosts().get(host)
    editing = {**info, 'host':host, 'editing':host, 'databases':', '.join(info['databases'])} if info else {}
    return render_template('servers.html', hosts=configured_hosts(), editing=editing, csrf=session['csrf'])


@app.post('/servers/<host>/test')
def test_server(host):
    info = configured_hosts().get(host)
    if not info:
        abort(404)
    try:
        with socket.create_connection((host,info['port']), timeout=5) as sock:
            transport = paramiko.Transport(sock)
            try:
                transport.start_client(timeout=6)
                key = transport.get_remote_server_key()
                observed = fingerprint(key)
            finally:
                transport.close()
        result = 'เชื่อมต่อ SSH ได้ • fingerprint ที่ตรวจพบ: ' + observed
        if not info['fingerprint']:
            result += '\nยังไม่ได้ยืนยันตัวตนเครื่อง: ตรวจสอบ fingerprint นี้ผ่านผู้ดูแลเครื่องก่อนบันทึกในช่อง fingerprint'
        elif not secrets.compare_digest(info['fingerprint'], observed):
            result += '\nไม่ผ่าน: fingerprint ไม่ตรงกับค่าที่บันทึกไว้'
        else:
            client = ssh_connection(host)
            try:
                _, stdout, stderr = client.exec_command("test -x /usr/local/sbin/backup-agent.sh && grep -q '^# HBC_DB_ONLY_V1$' /usr/local/sbin/backup-agent.sh", timeout=8)
                code = stdout.channel.recv_exit_status()
                result += '\nเข้าสู่ระบบ SSH สำเร็จ • ' + ('พบ backup-agent แบบฐานข้อมูลเท่านั้น' if code==0 else 'ยังไม่พบ backup-agent แบบฐานข้อมูลเท่านั้น')
                result += '\nการทดสอบนี้ไม่ได้สั่งสำรองข้อมูล'
            finally:
                client.close()
    except Exception as ex:
        result = 'ทดสอบไม่สำเร็จ: ' + type(ex).__name__ + ': ' + str(ex)
    with connect_db() as db:
        db.execute('UPDATE servers SET test_result=?,tested_at=? WHERE host=?',
                   (result,datetime.now().astimezone().isoformat(timespec='seconds'),host))
    flash(result)
    return redirect(url_for('servers',edit=host))


register_security(app, globals())

from operations import register_operations
register_operations(app, globals())


def cli():
    if len(sys.argv) == 3 and sys.argv[1] == 'run':
        host = sys.argv[2]
        if host not in configured_hosts():
            raise SystemExit('Invalid host')
        result = perform(host, 'scheduled')
        raise SystemExit(0 if result == 'success' else 1)
    if len(sys.argv) == 2 and sys.argv[1] == 'run-all':
        results = [perform(host, 'scheduled') for host in configured_hosts()]
        raise SystemExit(0 if all(r == 'success' for r in results) else 1)
    raise SystemExit('Usage: python3 app.py run 10.0.2.14 | run 10.0.2.16 | run-all')


init_db()
if __name__ == '__main__':
    if len(sys.argv) > 1:
        cli()
    else:
        # Development only. Use gunicorn behind TLS reverse proxy in production.
        app.run(host='127.0.0.1', port=int(os.getenv('PORT', '8088')), debug=False)

# To download very large archives, prefer a dedicated SFTP client to avoid
# tying up a dashboard web worker. Downloads are limited to recorded successful jobs.
