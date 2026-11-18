import tempfile
import unittest
from unittest.mock import MagicMock, patch
from pathlib import Path
import app

class ServerTests(unittest.TestCase):
    def setUp(self):
        self.original = app.DB
        self.temp = tempfile.TemporaryDirectory()
        app.DB = Path(self.temp.name) / 'test.sqlite3'
        app.init_db()
        self.c = app.app.test_client()
        with self.c.session_transaction() as s:
            s.update(authed=True, csrf='test')
        self.payload = dict(csrf='test', host='10.0.2.99', port='2222', name='Test',
                            ssh_user='backup', private_key='keys/test', databases='db1, db2', fingerprint='')

    def tearDown(self):
        app.DB = self.original
        self.temp.cleanup()

    def test_add_edit_persistence(self):
        self.assertEqual(self.c.post('/servers', data=self.payload).status_code, 302)
        self.assertEqual(app.configured_hosts()['10.0.2.99']['port'], 2222)
        self.assertEqual(self.c.post('/servers', data=self.payload).status_code, 400)
        self.assertEqual(self.c.post('/servers', data={**self.payload, 'editing':'10.0.2.99', 'name':'Updated'}).status_code, 302)
        app.init_db()
        self.assertEqual(app.configured_hosts()['10.0.2.99']['name'], 'Updated')
        self.assertEqual(len(app.configured_hosts()), 3)

    def test_validation_and_security(self):
        self.assertEqual(self.c.post('/servers',data={**self.payload,'csrf':'bad'}).status_code,403)
        self.assertEqual(self.c.post('/servers',data={**self.payload,'host':'invalid'}).status_code,400)
        self.assertEqual(self.c.post('/servers',data={**self.payload,'port':'0'}).status_code,400)
        self.assertEqual(app.app.test_client().get('/servers').status_code,302)
        with self.assertRaises(app.paramiko.SSHException):
            app.VerifiedHostKey('SHA256:incorrect').missing_host_key(None,'test',app.paramiko.RSAKey.generate(1024))

    def test_password_storage_and_non_disclosure(self):
        payload = {**self.payload, 'auth_method':'password', 'ssh_password':'unit-test-secret-42'}
        self.assertEqual(self.c.post('/servers', data=payload).status_code, 302)
        with app.connect_db() as db:
            encrypted = db.execute('SELECT password_encrypted FROM servers WHERE host=?', (payload['host'],)).fetchone()[0]
        self.assertNotIn(payload['ssh_password'], encrypted)
        self.assertEqual(app.password_cipher().decrypt(encrypted.encode()).decode(), payload['ssh_password'])
        for path in ['/servers?edit=10.0.2.99','/api/status']:
            html = self.c.get(path).get_data(as_text=True)
            self.assertNotIn(payload['ssh_password'], html)
            self.assertNotIn(encrypted, html)
        payload.update(editing=payload['host'], ssh_password='')
        self.assertEqual(self.c.post('/servers', data=payload).status_code, 302)
        self.assertTrue(app.configured_hosts()[payload['host']]['has_password'])

    def test_pages(self):
        for path in ['/', '/servers', '/servers?edit=10.0.2.14', '/api/status']:
            self.assertEqual(self.c.get(path).status_code,200,path)

    def test_database_only_backup_protocol(self):
        client = MagicMock()
        stdout = MagicMock()
        stderr = MagicMock()
        stderr.read.return_value = b''
        stdout.channel.recv_exit_status.return_value = 0
        client.exec_command.return_value = (None, stdout, stderr)
        with patch.object(app, 'ssh_connection', return_value=client):
            stdout.read.return_value = b'SUCCESS|/backup/test|1234\n'
            self.assertEqual(app.perform('10.0.2.14', 'test'), 'success')
            command = client.exec_command.call_args.args[0]
            self.assertIn('HBC_DB_ONLY_V1', command)
            self.assertIn('--db-only', command)
            stdout.read.return_value = b'SUCCESS|/backup/legacy|1234|5678\n'
            self.assertEqual(app.perform('10.0.2.14', 'test'), 'failed')
        with app.connect_db() as db:
            job = db.execute("SELECT * FROM jobs WHERE status='success'").fetchone()
            self.assertEqual(job['sql_bytes'], 1234)
            self.assertIsNone(job['web_bytes'])
        html = self.c.get('/').get_data(as_text=True)
        self.assertNotIn('/var/www/html', html)
        self.assertNotIn('เว็บไซต์', html)

if __name__ == '__main__':
    unittest.main()
