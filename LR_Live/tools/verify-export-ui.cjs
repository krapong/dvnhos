// Verify browser download routing without patient values or a real browser session.
const fs=require('fs'),vm=require('vm'),assert=require('assert/strict');
const source=fs.readFileSync(require('path').join(__dirname,'../index.php'),'utf8');
const code=source.slice(source.indexOf('function reportOutputUrl('),source.indexOf('let liveRequestId='));
let csv='',download='',requested,usage=0;
const inputs={from:{value:'2026-11-01'},to:{value:'2026-11-17'},'export-format':{value:'csv'},'registry-search-status':{textContent:''}};
const buttons=[{disabled:false},{disabled:false}];
const context={window:{},URLSearchParams,csrfToken:'fixture',FIELD_CATALOG:[{label:'HN'},{label:'เวลา'},{label:'ค่าว่าง'}],
 document:{getElementById:id=>inputs[id],querySelectorAll:()=>buttons,createElement:()=>({set download(v){download=v},click(){}})},
 registryRows:()=>[{HN:'0001',เวลา:'09:30:15'}],recordUsage:async()=>{usage++;return true},
 Blob:class{constructor(parts){csv=parts[0]}},URL:{createObjectURL:()=>'',revokeObjectURL:()=>{}},setTimeout:()=>{},alert:m=>{throw Error(m)},
 fetch:async(url,options)=>{requested={url,options};return {ok:true,headers:{get:()=>url.includes('csvzip')?'application/zip':url.includes('xlsx')?'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'text/csv; charset=UTF-8'},blob:async()=>({})}}
};
vm.createContext(context);vm.runInContext(code,context);
(async()=>{
 await context.exportCSV();assert(csv.includes('0001'));assert(csv.includes('09:30:15'));assert(csv.includes('ค่าว่าง'));assert.equal(usage,1);
 assert.equal(context.csvCell('=1+1'),'"\'=1+1"');
 for(const format of ['xlsx','csv','csvzip'])for(const all of [false,true]){
   await context.exportSelected(all,format);const query=new URL(requested.url,'http://fixture').searchParams;
   assert.equal(query.get('output'),format);assert.equal(query.get('all'),all?'1':null);
   assert.equal(requested.options.method,'POST');assert.equal(requested.options.body.get('csrf'),'fixture');
   assert(download.endsWith('.'+(format==='csvzip'?'zip':format)));assert(buttons.every(b=>!b.disabled));
 }
 context.fetch=async()=>({ok:false,headers:{get:()=> 'application/json'},json:async()=>({error:'fixture error'})});
 context.alert=()=>{};await context.exportSelected(true,'csv');assert.equal(inputs['registry-search-status'].textContent,'fixture error');assert(buttons.every(b=>!b.disabled));
 console.log('OK browser CSV raw values, all six download routes, CSRF, extensions and failure recovery');
})().catch(e=>{console.error(e);process.exitCode=1});
