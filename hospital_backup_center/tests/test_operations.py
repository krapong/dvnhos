import io
import stat
import unittest
from types import SimpleNamespace
from unittest.mock import MagicMock, patch
from test_servers import ServerTests
from operations import checked_folder, inventory
import app

class MemoryFile(io.BytesIO):
    def stat(self): return SimpleNamespace(st_size=len(self.getvalue()))

class FakeSFTP:
    def __init__(self):
        self.name='20261118_133906'
        self.content={'.hbc-complete':b'HBC_DB_ONLY_V1\n','databases.sql.gz':b'compressed-db-test','databases.txt':b'db1\ndb2\n'}
    def __enter__(self): return self
    def __exit__(self,*args): pass
    def close(self): pass
    def normalize(self,path): return path
    def lstat(self,path):return SimpleNamespace(st_mode=stat.S_IFDIR if path.endswith(self.name) else stat.S_IFREG)
    def stat(self,path):return SimpleNamespace(st_size=18,st_mtime=1800000000)
    def open(self,path,mode='r'): return MemoryFile(self.content[path.rsplit('/',1)[-1]])
    def listdir_attr(self,root):return [SimpleNamespace(filename=self.name,st_mode=stat.S_IFDIR)]

class OperationTests(ServerTests):
    def remote(self):
        c=MagicMock();sf=FakeSFTP();c.open_sftp.return_value=sf
        out=MagicMock();out.read.return_value=b'Filesystem 1024-blocks Used Available Capacity Mounted on\n/dev/test 104857600 26214400 78643200 25% /var\nactive\n'
        out.channel.recv_exit_status.return_value=0
        err=MagicMock();err.read.return_value=b''
        c.exec_command.return_value=(MagicMock(),out,err)
        return c

    def test_inventory_and_traversal(self):
        sf=FakeSFTP()
        self.assertEqual(len(inventory(sf)),1)
        for name in ['../etc/passwd','20261118_133906/../../etc','x']:
            with self.assertRaises(ValueError):checked_folder(sf,name)
        sf.normalize=lambda path:'/etc'
        with self.assertRaises(ValueError):checked_folder(sf,sf.name)

    def test_all_read_menus_and_download(self):
        with patch.object(app,'ssh_connection',return_value=self.remote()):
            for path in ['/history','/notifications','/settings','/help','/files?host=10.0.2.16','/schedule?host=10.0.2.16','/retention?host=10.0.2.16','/storage','/restore?host=10.0.2.16','/api/health/10.0.2.16']:
                self.assertEqual(self.c.get(path).status_code,200,path)
            r=self.c.get('/files/10.0.2.16/20261118_133906/download')
            self.assertEqual(r.status_code,200)
            self.assertEqual(r.data,b'compressed-db-test')
            self.assertIn('attachment',r.headers['Content-Disposition'])
            with app.connect_db() as db:
                count=db.execute("SELECT count(*) FROM jobs WHERE trigger_type='remote'").fetchone()[0]
            self.assertEqual(count,1)
            self.c.get('/files?host=10.0.2.16')
            with app.connect_db() as db:
                self.assertEqual(db.execute('SELECT count(*) FROM jobs').fetchone()[0],1)

    def test_no_unauthorized_mutations(self):
        client=self.remote()
        with patch.object(app,'ssh_connection',return_value=client):
            r=self.c.post('/retention',data={'csrf':'test','host':'10.0.2.16','confirm':'wrong'})
            self.assertEqual(r.status_code,200)
            client.exec_command.assert_not_called()
            r=self.c.post('/restore',data={'csrf':'test','host':'10.0.2.16','target':'10.0.2.14','name':'20261118_133906','confirm':'RESTORE 10.0.2.14'})
            self.assertIn('เครื่องทดสอบ',r.get_data(as_text=True))
            client.exec_command.assert_not_called()
        for path in ['/schedule','/retention','/restore','/settings']:
            self.assertEqual(self.c.post(path,data={}).status_code,403)

    def test_health_values(self):
        with patch.object(app,'ssh_connection',return_value=self.remote()):
            data=self.c.get('/api/health/10.0.2.16').get_json()
            self.assertEqual(data['available_gb'],75)
            self.assertEqual(data['used_percent'],25)
            self.assertTrue(data['schedule_active'])

    def test_backup_buttons_dispatch(self):
        with patch.object(app,'launch',return_value=True) as launch:
            self.assertEqual(self.c.post('/backup/10.0.2.16',data={'csrf':'test'}).status_code,302)
            launch.assert_called_with('10.0.2.16','manual')
            launch.reset_mock()
            self.assertEqual(self.c.post('/backup-all',data={'csrf':'test'}).status_code,302)
            self.assertEqual(launch.call_count,2)
        self.assertEqual(self.c.post('/backup/10.0.2.99',data={'csrf':'test'}).status_code,404)

    def test_settings_password_change_persists(self):
        import tempfile
        from pathlib import Path
        from werkzeug.security import generate_password_hash,check_password_hash
        with tempfile.TemporaryDirectory() as d:
            path=Path(d);(path/'.env').write_text('SECRET_KEY=unchanged\nADMIN_PASSWORD_HASH=old\n')
            with patch.object(app,'ROOT',path), patch.object(app,'PASSWORD_HASH',generate_password_hash('current-password')):
                r=self.c.post('/settings',data={'csrf':'test','current_password':'wrong','new_password':'next-password-123','confirm_password':'next-password-123'})
                self.assertIn('ADMIN_PASSWORD_HASH=old',(path/'.env').read_text())
                r=self.c.post('/settings',data={'csrf':'test','current_password':'current-password','new_password':'next-password-123','confirm_password':'next-password-123'})
                self.assertEqual(r.status_code,200)
                self.assertTrue(check_password_hash(app.PASSWORD_HASH,'next-password-123'))
                self.assertIn('SECRET_KEY=unchanged',(path/'.env').read_text())
                self.assertNotIn('next-password-123',(path/'.env').read_text())

    def test_schedule_write_and_timer_commands(self):
        client=self.remote()
        sf=MagicMock()
        old=MagicMock();old.__enter__.return_value.read.return_value=b'Unit=hospital-db-backup.service\n'
        new=MagicMock();sf.open.side_effect=[old,new]
        client.open_sftp.return_value=MagicMock()
        client.open_sftp.return_value.__enter__.return_value=sf
        with patch.object(app,'ssh_connection',return_value=client):
            r=self.c.post('/schedule',data={'csrf':'test','host':'10.0.2.16','day':'Wed','hour':'4','minute':'15','enabled':'1'})
        self.assertEqual(r.status_code,200)
        content=new.__enter__.return_value.write.call_args.args[0]
        self.assertIn('OnCalendar=Wed *-*-* 04:15:00 Asia/Bangkok',content)
        commands=[call.args[0] for call in client.exec_command.call_args_list]
        self.assertTrue(any('enable --now hospital-db-backup.timer' in cmd for cmd in commands))
        self.assertFalse(any('start hospital-db-backup.service' in cmd for cmd in commands))

    def test_restore_worker_with_synthetic_sql_only(self):
        import gzip
        from werkzeug.security import generate_password_hash
        source=self.remote();target=self.remote()
        sf=FakeSFTP();sf.content['databases.sql.gz']=gzip.compress(b'CREATE DATABASE test_restore;\n')
        source.open_sftp.return_value=sf
        with app.connect_db() as conn:
            conn.execute('UPDATE servers SET restore_target=1 WHERE host=?',('10.0.2.14',))
        pool=MagicMock();pool.submit.side_effect=lambda fn:fn()
        with patch.object(app,'ssh_connection',side_effect=lambda host:source if host=='10.0.2.16' else target),patch.object(app,'PASSWORD_HASH',generate_password_hash('unit-admin-password')),patch.object(app,'POOL',pool):
            r=self.c.post('/restore',data={'csrf':'test','host':'10.0.2.16','target':'10.0.2.14','name':sf.name,'confirm':'RESTORE 10.0.2.14','admin_password':'unit-admin-password'})
        self.assertEqual(r.status_code,302)
        with app.connect_db() as conn:
            self.assertEqual(conn.execute('SELECT status FROM restore_jobs').fetchone()[0],'success')
        stdin=target.exec_command.return_value[0]
        stdin.write.assert_called_with(b'CREATE DATABASE test_restore;\n')
        self.assertNotIn('10.0.2.14',app.RUNNING)

if __name__=='__main__':unittest.main()
