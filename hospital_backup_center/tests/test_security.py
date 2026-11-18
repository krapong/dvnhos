import json
import re
import time
from unittest.mock import patch
import pyotp
from werkzeug.security import generate_password_hash
from test_servers import ServerTests
import app

class SecurityTests(ServerTests):
    def setUp(self):
        super().setUp()
        self.password='security-test-password'
        self.hashpatch=patch.object(app,'PASSWORD_HASH',generate_password_hash(self.password))
        self.hashpatch.start()
    def tearDown(self):
        self.hashpatch.stop();super().tearDown()
    def csrf(self,client):
        with client.session_transaction() as s:return s['csrf']
    def enroll(self):
        r=self.c.post('/security/mfa',data={'csrf':self.csrf(self.c),'current_password':self.password})
        self.assertEqual(r.status_code,302)
        current=app.security_state()
        secret=app.password_cipher().decrypt(current['pending_encrypted'].encode()).decode()
        self.assertNotIn(secret,current['pending_encrypted'])
        r=self.c.post('/security/mfa/confirm',data={'csrf':self.csrf(self.c),'code':pyotp.TOTP(secret).now()})
        self.assertEqual(r.status_code,200)
        codes=re.findall(r'<code>([a-f0-9]{16})</code>',r.get_data(as_text=True))
        self.assertEqual(len(codes),10)
        self.assertTrue(app.security_state()['enabled'])
        return secret,codes
    def password_login(self,client):
        client.get('/login')
        return client.post('/login',data={'csrf':self.csrf(client),'password':self.password})

    def test_mfa_setup_requires_password_and_confirmation(self):
        self.c.post('/security/mfa',data={'csrf':'test','current_password':'wrong'})
        self.assertEqual(app.security_state()['pending_encrypted'],'')
        self.c.post('/security/mfa',data={'csrf':'test','current_password':self.password})
        self.assertFalse(app.security_state()['enabled'])
        r=self.c.get('/security/mfa')
        self.assertEqual(r.status_code,200)
        self.assertIn('<svg',r.get_data(as_text=True))
        self.assertEqual(r.headers['Cache-Control'],'no-store')
        with self.c.session_transaction() as s:
            self.assertNotIn('secret',s)
        self.assertEqual(self.c.post('/security/mfa/confirm',data={}).status_code,403)

    def test_login_factor_replay_and_single_use_recovery(self):
        secret,codes=self.enroll()
        client=app.app.test_client()
        r=self.password_login(client)
        self.assertIn('/login/mfa',r.location)
        self.assertEqual(client.get('/').status_code,302)
        r=client.post('/login/mfa',data={'csrf':self.csrf(client),'code':pyotp.TOTP(secret).now()})
        self.assertEqual(r.status_code,200) # enrollment consumed this counter
        r=client.post('/login/mfa',data={'csrf':self.csrf(client),'code':codes[0]})
        self.assertEqual(r.status_code,302)
        self.assertEqual(client.get('/settings').status_code,200)
        client.post('/logout',data={'csrf':self.csrf(client)})
        self.password_login(client)
        r=client.post('/login/mfa',data={'csrf':self.csrf(client),'code':codes[0]})
        self.assertEqual(r.status_code,200)
        self.assertEqual(client.get('/settings').status_code,302)
        # A new counter within the allowed clock window succeeds, once.
        code=pyotp.TOTP(secret).at(time.time()+30)
        r=client.post('/login/mfa',data={'csrf':self.csrf(client),'code':code})
        self.assertEqual(r.status_code,302)
        self.assertEqual(client.get('/settings').status_code,200)

    def test_other_sessions_invalidated_and_disable_protected(self):
        other=app.app.test_client()
        with other.session_transaction() as s:s.update(authed=True,csrf='old')
        secret,codes=self.enroll()
        self.assertEqual(other.get('/settings').status_code,302)
        r=self.c.post('/security/mfa/disable',data={'csrf':self.csrf(self.c),'current_password':'wrong','code':codes[0]})
        self.assertTrue(app.security_state()['enabled'])
        self.c.post('/security/mfa/disable',data={'csrf':self.csrf(self.c),'current_password':self.password,'code':codes[0]})
        self.assertFalse(app.security_state()['enabled'])

    def test_audit_preserved_and_contains_no_credentials(self):
        with app.connect_db() as db:
            db.execute("INSERT INTO audit_log(occurred_at,actor,ip,event,endpoint,method,status,detail) VALUES ('2026-01-01T00:00:00+00:00','admin','','old.event','','GET',200,'{}')")
        self.c.post('/login',data={'csrf':'test','password':'do-not-log-this-password'})
        r=self.c.get('/audit')
        self.assertEqual(r.status_code,200)
        self.assertIn('old.event',r.get_data(as_text=True))
        r=self.c.get('/audit/export')
        self.assertEqual(r.status_code,200)
        self.assertNotIn('do-not-log-this-password',r.get_data(as_text=True))
        self.assertIn('login.failed',r.get_data(as_text=True))
        self.assertEqual(app.app.test_client().get('/audit').status_code,302)

    def test_login_rate_limit_persistent(self):
        client=app.app.test_client();client.get('/login')
        for _ in range(8):
            client.post('/login',data={'csrf':self.csrf(client),'password':'wrong'})
        r=client.post('/login',data={'csrf':self.csrf(client),'password':self.password})
        self.assertEqual(r.status_code,200)
        self.assertEqual(client.get('/settings').status_code,302)
