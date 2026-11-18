// Read-only SQL integration in an isolated fixture. Never writes patient data to artifacts.
const fs=require('fs'),path=require('path'),crypto=require('crypto'),net=require('net');
const {spawn}=require('child_process');
const root=path.resolve(__dirname,'..'),parent=path.join(root,'.private');
const fixture=fs.mkdtempSync(path.join(parent,'verify-report-api-'));const guard=crypto.randomBytes(32).toString('hex');
const php=process.env.PHP_BINARY||'C:\\xampp\\php\\php.exe';let server;
const assert=(ok,label)=>{if(!ok)throw Error(label);console.log('OK '+label)};
const quoted=s=>"'"+s.replaceAll('\\','\\\\').replaceAll("'","\\'")+"'";
(async()=>{
 fs.mkdirSync(path.join(fixture,'.private'),{recursive:true});fs.mkdirSync(path.join(fixture,'database'));
 for(const file of ['api.php','report.php','report-fields.php','report-labs.php','field-options.php','family.php','manual-store.php','export-report.php','branding.php'])fs.copyFileSync(path.join(root,file),path.join(fixture,file));
 fs.copyFileSync(path.join(root,'database/sheet-options-source.json'),path.join(fixture,'database/sheet-options-source.json'));
 for(const file of ['config.php','supplement.config.php','branding.config.php'])fs.writeFileSync(path.join(fixture,file),'<?php return require '+quoted(path.join(root,file))+';');
 fs.writeFileSync(path.join(fixture,'security.php'),`<?php if(!hash_equals('${guard}',$_SERVER['HTTP_X_TEST_GUARD']??'')){http_response_code(403);exit;} $cfg=require __DIR__.'/config.php'; $_SESSION=['auth_user'=>'isolated-read-only-test','csrf'=>'fixture-csrf'];function usageLog($event,$details=[]){return true;}`);
 fs.writeFileSync(path.join(fixture,'expected.php'),`<?php require __DIR__.'/security.php';require __DIR__.'/report.php';$pdo=reportConnection($cfg);$pdo->exec('SET SESSION TRANSACTION READ ONLY');$count=$pdo->query('SELECT COUNT(*) FROM ipt i LEFT JOIN ipt_labour l ON l.an=i.an LEFT JOIN ipt_labour_infant f ON f.ipt_labour_id=l.ipt_labour_id WHERE '.reportDeliveryAdmissionWhere())->fetchColumn();header('Content-Type: application/json');echo json_encode(['count'=>(int)$count,'fields'=>count(reportFields())]);`);
 const probe=net.createServer();await new Promise(r=>probe.listen(0,'127.0.0.1',r));const port=probe.address().port;await new Promise(r=>probe.close(r));
 server=spawn(php,['-n','-d','extension_dir=C:\\xampp\\php\\ext','-d','extension=pdo_mysql','-d','memory_limit=128M','-d','max_execution_time=120','-S','127.0.0.1:'+port,'-t',fixture],{cwd:fixture,windowsHide:true,stdio:['ignore','ignore','pipe']});let errors='';server.stderr.on('data',d=>errors+=d.toString());
 const base='http://127.0.0.1:'+port;
 const req=(url,body)=>fetch(base+url,{headers:{'X-Test-Guard':guard},method:body?'POST':'GET',body:body?new URLSearchParams(body):undefined});
 for(let i=0;i<50;i++){try{await req('/expected.php');break}catch(e){if(i===49)throw Error('SQL fixture server failed');await new Promise(r=>setTimeout(r,100))}}
 const expected=await (await req('/expected.php')).json();assert(expected.count>0,'Read-only independent registry count available ('+expected.count+' rows)' );
 const all='/api.php?output=xlsx&all=1&from=2026-11-01&to=2026-11-17';
 assert((await req(all)).status===405,'XLSX GET rejected');
 assert((await req(all,{csrf:'bad'})).status===403,'XLSX requires CSRF');
 const period=await (await req('/api.php?from=2026-11-01&to=2026-11-17')).json();assert(period.ok&&period.data.main.length>0,'Existing period API still reads all data groups');
 let res=await req(all,{csrf:'fixture-csrf'});if(!res.ok)throw Error('XLSX API failed (status '+res.status+'); '+errors.split('\n').filter(l=>/LR API|Fatal|Warning/.test(l)).join('\n'));if(!res.headers.get('content-type').includes('spreadsheetml')){const t=await res.text();throw Error('XLSX unexpected response: '+res.headers.get('content-type')+'; fatal='+/Fatal error/.test(t)+'; memory='+/Allowed memory/.test(t)+'; timeout='+/Maximum execution/.test(t)+'; serverFatal='+/PHP Fatal/.test(errors)+'; warnings='+JSON.stringify([...t.matchAll(/<b>(Warning|Notice)<\/b>:\s*(.*?) in <b>/gs)].map(m=>m[2]))+'; warning='+/Warning/.test(t)+'; notice='+/Notice/.test(t)+'; deprecated='+/Deprecated/.test(t)+'; zipSignature='+t.startsWith('PK')+'; bytes='+t.length+'; apiError='+JSON.stringify(errors.split('\n').filter(l=>l.includes('[LR API]')).map(l=>l.slice(-250)))+'; responseShape='+JSON.stringify((()=>{try{const j=JSON.parse(t);return {ok:j.ok,error:j.error,groups:Object.keys(j.data||{})}}catch{return {parse:false}}})()))}console.log('OK Native XLSX response type');
 const bytes=Buffer.from(await res.arrayBuffer());let offset=0,parts={};
 while(offset+30<bytes.length&&bytes.readUInt32LE(offset)===0x04034b50){const method=bytes.readUInt16LE(offset+8),size=bytes.readUInt32LE(offset+18),length=bytes.readUInt16LE(offset+26),extra=bytes.readUInt16LE(offset+28);if(method!==0)throw Error('Unsupported XLSX ZIP method');const name=bytes.toString('utf8',offset+30,offset+30+length);const start=offset+30+length+extra;parts[name]=bytes.toString('utf8',start,start+size);offset=start+size;}
 const sheet=parts['xl/worksheets/sheet1.xml'];const exported=(sheet.match(/<row r=/g)||[]).length-1;
 assert(exported===expected.count,'All-time XLSX count matches independent SQL count ('+exported+' rows; no patient values logged)');
 const headers=sheet.slice(sheet.indexOf('<row '),sheet.indexOf('</row>'));assert((headers.match(/<c /g)||[]).length===expected.fields,'XLSX contains every configured field ('+expected.fields+' columns)');
 assert(Object.keys(parts).filter(k=>k.startsWith('xl/worksheets/')).length===10,'All ten data and metadata sheets present');

 const unzip=bytes=>{const parts={};let off=0;while(off+30<bytes.length&&bytes.readUInt32LE(off)===0x04034b50){const size=bytes.readUInt32LE(off+18),len=bytes.readUInt16LE(off+26),extra=bytes.readUInt16LE(off+28),start=off+30+len+extra;parts[bytes.toString('utf8',off+30,off+30+len)]=bytes.toString('utf8',start,start+size);off=start+size;}return parts;};
 const parseCsv=text=>{text=text.replace(/^\uFEFF/,'');let rows=[],row=[],cell='',quoted=false;for(let i=0;i<text.length;i++){const c=text[i];if(c==='"'){if(quoted&&text[i+1]==='"'){cell+='"';i++;}else quoted=!quoted;}else if(c===','&&!quoted){row.push(cell);cell='';}else if(c==='\n'&&!quoted){row.push(cell.replace(/\r$/,''));rows.push(row);row=[];cell='';}else cell+=c;}assert(!quoted&&cell===''&&row.length===0,'CSV terminated and balanced');return rows;};
 for(const output of ['csv','csvzip']){
   const url='/api.php?output='+output+'&all=1';
   assert((await req(url)).status===405,output+' GET rejected');assert((await req(url,{csrf:'bad'})).status===403,output+' CSRF enforced');
   const response=await req(url,{csrf:'fixture-csrf'});assert(response.ok,output+' all-time download succeeds');
   const buf=Buffer.from(await response.arrayBuffer());
   const files=output==='csv'?{'01.csv':buf.toString('utf8')}:unzip(buf);
   assert(Object.keys(files).length===(output==='csv'?1:10),output+' correct file count');
   for(const [i,csv] of Object.values(files).entries()){
     assert(csv.startsWith('\uFEFF'),output+' UTF-8 BOM '+(i+1));const parsed=parseCsv(csv);
     assert(parsed.every(row=>row.length===parsed[0].length),output+' consistent column counts '+(i+1));
     const xml=parts['xl/worksheets/sheet'+(i+1)+'.xml'];
     assert(parsed.length===(xml.match(/<row r=/g)||[]).length,output+' row count matches XLSX sheet '+(i+1));
     if(i!==9){
       const xmlRows=[...xml.matchAll(/<row\b[^>]*>(.*?)<\/row>/gs)];
       const decode=t=>t.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&apos;/g,"'").replace(/&amp;/g,'&');
       for(const n of [...new Set([0,1,Math.floor(parsed.length/2),parsed.length-1])].filter(n=>n<parsed.length)){
         const values=[...xmlRows[n][1].matchAll(/<t\b[^>]*>(.*?)<\/t>/gs)].map(m=>decode(m[1]));
         assert(values.length===parsed[n].length&&values.every((v,c)=>parsed[n][c]===((/^[\s\uFEFF]*[=+@-]|^[\t\r\n]/u.test(v)?"'":'')+v)),output+' representative values equal XLSX sheet '+(i+1)+' row '+n);
       }
     }

   }
 }
 for(const output of ['xlsx','csv','csvzip']){
   const response=await req('/api.php?output='+output+'&from=2026-11-01&to=2026-11-17',{csrf:'fixture-csrf'});
   assert(response.ok,output+' period download succeeds');const buf=Buffer.from(await response.arrayBuffer());
   const count=output==='xlsx'?(unzip(buf)['xl/worksheets/sheet1.xml'].match(/<row r=/g)||[]).length-1:parseCsv(output==='csv'?buf.toString('utf8'):unzip(buf)['01.csv']).length-1;
   assert(count===period.data.main.length,output+' period rows match registry');
 }
 const an=period.data.main[0]['AN มารดา'];res=await req('/api.php?output=print&an='+encodeURIComponent(an),{csrf:'fixture-csrf'});const html=await res.text();assert(res.ok&&html.includes('แบบสรุปเวชระเบียนรายบุคคล')&&html.includes('Lab ทะเบียนคลอด'),'Individual print reads complete patient data');
 assert((await req('/api.php?output=print',{csrf:'fixture-csrf'})).status===400,'Print requires patient identity');
 assert(fs.readdirSync(path.join(fixture,'.private/exports')).length===0,'Patient export temporary files deleted after download');
 assert(!errors.includes('PHP Fatal')&&!errors.includes('PHP Warning'),'SQL integration has no PHP runtime errors');
})().catch(e=>{console.error(e.message);process.exitCode=1}).finally(async()=>{
 if(server){server.kill();await new Promise(r=>server.once('exit',r));}
 const resolved=path.resolve(fixture);if(!resolved.startsWith(path.resolve(parent)+path.sep+'verify-report-api-'))throw Error('Unsafe fixture cleanup');fs.rmSync(resolved,{recursive:true,force:true});
});
