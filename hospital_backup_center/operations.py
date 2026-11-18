"""Authenticated dashboard operations. Remote changes are POST-only and CSRF protected."""
import gzip
import json
import re
import shlex
import stat
from datetime import datetime, timezone
from pathlib import PurePosixPath
from flask import jsonify, request, render_template, session, flash, redirect, url_for, abort, Response, stream_with_context
from werkzeug.security import check_password_hash, generate_password_hash

ROOT = '/var/backup/hospital-center'
SET_NAME = re.compile(r'^\d{8}_\d{6}$')


def checked_folder(sftp, name):
    if not SET_NAME.fullmatch(name):
        raise ValueError('ชื่อชุดสำรองไม่ถูกต้อง')
    path = ROOT + '/' + name
    if sftp.normalize(ROOT) != ROOT or sftp.normalize(path) != path:
        raise ValueError('ไม่อนุญาต symlink ในเส้นทางสำรอง')
    for suffix in ['', '/.hbc-complete', '/databases.sql.gz']:
        mode = sftp.lstat(path + suffix).st_mode
        if stat.S_ISLNK(mode):
            raise ValueError('ไม่อนุญาต symlink')
        if suffix and not stat.S_ISREG(mode):
            raise ValueError('ไฟล์สำรองไม่ถูกต้อง')
    with sftp.open(path + '/.hbc-complete') as f:
        if f.read(100).strip() != b'HBC_DB_ONLY_V1':
            raise ValueError('ชุดสำรองไม่ใช่ของระบบ')
    return path


def inventory(sftp):
    result = []
    try:
        entries = sftp.listdir_attr(ROOT)
    except FileNotFoundError:
        return result
    for entry in entries:
        if not SET_NAME.fullmatch(entry.filename) or not stat.S_ISDIR(entry.st_mode):
            continue
        try:
            folder = checked_folder(sftp, entry.filename)
            data = sftp.stat(folder + '/databases.sql.gz')
            marker = sftp.stat(folder + '/.hbc-complete')
            with sftp.open(folder + '/databases.txt') as f:
                databases = f.read(16384).decode().splitlines()
            result.append(dict(name=entry.filename, size=data.st_size, completed=marker.st_mtime,
                               databases=databases, folder=folder))
        except (OSError, ValueError):
            continue
    return sorted(result, key=lambda item: (item['completed'], item['name']), reverse=True)


