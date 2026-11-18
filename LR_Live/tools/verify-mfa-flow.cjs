// Isolated synthetic account only. No connection to HOSxP or production MFA files.
const fs=require('fs'),path=require('path'),crypto=require('crypto'),os=require('os');
const {spawn,spawnSync}=require('child_process');
const root=path.resolve(__dirname,'..'),parent=path.join(root,'.private');
const fixture=fs.mkdtempSync(path.join(parent,'verify-mfa-flow-'));
const php=process.env.PHP_BINARY||'C:\\xampp\\php\\php.exe';
const assert=(ok,label)=>{if(!ok)throw Error(label);console.log('OK '+label)};
const phpArgs=['-n','-d','extension_dir=C:\\xampp\\php\\ext','-d','extension=openssl'];
let server,browser;
function codeFor(secret){let bits='',bytes=[];for(const c of secret)bits+='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'.indexOf(c).toString(2).padStart(5,'0');for(let i=0;i+8<=bits.length;i+=8)bytes.push(parseInt(bits.slice(i,i+8),2));const counter=Buffer.alloc(8);counter.writeBigUInt64BE(BigInt(Math.floor(Date.now()/30000)));const h=crypto.createHmac('sha1',Buffer.from(bytes)).update(counter).digest();const offset=h[19]&15;return String((h.readUInt32BE(offset)&0x7fffffff)%1000000).padStart(6,'0')}
(async()=>{
 fs.mkdirSync(path.join(fixture,'.private'),{recursive:true});fs.mkdirSync(path.join(fixture,'sessions'));fs.mkdirSync(path.join(fixture,'assets/vendor'),{recursive:true});fs.copyFileSync(path.join(root,'assets/vendor/qrcode.js'),path.join(fixture,'assets/vendor/qrcode.js'));fs.mkdirSync(path.join(fixture,'tools'));
 for(const file of ['auth-bootstrap.php','login.php','mfa.php','mfa-store.php','login-throttle.php','usage-log.php','security.php','logout.php'])fs.copyFileSync(path.join(root,file),path.join(fixture,file));
 fs.copyFileSync(path.join(root,'tools/reset-mfa.php'),path.join(fixture,'tools/reset-mfa.php'));
 fs.writeFileSync(path.join(fixture,'config.php'),'<?php return [];');
 fs.writeFileSync(path.join(fixture,'hosxp-auth.php'),`<?php function authenticateHosxpNurse($cfg,$user,$password){return $user==='fixture-user'&&$password==='synthetic-password';} function hosxpSessionAllowed($cfg,$user){return $user==='fixture-user';}`);
 fs.writeFileSync(path.join(fixture,'index.php'),`<?php require __DIR__.'/security.php'; echo 'SYNTHETIC PROTECTED PAGE';`);
 fs.writeFileSync(path.join(fixture,'api.php'),`<?php require __DIR__.'/security.php'; header('Content-Type: application/json'); echo '{"ok":true}';`);
 const net=require('net');const probe=net.createServer();await new Promise(r=>probe.listen(0,'127.0.0.1',r));const port=probe.address().port;await new Promise(r=>probe.close(r));
 server=spawn(php,[...phpArgs,'-d','session.save_path='+path.join(fixture,'sessions'),'-S','127.0.0.1:'+port,'-t',fixture],{cwd:fixture,windowsHide:true,stdio:['ignore','ignore','pipe']});let errors='';server.stderr.on('data',d=>errors+=d.toString());
 const base='http://127.0.0.1:'+port;let cookie='';
 async function req(url,body){const res=await fetch(base+url,{redirect:'manual',method:body?'POST':'GET',headers:cookie?{Cookie:cookie}:{},body:body?new URLSearchParams(body):undefined});const set=res.headers.get('set-cookie');if(set)cookie=set.split(';')[0];return {status:res.status,location:res.headers.get('location'),text:await res.text()}}
 for(let i=0;i<50;i++){try{await req('/login.php');break}catch(e){if(i===49)throw Error('Fixture server failed: '+errors);await new Promise(r=>setTimeout(r,100))}}
 const csrf=html=>{const m=html.match(/name="csrf" value="([a-f0-9]+)"/);if(!m)throw Error('No CSRF token');return m[1]};
 async function passwordLogin(){const page=await req('/login.php');const login=await req('/login.php',{csrf:csrf(page.text),username:'fixture-user',password:'synthetic-password'});assert(login.status===303&&login.location==='mfa.php','Password verification requires MFA');return req('/mfa.php')}
 assert((await req('/api.php')).status===401,'Anonymous API access denied');
 let page=await passwordLogin();assert(page.text.includes('ตั้งค่า MFA ครั้งแรก'),'First login enrollment');
 assert((await req('/index.php')).location==='login.php','Password-only session cannot read patient pages');
 assert((await req('/api.php')).status===401,'Password-only session cannot read patient API');
 const seed=page.text.match(/<code>([A-Z2-7]{32})<\/code>/)?.[1];assert(!!seed,'Authenticator setup key rendered');
 assert((await req('/mfa.php',{csrf:'bad',code:'000000'})).status===403,'MFA requires CSRF');
 const consumedOtp=codeFor(seed);page=await req('/mfa.php',{csrf:csrf(page.text),code:consumedOtp});assert(page.status===200&&page.text.includes('ตั้งค่า MFA สำเร็จ'),'Enrollment verifies real TOTP');
 const recovery=[...page.text.matchAll(/<code>([A-F0-9]{16})<\/code>/g)].map(m=>m[1]);assert(recovery.length===10,'Ten recovery codes displayed once');
 assert((await req('/index.php')).text==='SYNTHETIC PROTECTED PAGE','Full MFA session can read protected page');
 async function logout(){const sessFiles=fs.readdirSync(path.join(fixture,'sessions'));let token;for(const name of sessFiles){const text=fs.readFileSync(path.join(fixture,'sessions',name),'utf8');const m=text.match(/csrf\|s:64:"([a-f0-9]+)"/);if(text.includes('auth_user|')&&m)token=m[1]}assert(!!token,'Authenticated session CSRF exists');assert((await req('/logout.php',{csrf:token})).status===303,'Logout succeeds')}
 await logout();page=await passwordLogin();assert(!page.text.includes('<code>'+seed),'Enrolled secret is hidden on later logins');
 assert((await req('/mfa.php',{csrf:csrf(page.text),code:consumedOtp})).status===401,'Previously consumed TOTP cannot authenticate twice');
 assert((await req('/mfa.php',{csrf:csrf(page.text),code:recovery[0]})).status===303,'Recovery code authenticates');
 await logout();page=await passwordLogin();assert((await req('/mfa.php',{csrf:csrf(page.text),code:recovery[0]})).status===401,'Consumed recovery code rejected');
 assert((await req('/mfa.php',{csrf:csrf(page.text),code:recovery[1]})).status===303,'Unused recovery code remains usable');
 const reset=spawnSync(php,[...phpArgs,path.join(fixture,'tools/reset-mfa.php'),'--user=fixture-user','--confirm=fixture-user'],{encoding:'utf8',windowsHide:true});assert(reset.status===0,'Authorized CLI MFA reset');
 assert((await req('/api.php')).status===401,'MFA reset revokes existing sessions');
 page=await passwordLogin();assert(page.text.includes('ตั้งค่า MFA ครั้งแรก'),'Reset requires enrollment again');
 for(let i=0;i<5;i++)await req('/mfa.php',{csrf:csrf(page.text),code:'not-an-otp'});
 assert((await req('/mfa.php',{csrf:csrf(page.text),code:'not-an-otp'})).status===429,'Repeated MFA attempts rate limited');
 // Optional visual verification with an already-installed Playwright library.
 if(process.env.PLAYWRIGHT_MODULE){
  const {chromium}=require(process.env.PLAYWRIGHT_MODULE);browser=await chromium.launch({headless:true,...(process.env.PLAYWRIGHT_CHROMIUM?{executablePath:process.env.PLAYWRIGHT_CHROMIUM}:{})});const p=await browser.newPage({viewport:{width:1200,height:900}});
  await p.goto(base+'/login.php');await p.locator('#username').fill('fixture-user');await p.locator('#password').fill('synthetic-password');await p.locator('.submit').click();await p.waitForURL('**/mfa.php');await p.locator('#mfa-qr svg').waitFor();assert(await p.locator('#mfa-qr svg path').count()>0,'Local MFA QR rendered');assert((await p.locator('#mfa-qr-status').textContent()).includes('สแกนด้วย'),'QR setup instructions displayed');
  const output=path.join(root,'output/playwright/mfa-export');fs.mkdirSync(output,{recursive:true});await p.screenshot({path:path.join(output,'mfa-setup.png'),fullPage:true});
  await p.goto('file:///'+path.join(output,'print.html').replaceAll('\\','/'));await p.screenshot({path:path.join(output,'patient-print.png'),fullPage:true});await p.emulateMedia({media:'print'});assert(await p.locator('.toolbar').evaluate(el=>getComputedStyle(el).display)==='none','Print controls hidden on paper');
  assert(await p.locator('.field').count()>100,'Printed form includes hidden and visible fields');
  await browser.close();browser=null;
 }
 assert(!errors.includes('PHP Fatal')&&!errors.includes('PHP Warning'),'MFA flow has no PHP runtime errors');
})().catch(e=>{console.error(e.message);process.exitCode=1}).finally(async()=>{
 if(browser)await browser.close();if(server){server.kill();await new Promise(r=>server.once('exit',r));}
 const resolved=path.resolve(fixture);if(!resolved.startsWith(path.resolve(parent)+path.sep+'verify-mfa-flow-'))throw Error('Unsafe fixture cleanup');fs.rmSync(resolved,{recursive:true,force:true});
});
