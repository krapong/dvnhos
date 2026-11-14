const THAI_MONTH_NAMES=['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
function todayThaiISO(now=new Date()) {
  const parts=new Intl.DateTimeFormat('en-US-u-ca-gregory-nu-latn',{timeZone:'Asia/Bangkok',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(now);
  const part=type=>parts.find(item=>item.type===type).value;
  return `${part('year')}-${part('month')}-${part('day')}`;
}
function thaiDate(value) {
  if(!/^\d{4}-\d{2}-\d{2}$/.test(String(value)))return String(value??'');
  const date=new Date(value+'T00:00:00Z');
  return Number.isFinite(date.getTime())&&date.toISOString().slice(0,10)===value?new Intl.DateTimeFormat('th-TH-u-ca-buddhist',{day:'numeric',month:'short',year:'numeric',timeZone:'Asia/Bangkok'}).format(date):'—';
}
function thaiDateField(id,value) {
  const [year,month,day]=value.split('-').map(Number);
  const options=(values,selected)=>values.map(([v,label])=>`<option value="${v}" ${v===selected?'selected':''}>${label}</option>`).join('');
  const days=new Date(Date.UTC(year,month,0)).getUTCDate();
  return `<span class="thai-date-field" data-date-field="${id}"><input type="hidden" id="${id}" value="${value}"><select data-part="day" aria-label="วัน">${options(Array.from({length:days},(_,i)=>[i+1,i+1]),day)}</select><select data-part="month" aria-label="เดือน">${options(THAI_MONTH_NAMES.map((name,i)=>[i+1,name]),month)}</select><select data-part="year" aria-label="ปี พ.ศ.">${options(Array.from({length:Math.max(2100,year)-Math.min(1900,year)+1},(_,i)=>{const y=Math.min(1900,year)+i;return [y,y+543];}),year)}</select><small>พ.ศ.</small></span>`;
}
function syncThaiDateField(id) {
  const input=document.getElementById(id),wrapper=input?.closest('[data-date-field]');
  if(!wrapper)return;
  const [year,month,day]=input.value.split('-').map(Number);
  wrapper.querySelector('[data-part="year"]').value=year;
  wrapper.querySelector('[data-part="month"]').value=month;
  const select=wrapper.querySelector('[data-part="day"]');
  select.innerHTML=Array.from({length:new Date(Date.UTC(year,month,0)).getUTCDate()},(_,i)=>`<option value="${i+1}">${i+1}</option>`).join('');select.value=day;
}
document.addEventListener('change',event=>{
  if(!event.target.matches?.('[data-part]'))return;
  const wrapper=event.target.closest('[data-date-field]');if(!wrapper)return;
  const year=Number(wrapper.querySelector('[data-part="year"]').value),month=Number(wrapper.querySelector('[data-part="month"]').value);
  const day=Math.min(Number(wrapper.querySelector('[data-part="day"]').value),new Date(Date.UTC(year,month,0)).getUTCDate());
  const id=wrapper.dataset.dateField;
  document.getElementById(id).value=`${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
  syncThaiDateField(id);document.getElementById(id).dispatchEvent(new Event('change',{bubbles:true}));
});