def register_operations(app, ctx):
    def hosts(): return ctx['configured_hosts']()
    def db(): return ctx['connect_db']()
    def connect(host):
        if host not in hosts(): abort(404)
        return ctx['ssh_connection'](host)
    def run(host, command):
        client = connect(host)
        try:
            _, out, err = client.exec_command(command, timeout=15)
            out.channel.settimeout(30)
            output = out.read().decode('utf-8', 'replace')
            error = err.read().decode('utf-8', 'replace')
            if out.channel.recv_exit_status():
                raise RuntimeError(error[-1500:] or output[-1500:] or 'คำสั่งบนเซิร์ฟเวอร์ไม่สำเร็จ')
            return output
        finally: client.close()
    def page(title, **values):
        return render_template('operations.html', title=title, hosts=hosts(), csrf=session['csrf'], **values)
    def selected(): return request.values.get('host') or next(iter(hosts()), '')

    @app.route('/api/health/<host>')
    def server_health(host):
        try:
            output = run(host, "(df -Pk /var/backup/hospital-center 2>/dev/null || df -Pk /var); systemctl show hospital-db-backup.timer -p ActiveState -p NextElapseUSecRealtime")
            lines = output.splitlines()
            fields = lines[1].split()
            total, used, available = map(int, fields[1:4])
            return jsonify(total_gb=round(total/1048576,2), available_gb=round(available/1048576,2),
                           used_percent=round(used/total*100,1), schedule_active=any(line in ['active','ActiveState=active'] for line in lines[2:]), next_run=next((line.split('=',1)[1] for line in lines if line.startswith('NextElapseUSecRealtime=')),''))
        except Exception as ex:
            return jsonify(error=str(ex)), 502

    @app.route('/history')
    def history():
        host = request.args.get('host', '')
        status = request.args.get('status', '')
        with db() as conn:
            rows = [dict(r) for r in conn.execute("SELECT * FROM jobs WHERE (?='' OR host=?) AND (?='' OR status=?) ORDER BY id DESC", (host,host,status,status))]
        return page('ประวัติการสำรอง', mode='history', jobs=rows, selected=host, status=status)

    @app.route('/files')
    def files():
        host = selected()
        rows, error = [], ''
        client = None
        try:
            client = connect(host)
            with client.open_sftp() as sftp: rows = inventory(sftp)
            # Import successful timer-generated backups without duplicating existing history.
            with db() as conn:
                for item in rows:
                    if not conn.execute('SELECT 1 FROM jobs WHERE host=? AND folder=?',(host,item['folder'])).fetchone():
                        stamp = datetime.fromtimestamp(item['completed'],timezone.utc).astimezone().isoformat(timespec='seconds')
                        conn.execute("INSERT INTO jobs(host,trigger_type,started_at,completed_at,status,folder,sql_bytes,output) VALUES (?,?,?,?,?,?,?,?)",(host,'remote',stamp,stamp,'success',item['folder'],item['size'],'Imported from completed remote backup'))
        except Exception as ex: error = str(ex)
        finally:
            if client: client.close()
        return page('ไฟล์สำรองและดาวน์โหลด', mode='files', selected=host, files=rows, error=error)

    @app.route('/files/<host>/<name>/download')
    def download_backup(host, name):
        client = connect(host)
        try:
            sftp = client.open_sftp()
            folder = checked_folder(sftp, name)
            file = sftp.open(folder + '/databases.sql.gz','rb')
            size = file.stat().st_size
        except Exception:
            client.close()
            abort(404)
        def chunks():
            try:
                while True:
                    chunk = file.read(262144)
                    if not chunk: break
                    yield chunk
            finally:
                file.close(); sftp.close(); client.close()
        response = Response(stream_with_context(chunks()), mimetype='application/gzip')
        response.headers['Content-Disposition'] = 'attachment; filename="databases_' + host + '_' + name + '.sql.gz"'
        response.headers['Content-Length'] = str(size)
        response.headers['Cache-Control'] = 'no-store'
        return response

    @app.route('/schedule', methods=['GET','POST'])
    def schedule():
        host = selected()
        error = ''
        if request.method == 'POST':
            try:
                day = request.form.get('day','Fri')
                hour, minute = int(request.form.get('hour','2')), int(request.form.get('minute','0'))
                if day not in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] or not (0<=hour<24 and 0<=minute<60):
                    raise ValueError('วันหรือเวลาไม่ถูกต้อง')
                enabled = request.form.get('enabled') == '1'
                client = connect(host)
                try:
                    # Only the timer installed by this application can be edited.
                    with client.open_sftp() as sftp:
                        with sftp.open('/etc/systemd/system/hospital-db-backup.timer') as f:
                            previous = f.read().decode()
                        if 'Unit=hospital-db-backup.service' not in previous:
                            raise ValueError('ยังไม่ได้ติดตั้ง timer ของระบบนี้')
                        content = '[Unit]\nDescription=Hospital database weekly backup\n[Timer]\nOnCalendar='+day+f' *-*-* {hour:02}:{minute:02}:00 Asia/Bangkok\nPersistent=true\nUnit=hospital-db-backup.service\n[Install]\nWantedBy=timers.target\n'
                        with sftp.open('/etc/systemd/system/hospital-db-backup.timer','w') as f:f.write(content)
                finally: client.close()
                action = 'enable --now' if enabled else 'disable --now'
                run(host, 'systemctl daemon-reload && systemctl '+action+' hospital-db-backup.timer'+(' && systemctl restart hospital-db-backup.timer' if enabled else ''))
                ctx['audit']('schedule.saved', {'host':host,'day':day,'hour':hour,'minute':minute,'enabled':enabled})
                flash('บันทึกตารางเวลาแล้ว')
            except Exception as ex: error = str(ex)
        details = ''
        try:
            details = run(host, "systemctl show hospital-db-backup.timer -p ActiveState -p NextElapseUSecRealtime -p TimersCalendar; systemctl cat hospital-db-backup.timer; systemctl show hospital-db-retention.timer -p ActiveState -p NextElapseUSecRealtime")
        except Exception as ex: error = str(ex)
        match = re.search(r'OnCalendar=(Mon|Tue|Wed|Thu|Fri|Sat|Sun) \*-\*-\* (\d{2}):(\d{2}):', details)
        current = dict(day=match[1],hour=int(match[2]),minute=int(match[3])) if match else dict(day='Fri',hour=2,minute=0)
        current['enabled'] = 'ActiveState=active' in details.split('# /etc/systemd')[0]
        return page('ตั้งเวลาอัตโนมัติ', mode='schedule', selected=host, details=details, error=error, current=current)

    @app.route('/retention', methods=['GET','POST'])
    def retention():
        host = selected()
        rows, error = [], ''
        try:
            if request.method == 'POST':
                if request.form.get('confirm') != host: raise ValueError('กรอก IP เซิร์ฟเวอร์เพื่อยืนยัน')
                output = run(host,'/usr/local/sbin/backup-agent.sh --prune-only')
                ctx['audit']('retention.completed', {'host':host,'deleted_count':output.count('PRUNED ')})
                flash(output.strip() or 'ตรวจแล้ว ไม่มีชุดสำรองที่ต้องลบ')
            client = connect(host)
            try:
                with client.open_sftp() as sftp: rows = inventory(sftp)
            finally: client.close()
            for index,item in enumerate(rows):
                item['expired'] = index >= 3
        except Exception as ex: error = str(ex)
        return page('ลบข้อมูลเก่าตามนโยบาย', mode='retention', selected=host, files=rows, error=error)

    @app.route('/storage')
    def storage():
        rows = []
        for host in hosts():
            try:
                output = run(host,"if test -d /var/backup/hospital-center; then df -Pk /var/backup/hospital-center; du -sk /var/backup/hospital-center; else df -Pk /var; echo 'Backup directory not created yet'; fi")
                rows.append(dict(host=host, output=output))
            except Exception as ex: rows.append(dict(host=host,output=str(ex)))
        return page('พื้นที่จัดเก็บข้อมูลสำรอง',mode='storage', rows=rows)

    @app.route('/server-log/<host>')
    def server_log(host):
        try:
            text = run(host, 'journalctl -u hospital-db-backup.service -n 100 --no-pager')
        except Exception as ex: text = str(ex)
        return page('Log งานสำรองอัตโนมัติ '+host, mode='log', text=text)

    @app.route('/notifications')
    def notifications():
        with db() as conn:
            jobs = [dict(r) for r in conn.execute("SELECT * FROM jobs WHERE status!='success' ORDER BY id DESC LIMIT 100")]
        return page('แจ้งเตือน',mode='history',jobs=jobs,selected='',status='')

    @app.route('/settings', methods=['GET','POST'])
    def settings():
        if request.method == 'POST':
            current, new = request.form.get('current_password',''),request.form.get('new_password','')
            if not check_password_hash(ctx['PASSWORD_HASH'],current): flash('รหัสผ่านปัจจุบันไม่ถูกต้อง')
            elif len(new)<12 or new!=request.form.get('confirm_password'): flash('รหัสผ่านใหม่ต้องตรงกันและยาวอย่างน้อย 12 ตัวอักษร')
            else:
                ctx['change_admin_password'](new)
                flash('เปลี่ยนรหัสผ่านผู้ดูแลระบบแล้ว')
        return page('ตั้งค่าระบบ',mode='settings')

    @app.route('/help')
    def help_page():
        return page('คู่มือการใช้งาน',mode='help',manual=(ctx['ROOT']/'README_TH.md').read_text(encoding='utf-8'))

    @app.route('/restore', methods=['GET','POST'])
    def restore():
        host=selected(); rows=[];error=''
        try:
            client=connect(host)
            try:
                with client.open_sftp() as sftp:rows=inventory(sftp)
            finally:client.close()
            if request.method=='POST':
                target=request.form.get('target','');name=request.form.get('name','')
                if target==host or not hosts().get(target,{}).get('restore_target'):
                    raise ValueError('เลือกเครื่องทดสอบที่เปิดสิทธิ์กู้คืน และต้องเป็นคนละเครื่องกับต้นทาง')
                if request.form.get('confirm')!='RESTORE '+target or not check_password_hash(ctx['PASSWORD_HASH'],request.form.get('admin_password','')):
                    raise ValueError('รหัสผ่านผู้ดูแลหรือข้อความยืนยันไม่ถูกต้อง')
                if name not in [item['name'] for item in rows]:raise ValueError('เลือกชุดสำรองที่มีอยู่จริง')
                with ctx['LOCK']:
                    if target in ctx['RUNNING'] or host in ctx['RUNNING']:raise ValueError('มีงานกำลังทำงาน กรุณารอ')
                    ctx['RUNNING'].add(target)
                with db() as conn:
                    conn.execute("CREATE TABLE IF NOT EXISTS restore_jobs(id INTEGER PRIMARY KEY,source TEXT,target TEXT,name TEXT,status TEXT,output TEXT)")
                    job=conn.execute("INSERT INTO restore_jobs(source,target,name,status,output) VALUES (?,?,?,'running','')",(host,target,name)).lastrowid
                def work():
                    source_client=target_client=None;status='failed';output=''
                    try:
                        source_client=connect(host);target_client=connect(target)
                        with source_client.open_sftp() as sf:
                            folder=checked_folder(sf,name)
                            # Verify the stored checksum before transmitting SQL to the test target.
                            _,out,err=source_client.exec_command('cd '+shlex.quote(folder)+' && gzip -t databases.sql.gz && sha256sum -c SHA256SUMS',timeout=15)
                            out.read();err.read()
                            if out.channel.recv_exit_status():raise ValueError('ไฟล์สำรองตรวจสอบไม่ผ่าน')
                            stdin,stdout,stderr=target_client.exec_command('flock -n /var/backup/hospital-center/.backup.lock mariadb --defaults-extra-file=/root/.hospital-backup.cnf',timeout=15)
                            stdout.channel.settimeout(7200)
                            # Drain bounded diagnostics concurrently to prevent pipe deadlock.
                            diagnostics=[]
                            def drain():
                                while True:
                                    data=stderr.read(4096)
                                    if not data:break
                                    diagnostics.append(data)
                                    if len(diagnostics)>16:diagnostics.pop(0)
                            reader=ctx['threading'].Thread(target=drain,daemon=True);reader.start()
                            with sf.open(folder+'/databases.sql.gz','rb') as raw:
                                with gzip.GzipFile(fileobj=raw) as sql:
                                    while True:
                                        chunk=sql.read(262144)
                                        if not chunk:break
                                        stdin.write(chunk)
                            stdin.flush();stdin.channel.shutdown_write()
                            stdout.read()
                            code=stdout.channel.recv_exit_status();reader.join(timeout=10)
                            if code:raise RuntimeError('MariaDB restore failed; exit '+str(code))
                            status='success';output='กู้คืนบนเครื่องทดสอบสำเร็จ'
                    except Exception as ex:output=type(ex).__name__+': '+str(ex)
                    finally:
                        for client in [source_client,target_client]:
                            if client:client.close()
                        with db() as conn:conn.execute('UPDATE restore_jobs SET status=?,output=? WHERE id=?',(status,output,job))
                        with ctx['LOCK']:ctx['RUNNING'].discard(target)
                try:
                    ctx['POOL'].submit(work)
                except Exception:
                    with ctx['LOCK']:ctx['RUNNING'].discard(target)
                    with db() as conn:conn.execute("UPDATE restore_jobs SET status='failed',output='Cannot start worker' WHERE id=?",(job,))
                    raise
                return redirect(url_for('restore_job',job_id=job))
        except Exception as ex:error=str(ex)
        return page('กู้คืนฐานข้อมูลบนเครื่องทดสอบ',mode='restore',selected=host,files=rows,error=error)

    @app.route('/restore/<int:job_id>')
    def restore_job(job_id):
        with db() as conn:
            conn.execute("CREATE TABLE IF NOT EXISTS restore_jobs(id INTEGER PRIMARY KEY,source TEXT,target TEXT,name TEXT,status TEXT,output TEXT)")
            row=conn.execute('SELECT * FROM restore_jobs WHERE id=?',(job_id,)).fetchone()
        if not row:abort(404)
        return page('ผลการกู้คืน',mode='restore_job',job=dict(row))
