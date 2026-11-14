/* Daily and cumulative values transcribed from the seven user-supplied reports. */
const SOURCE_REPORTS = {
  opd: {
    date:'3 พ.ย. 2569', period:'1–31 ก.ค. 69 (ตามหัวตารางในภาพ)', riskPeriod:'1–30 พ.ย. 69', productivity:'157.49',
    services:[['ผู้มารับบริการ','163','339'],['ADMIT','2','8'],['REFER','27','52'],['วัด VA','2','6'],['PT แยกโซน','0','2'],['จองคิว (CT, Echo, MRI, U/S, EGD)','1','2'],['Consult case','2','3']],
    risks:[['เกิดภาวะวิกฤติขณะรอตรวจ','0','0'],['เกิดอาการเปลี่ยนแปลงขณะรอตรวจ','0','0'],['Triage ผิด','0','2'],['ระบุตัวผู้ป่วยผิดคน (OPD card/ซักประวัติ/ส่งตรวจรักษา)','0','0'],['รายงานแลปผิดคน','0','0'],['พลัดตกหกล้ม','0','0'],['ร้องเรียนพฤติกรรมบริการ','0','0']],
    staff:[['พยาบาล (รวมหลังตรวจ)','5'],['แพทย์ OPD','5'],['แพทย์ ER','1'],['แพทย์ NCD','1'],['แพทย์ออก PCU','0'],['แพทย์ลา / ประชุม','0']],
    note:'วันที่รายงานและช่วงสะสมในหัวตารางต่างกัน จึงแสดงตามต้นฉบับ'
  },
  lr: {
    date:'3 พ.ย. 2569',shift:'เวรบ่าย 16.00–00.00 น.',period:'1–30 พ.ย. 69',productivity:'91.84',
    services:[['ผู้รับบริการทั้งหมด (คลอด/PV/PAP smear/NST/U/S/แจ้งเกิด/ดูแลหลังคลอด ฯลฯ)','2','16'],['ผู้รับบริการคลอด','2','11'],['รอคลอด','1','6'],['คลอด N/D (Term)','1','4'],['คลอด Preterm','0','0'],['คลอด V/E','0','0'],['คลอดท่า Breech','0','0'],['คลอด BBA','0','0'],['Birth Asphyxia','0','0'],['Refer มารดา','0','1'],['PPH / PIH','0/0','0/0'],['CPD / Macrosomia / failed induction','0/0/0','0/0/0'],['Previous C/S / Ectopic preg','0/0','0/0'],['Breech presentation','0','0'],['Fetal distress','0','0'],['Preterm labor','0','0'],['Refer ทารก','0','0']],
    risks:[['Induction','0','0'],['Stop labor (นับเพิ่มเฉพาะรายใหม่)','0','0'],['ขูดมดลูก','0','0'],['ล้วงรก','0','0'],['Re-suture แผลฝีเย็บ','0','0']],
    admissions:[['LR','1','6'],['Ward','0','4']],note:'แสดงรายการที่มองเห็นในภาพ ส่วนล่างของต้นฉบับถูกตัด'
  },
  ht: {
    date:'22 ต.ค. 2569',period:'1 ต.ค. 69–ปัจจุบัน',riskPeriod:'1 ต.ค. 68–ปัจจุบัน (ตามภาพ)',
    services:[['ผู้มารับบริการ','120','561'],['Telemed','0','0'],['ADMIT','0','0'],['OBS.BP / วัด VA','0/0','8/0'],['ส่งตรวจ ER','0','1'],['ลงข้อมูลตรวจแลป รพ.สต.','-','-'],['ช่วยแพทย์ตรวจ','24','129']],
    risks:[['Hypertension Urgency','1','8(1)'],['Hyperkalemia','0','0(0)'],['Tachycardia','0','0(0)'],['Bradycardia','0','0(0)'],['AKI','0','1'],['พ่นยา','0','0(0)'],['ระบุตัวผู้ป่วยผิดคน','0','0'],['รายงานแลปผิดคน','0','0'],['พลัดตกหกล้ม','0','0'],['ร้องเรียนพฤติกรรมบริการ','0','0']]
  },
  asthma: {
    date:'30 ต.ค. 2569',period:'1 ต.ค. 69–ปัจจุบัน',
    services:[['ผู้มารับบริการ (Asthma/COPD)','40 (24/16)','173 (56/77)'],['ADMIT','0','0(0)'],['พ่นยา','0','7'],['Obs. BP / Consult แพทย์','0/0','0/0'],['คัดกรองตาเท้าเบาหวาน','0','0'],['ติดตามผู้ป่วยผิดนัด','0','0'],['ฉีด Vaccine ไข้หวัดใหญ่','7','17'],['ฉีดยา IV','0','2'],['ส่ง ER / ทำหัตถการ No.8','0/0','2/0'],['Identification Error','0','0']],
    risks:[['AE Asthma','0','1(0)'],['AE COPD','0','6(1)'],['HT Crisis','0','1(1)'],['Hypotension','0','0(0)'],['Desat < 88%','0','0(0)']],note:'ยอดสะสมผู้รับบริการ 173 และรายละเอียด (56/77) แสดงตามต้นฉบับ'
  },
  warfarin: {
    date:'2 พ.ย. 2569',period:'1 ต.ค. 69–ปัจจุบัน',riskPeriod:'1 ต.ค. 68–ปัจจุบัน (ตามภาพ)',
    services:[['ผู้มารับบริการ','67','112'],['ADMIT','1','1 (PT INR prolong)'],['OBS.BP / Consult แพทย์','0/0','0/0'],['พ่นยา','0','0'],['ส่งตรวจ ER','1','2'],['ลงข้อมูลตรวจแลป รพ.สต.','0','0'],['ฉีด Vaccine ไข้หวัดใหญ่','0','3']],
    risks:[['PT/INR Prolong','1','2(1)'],['Hypoglycemia','0','0'],['Hyperglycemia','0','0(0)'],['HT Crisis','0','0(0)'],['Hypotension','0','0(0)'],['AE','0','0(0)'],['Tachycardia','0','0'],['Bradycardia','0','0'],['Hyperkalemia','1','1(1)'],['Dyspnea','0','0(0)'],['Anemia','0','0'],['ระบุตัวผู้ป่วยผิดคน','0','0'],['รายงานแลปผิด','0','0'],['พลัดตกหกล้ม','0','0'],['ร้องเรียนพฤติกรรมบริการ','0','0']],note:'รายงานแลปผิด ปริ๊นท์แลปวันที่ปัจจุบัน รวมผลแลปที่ออกย้อนหลังด้วย'
  },
  dm: {
    date:'3 พ.ย. 2569',period:'1 ต.ค. 69–ปัจจุบัน',
    services:[['ผู้มารับบริการ','66','303'],['TELEMED','0','1'],['Obs. BP / สอนฉีดยา','0/0','2/0'],['พ่นยา / ฉีดยา','0/0','1/0'],['ส่ง ER / Admit / OPD','0/0/0','3/0/0'],['ลงข้อมูลตรวจแลป รพ.สต.','-','0'],['ลงทะเบียนผู้ป่วย NCD','-','0'],['Identification Error','0','0']],
    risks:[['Hypoglycemia','0','0(0)'],['Hyperglycemia','0','3(2)'],['HT Crisis','0','0(0)'],['AE','0','0(0)'],['AKI','0','1(1)'],['Desat < 88%','0','0(0)'],['Hypotension','0','0(0)']]
  },
  ckd: {
    date:'4 พ.ย. 2569',period:'1 ต.ค. 68–ปัจจุบัน (ตามภาพ)',
    services:[['ผู้มารับบริการ','61','323'],['ADMIT','0','0'],['Obs. BP','2','4'],['ส่ง Refer RRT จอมทอง','0','0'],['พ่นยา','0','0'],['สอนฉีดยา','0','0'],['Consult HD / PD NURSE','0/0','0/0'],['Consult PC NURSE (ward)','0','0'],['Consult PC NURSE (OPD)','0','0'],['ลงข้อมูลแลป รพ.สต.','0','0'],['ส่ง ER / ห้องหัตถการ','3/0','6/0'],['Identification Error','0','0']],
    risks:[['Hypotension','0','0(0)'],['Hyperglycemia','1','3(1)'],['HT Crisis','3','4(2)'],['Syncope','0','0(0)'],['AE','0','0(0)'],['Hyperkalemia','0','1(1)'],['PCO2<15','1','2(2)'],['Dyspnea','0','0(0)'],['Bradycardia','0','0(0)']]
  }
};

function sourceTable(rows, headers) {
  return table(headers,rows.map((row,index)=>[String(index+1),...row.map(escapeHtml)]));
}

function sourceToolbar() {
  return `<div class="toolbar"><span class="demo">รายงานตามภาพต้นฉบับ • บันทึกแบบ Manual</span>${['overview','risks','workforce','approvals'].includes(pg)?`<label class="selector">หน่วยงาน <select id="unitFilter"><option value="all">ทุกหน่วยงาน</option>${U.map(unit=>`<option value="${unit.id}">${escapeHtml(unit.en)}</option>`).join('')}</select></label>`:''}${pageUnit?`<label class="selector">วันที่ ${thaiDateField('viewDate',state.date||todayThaiISO())}</label><label class="selector">เวร <select id="viewShift">${[['day','รายวัน'],['morning','เช้า'],['evening','บ่าย'],['night','ดึก']].map(([value,label])=>`<option value="${value}" ${value===(state.shift||(pageUnit.id==='lr'?'evening':'day'))?'selected':''}>${label}</option>`).join('')}</select></label>`:''}<button class="btn primary" id="printBtn">พิมพ์รายงาน</button></div>`;
}

function sourceUnitView(unit) {
  const report = dashboardReport(unit.id);
  if(!report)return `${header(unit.en+' : '+unit.name,'รายงานตามวันที่และเวรที่เลือก')}<div class="empty">ไม่พบข้อมูลตามวันที่และเวรที่เลือก</div><a class="btn primary" href="entry.html?unit=${unit.id}">บันทึกข้อมูลประจำวัน</a>`;
  const first = report.services[0];
  const summary = [kpi('ผู้รับบริการรายวัน',escapeHtml(first[1]),'ราย',colors(unit.id),EMOJI[unit.id],report.date),kpi('ยอดสะสมผู้รับบริการ',escapeHtml(first[2]),'ราย','#09a68f','▤',escapeHtml(report.period))];
  if(report.productivity) summary.push(kpi('Productivity',report.productivity,'','#9460cc','◎','ค่าตามรายงานต้นฉบับ'));
  const service = sourceTable(report.services,['ลำดับ','รายการ','รายวัน (ราย)','ยอดสะสม (ราย/ครั้ง)']);
  const risks = sourceTable(report.risks,['ลำดับ','รายการ','รายวัน','สะสม']);
  const admissions = report.admissions ? panel('ADMIT',sourceTable(report.admissions,['ลำดับ','หน่วยงาน','รายวัน','สะสม'])) : '';
  const staff = report.staff ? panel('เจ้าหน้าที่',table(['เจ้าหน้าที่','จำนวน (คน)'],report.staff.map(row=>row.map(escapeHtml)))) : '';
  return `${header(unit.en+' : '+unit.name,'รายงานประจำวันที่ '+report.date+(report.shift?' • '+report.shift:''))}<div class="grid reference-kpis" style="--card-count:${summary.length}">${summary.join('')}</div><div class="source-caption">ยอดสะสมบริการ ${escapeHtml(report.period)} • ยอดสะสมภาวะวิกฤต/อุบัติการณ์ ${escapeHtml(report.riskPeriod||report.period)}</div><div class="grid two equal source-report"><div>${panel('รายงานการให้บริการ',service)}</div><div class="source-stack">${panel(unit.id==='lr'?'กิจกรรมการให้บริการ':'ภาวะวิกฤต / อุบัติการณ์',risks)}${admissions}${staff}</div></div><div class="notice">ข้อมูลถอดจากภาพรายงานต้นฉบับ • เครื่องหมาย / และวงเล็บคงตามต้นฉบับ${report.note?' • '+escapeHtml(report.note):''}</div><div class="actions"><a class="btn primary" href="entry.html?unit=${unit.id}">บันทึกข้อมูลประจำวัน</a></div>`;
}

function sourceOverview() {
  const units = state.unit==='all'?U:U.filter(unit=>unit.id===state.unit);
  const items=units.map(unit=>({unit,report:dashboardReport(unit.id)})).filter(item=>item.report);
  const number=value=>{const match=String(value??'').replace(/,/g,'').match(/^\s*(\d+(?:\.\d+)?)/);return match?Number(match[1]):0;};
  const daily=items.map(item=>number(item.report.services[0][1]));
  const cumulative=items.map(item=>number(item.report.services[0][2]));
  const total=daily.reduce((a,b)=>a+b,0);
  const largest=total?items[daily.indexOf(Math.max(...daily))]:null;
  const productivity=items.filter(item=>item.report.productivity);
  const card=(icon,label,value,unit,detail,tone)=>`<article class="ov-stat" style="--tone:${tone}"><div class="ov-stat-top"><span>${label}</span><span class="ov-icon">${icon}</span></div><div class="ov-stat-value">${value}<small>${unit}</small></div><p>${detail}</p></article>`;
  const chartPanel=(eyebrow,title,subtitle,body,tag='')=>`<section class="ov-panel"><div class="ov-panel-heading"><div><span class="ov-eyebrow">${eyebrow}</span><h2>${title}</h2><p>${subtitle}</p></div>${tag?`<span class="ov-tag">${tag}</span>`:''}</div>${body}</section>`;
  const max=Math.max(1,...daily),ticks=[0,.25,.5,.75,1];
  const width=700,height=280,left=48,top=25,bottom=225,plot=620;
  const grid=ticks.map(t=>`<line x1="${left}" y1="${bottom-t*200}" x2="680" y2="${bottom-t*200}" stroke="#e7edf5" stroke-dasharray="4 5"/><text x="38" y="${bottom-t*200+4}" text-anchor="end">${Math.round(max*t)}</text>`).join('');
  const bars=items.map(({unit,report},i)=>{const slot=plot/items.length,x=left+i*slot+slot*.22,w=slot*.56,h=daily[i]/max*200;return `<g><title>${escapeHtml(unit.en)} · ${escapeHtml(report.date)}: ${daily[i]} ราย</title><rect x="${x}" y="${bottom-h}" width="${w}" height="${h}" rx="6" fill="${colors(unit.id)}"/><text class="ov-value-label" x="${x+w/2}" y="${bottom-h-9}" text-anchor="middle">${fmt(daily[i])}</text><text x="${x+w/2}" y="250" text-anchor="middle">${escapeHtml(unit.en.replace(' Clinic',''))}</text></g>`;}).join('');
  const bar=`<div class="ov-svg"><svg viewBox="0 0 ${width} ${height}" role="img" aria-label="กราฟแท่งผู้รับบริการรายวันแยกหน่วยงาน">${grid}${bars}</svg></div>`;
  let offset=0;
  const rings=items.map(({unit},i)=>{const share=total?daily[i]/total*100:0;const segment=`<circle cx="110" cy="110" r="82" fill="none" stroke="${colors(unit.id)}" stroke-width="27" pathLength="100" stroke-dasharray="${share} ${100-share}" stroke-dashoffset="${-offset}" transform="rotate(-90 110 110)"><title>${escapeHtml(unit.en)}: ${fmt(daily[i])} ราย (${share.toFixed(1)}%)</title></circle>`;offset+=share;return segment;}).join('');
  const donut=`<div class="ov-donut-layout"><svg viewBox="0 0 220 220" role="img" aria-label="กราฟโดนัทสัดส่วนผู้รับบริการจากรายงาน"><circle cx="110" cy="110" r="82" fill="none" stroke="#edf2f8" stroke-width="27"/>${rings}<text x="110" y="105" text-anchor="middle" class="ov-donut-number">${fmt(total)}</text><text x="110" y="130" text-anchor="middle">รายในชุดรายงาน</text></svg><div class="ov-legend">${items.map(({unit},i)=>`<a href="${unit.id}.html"><span class="ov-dot" style="background:${colors(unit.id)}"></span><span>${escapeHtml(unit.en)}</span><strong>${total?(daily[i]/total*100).toFixed(1):'0.0'}%</strong><small>${fmt(daily[i])} ราย</small></a>`).join('')}</div></div>`;
  const lineMax=Math.max(1,...cumulative,...daily),xAt=i=>items.length===1?365:left+i*plot/(items.length-1),yAt=v=>bottom-v/lineMax*200;
  const lineGrid=ticks.map(t=>`<line x1="${left}" y1="${bottom-t*200}" x2="680" y2="${bottom-t*200}" stroke="#e7edf5" stroke-dasharray="4 5"/><text x="38" y="${bottom-t*200+4}" text-anchor="end">${Math.round(lineMax*t)}</text>`).join('');
  const series=(values,color,label)=>`<polyline fill="none" stroke="${color}" stroke-width="3" stroke-linejoin="round" points="${values.map((v,i)=>`${xAt(i)},${yAt(v)}`).join(' ')}"/>${values.map((v,i)=>`<circle cx="${xAt(i)}" cy="${yAt(v)}" r="5" fill="white" stroke="${color}" stroke-width="3"><title>${escapeHtml(items[i].unit.en)} · ${label}: ${fmt(v)} ราย</title></circle>`).join('')}`;
  const line=`<div class="ov-line-key"><span><i style="background:#1684e5"></i>รายวัน</span><span><i style="background:#8c65d9"></i>ยอดสะสมตามต้นฉบับ</span></div><div class="ov-svg"><svg viewBox="0 0 700 280" role="img" aria-label="กราฟเส้นเปรียบเทียบรายวันและยอดสะสมแยกหน่วยงาน">${lineGrid}${series(cumulative,'#8c65d9','ยอดสะสม')}${series(daily,'#1684e5','รายวัน')}${items.map(({unit},i)=>`<text x="${xAt(i)}" y="250" text-anchor="middle">${escapeHtml(unit.en.replace(' Clinic',''))}</text>`).join('')}</svg></div><p class="ov-chart-note">แกนนอนคือหน่วยงาน • เส้นแสดงการเปรียบเทียบระหว่างหน่วยงาน ไม่ใช่แนวโน้มตามเวลา • ช่วงสะสมแตกต่างกัน</p>`;
  const rows=items.map(({unit,report},i)=>[`<a class="ov-unit-link" href="${unit.id}.html"><span class="ov-dot" style="background:${colors(unit.id)}"></span>${escapeHtml(unit.en)}</a>`,escapeHtml(report.date),`<strong>${escapeHtml(report.services[0][1])}</strong>`,escapeHtml(report.services[0][2]),escapeHtml(report.period),report.productivity?`<span class="ov-productivity">${escapeHtml(report.productivity)}%</span>`:'<span class="muted">ไม่มีข้อมูล</span>']);
  return `<div class="ov-dashboard"><section class="ov-hero"><div><span class="ov-eyebrow">NURSING PERFORMANCE</span><h1>ภาพรวมกลุ่มงานการพยาบาล</h1><p>มองเห็นภาพบริการ เชื่อมต่อการดูแลในทุกหน่วยงาน</p></div><div class="ov-hero-badge"><span class="ov-live-dot"></span>${items.length} หน่วยงานในมุมมองนี้<small>ปีงบประมาณ 2570</small></div></section><div class="ov-context"><span>ⓘ</span><p>ข้อมูลรายงานอ้างอิงและรายงานจากฐานข้อมูลกลาง • รายงานแต่ละหน่วยงานเป็นคนละวันและช่วงสะสมต่างกัน ยอดรวมด้านล่างเป็นผลรวมชุดรายงาน ไม่ใช่ยอดรวมวันเดียวกัน</p></div><div class="ov-stats">${card('▥','ผู้รับบริการในชุดรายงาน',fmt(total),'ราย','รวมค่ารายวันของหน่วยงานที่เลือก','#1684e5')}${card('◈','หน่วยงานที่มีรายงาน',items.length,'หน่วยงาน','แสดงเฉพาะหน่วยงานตามสิทธิ์ของคุณ','#0aa899')}${card('↗','ผู้รับบริการสูงสุดในชุดรายงาน',largest?escapeHtml(largest.unit.en):'—','',largest?`${fmt(Math.max(...daily))} ราย · ${escapeHtml(largest.report.date)}`:'ยังไม่มีข้อมูล','#8c65d9')}${card('◎','มีข้อมูล Productivity',productivity.length,'หน่วยงาน','แสดงค่าแยกรายหน่วย ไม่เฉลี่ยรวม','#e89432')}</div><div class="ov-chart-grid">${chartPanel('SERVICE VOLUME','ผู้รับบริการแยกหน่วยงาน','ค่ารายวันจากรายงานอ้างอิงของแต่ละหน่วยงาน',bar,'กราฟแท่ง')}${chartPanel('SERVICE MIX','สัดส่วนผู้รับบริการ','สัดส่วนจากผลรวมค่ารายวันในชุดรายงาน',donut,'โดนัท')}</div><div class="ov-bottom-grid">${chartPanel('SERVICE PROFILE','รายวันและยอดสะสม','เปรียบเทียบค่ารายงานของหน่วยงานที่เลือก',line,'กราฟเส้น')}${chartPanel('UNIT SNAPSHOT','Productivity รายหน่วย','ค่าที่ระบุไว้ในรายงานต้นฉบับ',`<div class="ov-productivity-list">${productivity.length?productivity.map(({unit,report})=>`<a href="${unit.id}.html"><span class="ov-unit-symbol" style="--tone:${colors(unit.id)}">${EMOJI[unit.id]}</span><div><strong>${escapeHtml(unit.en)}</strong><small>${escapeHtml(report.date)}</small></div><b>${escapeHtml(report.productivity)}<small>%</small></b></a>`).join(''):'<div class="empty">หน่วยงานที่เลือกยังไม่มีข้อมูล Productivity</div>'}</div><p class="ov-chart-note">ยังไม่แสดงสถานะผ่านเกณฑ์ เนื่องจากแต่ละหน่วยงานใช้สูตรและเกณฑ์ต่างกัน</p>`)}</div>${chartPanel('REPORT DETAILS','รายงานแยกหน่วยงาน','ตรวจสอบวันที่ ช่วงสะสม และรายละเอียดต้นฉบับ',table(['หน่วยงาน','วันที่รายงาน','ผู้รับบริการรายวัน','ยอดสะสม','ช่วงสะสมตามต้นฉบับ','Productivity'],rows),`${items.length} รายงาน`)}</div>`;
}

const SOURCE_DATES = {opd:'2026-11-03',lr:'2026-11-03',ht:'2026-10-22',asthma:'2026-10-30',warfarin:'2026-11-02',dm:'2026-11-03',ckd:'2026-11-04'};
const SOURCE_GROUPS = ['services','risks','admissions','staff'];

function sourceDraftKey(unit,date=SOURCE_DATES[unit],shift=unit==='lr'?'evening':'day') {
  return `ncc_source_v1_${window.NCC_USER?.id || 'demo'}_${unit}_${date}_${shift}`;
}

function readSourceDraft(unit,date,shift) {
  const central=window.NCC_REPORTS?.[`${unit}_${date||SOURCE_DATES[unit]}_${shift||(unit==='lr'?'evening':'day')}`];
  if(window.NCC_REPORTS!==undefined)return central?{...central.report,date:thaiDate(date||SOURCE_DATES[unit])}:null;
  const raw = localStorage.getItem(sourceDraftKey(unit,date,shift));
  if(!raw) return null;
  const saved = JSON.parse(raw);
  const source = SOURCE_REPORTS[unit];
  if(saved.version!==1 || saved.department!==unit || saved.date!==(date||SOURCE_DATES[unit]) || saved.shift!==(shift||(unit==='lr'?'evening':'day')) || !saved.report) throw new Error('รูปแบบฉบับร่างไม่ถูกต้อง');
  validateSourceReport(unit,saved.date,saved.shift,saved.report);
  for(const group of SOURCE_GROUPS) {
    const expected=source[group]||[];
    const rows=saved.report[group]||[];
    if(rows.length!==expected.length || rows.some((row,index)=>!Array.isArray(row)||row.length!==expected[index].length||row[0]!==expected[index][0]||row.some(value=>typeof value!=='string'))) throw new Error('รายการฉบับร่างไม่ตรงกับแบบฟอร์ม');
  }
  return saved.report;
}

function dashboardReport(unit) {
  try {
    const date=pageUnit?state.date:undefined,shift=pageUnit?state.shift:undefined;
    if(window.NCC_REPORTS!==undefined&&!date&&!shift) {
      const latest=Object.entries(window.NCC_REPORTS).filter(([key])=>key.startsWith(unit+'_')).sort(([a],[b])=>b.localeCompare(a))[0];
      if(latest)return {...latest[1].report,date:thaiDate(latest[0].split('_')[1])};
    }
    const saved=readSourceDraft(unit,date,shift);
    if(saved)return saved;
    if((date&&date!==SOURCE_DATES[unit])||(shift&&shift!==(unit==='lr'?'evening':'day')))return null;
    return SOURCE_REPORTS[unit];
  } catch(error) {
    return {...SOURCE_REPORTS[unit],note:'อ่านฉบับร่างไม่สำเร็จ แสดงภาพต้นฉบับ • '+error.message};
  }
}

function validateSourceReport(unit,date,shift,report) {
  const source=SOURCE_REPORTS[unit];
  const parsed=new Date(date+'T00:00:00Z');
  if(!source || !/^\d{4}-\d{2}-\d{2}$/.test(date)||!Number.isFinite(parsed.getTime())||parsed.toISOString().slice(0,10)!==date)throw new Error('วันที่รายงานไม่ถูกต้อง');
  if(!['day','morning','evening','night'].includes(shift))throw new Error('เวรไม่ถูกต้อง');
  for(const field of ['date','period','riskPeriod','shift','note','productivity']) {
    if(report[field]!==undefined&&(typeof report[field]!=='string'||report[field].length>500||/[<>]/.test(report[field])))throw new Error('รายละเอียดรายงานไม่ถูกต้อง');
  }
  for(const group of SOURCE_GROUPS) {
    const expected=source[group]||[],rows=report[group]||[];
    if(!Array.isArray(rows)||rows.length!==expected.length)throw new Error('รายการไม่ครบตามแบบฟอร์ม');
    rows.forEach((row,index)=>{
      if(!Array.isArray(row)||row.length!==expected[index].length||row[0]!==expected[index][0])throw new Error('รายการไม่ตรงตามแบบฟอร์ม');
      row.slice(1).forEach((value,column)=>{
        const numeric=/^\d+(?:\/\d+)*(?:\s*\(\d+(?:\/\d+)*\))?$/;
        if(typeof value!=='string'||!value.trim()||value.length>100||!(numeric.test(value)||value==='-'||value===expected[index][column+1]))throw new Error('ค่ารายการ '+row[0]+' ไม่ถูกต้อง');
      });
    });
  }
  if(source.productivity&&(typeof report.productivity!=='string'||report.productivity.trim()===''||!Number.isFinite(Number(report.productivity))||Number(report.productivity)<0))throw new Error('Productivity ไม่ถูกต้อง');
}

function sourceEntryPage() {
  const requested=new URLSearchParams(location.search).get('unit');
  const selected=U.some(unit=>unit.id===requested)?requested:U[0].id;
  return `${header('บันทึกรายงานประจำวัน','บันทึกรายวันและยอดสะสมตามแบบรายงานต้นฉบับ')}<section class="panel entry-panel"><div class="panel-body"><div class="entry-controls"><div class="formgrid"><div class="formgroup"><label for="sourceUnit">หน่วยงาน</label><select id="sourceUnit">${U.map(unit=>`<option value="${unit.id}" ${unit.id===selected?'selected':''}>${escapeHtml(unit.en)}</option>`).join('')}</select></div><div class="formgroup"><label for="sourceDate">วันที่รายงาน</label>${thaiDateField('sourceDate',todayThaiISO())}</div><div class="formgroup"><label for="sourceShift">เวร</label><select id="sourceShift"><option value="day">รายวัน (คลินิก)</option><option value="morning">เช้า</option><option value="evening" ${selected==='lr'?'selected':''}>บ่าย 16.00–00.00 น.</option><option value="night">ดึก</option></select></div></div><div class="actions"><button class="btn" id="sourceFill">กรอกตามภาพ</button><button class="btn primary" id="sourceSave">บันทึกฉบับร่าง</button><button class="btn" id="sourceLoad">เรียกข้อมูล</button><details class="entry-samples"><summary>ข้อมูลตัวอย่าง</summary><button class="btn alt" id="sourceSeed">บันทึกข้อมูลทดสอบทั้ง 7 หน่วยงาน</button></details></div></div><p id="sourceStatus" role="status" aria-live="polite"></p><div id="sourceFields"></div><div id="sourceResults"></div><div class="notice">รายงานบันทึกในฐานข้อมูลกลาง MySQL • ช่องคู่ใช้เครื่องหมาย / และวงเล็บได้ • นำเข้าฉบับร่างเดิมได้จากเบราว์เซอร์เครื่องที่เคยบันทึก</div></div></section>`;
}

function renderSourceFields(report,filled=false) {
  const titles={services:'การให้บริการ',risks:'ภาวะวิกฤต / อุบัติการณ์',admissions:'ADMIT',staff:'เจ้าหน้าที่'};
  const groups=SOURCE_GROUPS.filter(group=>report[group]).map(group=>{
    const headings=group==='staff'?['รายการ','คน']:['รายการ','รายวัน','สะสม'];
    const rows=report[group].map((row,index)=>[escapeHtml(row[0]),...row.slice(1).map((value,column)=>`<input aria-label="${escapeHtml(row[0])} ${column===0?'รายวัน':'สะสม'}" id="source_${group}_${index}_${column}" value="${filled?escapeHtml(value):''}" maxlength="100" autocomplete="off" required>`)]);
    return `<section class="entry-section entry-${group}"><h2>${titles[group]}<span>${report[group].length} รายการ</span></h2>${table(headings,rows)}</section>`;
  });
  byId('sourceFields').innerHTML=`<div class="entry-period"><span>สะสมบริการ <b>${escapeHtml(report.period)}</b></span><span>ภาวะวิกฤต <b>${escapeHtml(report.riskPeriod||report.period)}</b></span></div><div class="entry-sections">${groups.join('')}${report.productivity?`<section class="entry-section entry-productivity"><h2>Productivity</h2><div class="formgroup"><label for="sourceProductivity">ค่าตามรายงานต้นฉบับ</label><input id="sourceProductivity" type="number" min="0" step="0.01" value="${filled?report.productivity:''}"></div></section>`:''}</div>`;
}

function collectSourceReport() {
  const unit=byId('sourceUnit').value;
  const report=JSON.parse(JSON.stringify(SOURCE_REPORTS[unit]));
  const date=byId('sourceDate').value;
  if(!/^\d{4}-\d{2}-\d{2}$/.test(date)) throw new Error('กรุณาระบุวันที่รายงาน');
  report.date=thaiDate(date);
  if(unit==='lr')report.shift=byId('sourceShift').selectedOptions[0].text;
  for(const group of SOURCE_GROUPS) {
    if(!report[group])continue;
    report[group]=report[group].map((row,index)=>[row[0],...row.slice(1).map((_,column)=>{
      const value=byId(`source_${group}_${index}_${column}`).value.trim();
      if(!value)throw new Error('กรุณากรอกข้อมูลให้ครบ หรือใส่ - เมื่อไม่มีข้อมูล');
      return value;
    })]);
  }
  if(report.productivity) {
    const value=byId('sourceProductivity').value;
    if(value===''||!Number.isFinite(Number(value))||Number(value)<0)throw new Error('กรุณาระบุ Productivity ให้ถูกต้อง');
    report.productivity=value;
  }
  return report;
}

async function saveSourceReport(unit,date,shift,report) {
  validateSourceReport(unit,date,shift,report);
  const key=`${unit}_${date}_${shift}`;
  const body=new URLSearchParams({csrf:window.NCC_CSRF,department:unit,date,shift,report:JSON.stringify(report),version:String(window.NCC_REPORTS?.[key]?.version||0)});
  const response=await fetch(window.nccUrl('/api/reports'),{method:'POST',body,credentials:'same-origin'});
  if(response.redirected)throw new Error('หมดเวลาเข้าสู่ระบบ กรุณาเข้าสู่ระบบใหม่');
  let result;try{result=await response.json();}catch{throw new Error('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาลองใหม่');}
  if(!response.ok)throw new Error(result.error||'บันทึกไม่สำเร็จ');
  window.NCC_REPORTS[key]=result;
}

function bindSourceEntry() {
  const status=message=>{byId('sourceStatus').textContent=message;};
  const safely=action=>async event=>{const button=event?.currentTarget;if(button)button.disabled=true;try{await action();}catch(error){status('ดำเนินการไม่สำเร็จ • '+error.message);}finally{if(button)button.disabled=false;}};
  let loadSequence=0;
  const showSelected=()=>{
    const unit=byId('sourceUnit').value,date=byId('sourceDate').value,shift=byId('sourceShift').value;
    const saved=readSourceDraft(unit,date,shift);
    renderSourceFields(saved||SOURCE_REPORTS[unit],Boolean(saved));
    status(saved?`แสดงรายงานที่บันทึกแล้ว • ${thaiDate(date)} • ${byId('sourceShift').selectedOptions[0].text}`:'ยังไม่มีรายงานที่บันทึกสำหรับหน่วยงาน วันที่ และเวรนี้ • กรอกใหม่ หรือเลือก “กรอกตามภาพ” เพื่อใช้ข้อมูลตัวอย่าง');
  };
  const loadSelected=async()=>{
    const sequence=++loadSequence;
    showSelected();
    byId('sourceSave').disabled=true;
    byId('sourceFields').inert=true;
    try {
      const response=await fetch(window.nccUrl('/api/reports'),{credentials:'same-origin',cache:'no-store'});
      if(response.redirected)throw new Error('หมดเวลาเข้าสู่ระบบ กรุณาเข้าสู่ระบบใหม่');
      const data=await response.json();
      if(!response.ok||!data.reports)throw new Error(data.error||'โหลดรายงานจากฐานข้อมูลไม่สำเร็จ');
      if(sequence!==loadSequence)return;
      window.NCC_REPORTS=data.reports;
      showSelected();
    } catch(error) {if(sequence===loadSequence)status('โหลดข้อมูลล่าสุดไม่สำเร็จ • '+error.message);}
    finally {if(sequence===loadSequence){byId('sourceSave').disabled=false;byId('sourceFields').inert=false;}}
  };
  const reset=()=>{
    const unit=byId('sourceUnit').value;
    byId('sourceDate').value=byId('sourceDate').value||todayThaiISO();
    syncThaiDateField('sourceDate');
    byId('sourceShift').value=unit==='lr'?'evening':'day';
    return loadSelected();
  };
  byId('sourceUnit').onchange=safely(reset);
  byId('sourceDate').onchange=safely(loadSelected);
  byId('sourceShift').onchange=safely(loadSelected);
  byId('sourceFill').onclick=safely(()=>{++loadSequence;byId('sourceSave').disabled=false;byId('sourceFields').inert=false;renderSourceFields(SOURCE_REPORTS[byId('sourceUnit').value],true);status('แสดงข้อมูลตัวอย่างจากภาพต้นฉบับ • ยังไม่ได้บันทึกลงฐานข้อมูล');});
  byId('sourceSave').onclick=safely(async()=>{await saveSourceReport(byId('sourceUnit').value,byId('sourceDate').value,byId('sourceShift').value,collectSourceReport());status('บันทึกลงฐานข้อมูลกลาง MySQL สำเร็จ');});
  byId('sourceLoad').onclick=safely(loadSelected);
  byId('sourceSeed').textContent='นำเข้าฉบับร่างเดิมจากเครื่องนี้';
  byId('sourceSeed').onclick=safely(async()=>{
    const results=[];
    const prefix=`ncc_source_v1_${window.NCC_USER.id}_`;
    const legacy=/^ncc_source_v1_(opd|lr|ht|dm|ckd|warfarin|asthma)_\d{4}-\d{2}-\d{2}_(day|morning|evening|night)$/;
    const keys=Object.keys(localStorage).filter(key=>key.startsWith(prefix)||legacy.test(key));
    for(const key of keys) {
      try {
        const saved=JSON.parse(localStorage.getItem(key));
        if(!U.some(unit=>unit.id===saved.department))continue;
        const slot=`${saved.department}_${saved.date}_${saved.shift}`;
        if(window.NCC_REPORTS[slot]){results.push([saved.department,saved.date,saved.shift,'มีข้อมูลส่วนกลางแล้ว • ข้าม']);continue;}
        await saveSourceReport(saved.department,saved.date,saved.shift,saved.report);
        results.push([saved.department,saved.date,saved.shift,'นำเข้า MySQL สำเร็จ']);
      } catch(error) {results.push([key,'—','—','ไม่สำเร็จ • '+error.message]);}
    }
    byId('sourceResults').innerHTML=table(['หน่วยงาน','วันที่ (พ.ศ.)','เวร','ผลทดสอบ'],results.map(row=>row.map((value,i)=>escapeHtml(i===1?thaiDate(value):value))));
    status(keys.length?'นำเข้าฉบับร่างเสร็จแล้ว ข้อมูลเดิมในเครื่องยังคงอยู่':'ไม่พบฉบับร่างเดิมของบัญชีนี้ในเบราว์เซอร์');
  });
  reset();
}

function sourceReportRows(unitId='all',group='all') {
  const rows=[];
  for(const unit of U.filter(unit=>unitId==='all'||unit.id===unitId)) {
    const report=dashboardReport(unit.id);
    for(const section of SOURCE_GROUPS.filter(section=>group==='all'||section===group)) {
      for(const row of report[section]||[]) rows.push([unit.en,report.date,section,report.period,report.riskPeriod||report.period,...row]);
    }
  }
  return rows;
}

function sourceReportsPage() {
  return `${header('รายงานและส่งออกข้อมูล','รายวันและยอดสะสมตามรายงานอ้างอิงและข้อมูลส่วนกลางที่บันทึก')}<section class="panel"><div class="panel-body"><div class="formgrid"><div class="formgroup"><label for="rpUnit">หน่วยงาน</label><select id="rpUnit"><option value="all">ทุกหน่วยงาน</option>${U.map(unit=>`<option value="${unit.id}">${escapeHtml(unit.en)}</option>`).join('')}</select></div><div class="formgroup"><label for="rpType">หมวดรายงาน</label><select id="rpType"><option value="all">ทุกหมวด</option><option value="services">บริการ</option><option value="risks">ภาวะวิกฤต / อุบัติการณ์</option><option value="admissions">ADMIT</option><option value="staff">เจ้าหน้าที่</option></select></div><div class="formgroup"><label for="rpFormat">รูปแบบ</label><select id="rpFormat"><option value="csv">CSV</option><option value="print">พิมพ์ / PDF</option></select></div></div><div class="actions"><button class="btn" id="previewBtn">แสดงรายงาน</button><button class="btn primary" id="runExport">ส่งออกรายงาน</button></div></div></section><div class="notice">แสดงรายงานตามวันที่ในภาพแต่ละหน่วยงาน ยอดสะสมคงช่วงเวลาตามต้นฉบับ</div><div id="reportPreview"></div>`;
}

function bindSourceReports() {
  const headers=['หน่วยงาน','วันที่รายงาน','หมวด','ช่วงสะสมบริการ','ช่วงสะสมภาวะวิกฤต','รายการ','รายวัน / คน','สะสม'];
  const query=new URLSearchParams(location.search).get('unit');
  byId('rpUnit').value=U.some(unit=>unit.id===query)?query:'all';
  const rows=()=>sourceReportRows(byId('rpUnit').value,byId('rpType').value).map(row=>row.length===7?[...row,'']:row);
  const refresh=()=>{byId('reportPreview').innerHTML=table(headers,rows().map(row=>row.map(escapeHtml)));};
  byId('previewBtn').onclick=refresh;
  byId('rpUnit').onchange=refresh;
  byId('rpType').onchange=refresh;
  byId('runExport').onclick=()=>{
    refresh();
    if(byId('rpFormat').value==='print')return window.nccPrint();
    const csv='\uFEFF'+[headers,...rows()].map(row=>row.map(value=>'"'+String(value).replaceAll('"','""')+'"').join(',')).join('\r\n');
    exportFile('Nursing_Manual_Reports.csv',csv,'text/csv;charset=utf-8');
  };
  refresh();
}

function sourceRisksPage() {
  const rows=sourceReportRows(state.unit,'risks').map(row=>[row[0],row[1],row[4],...row.slice(5)]);
  return `${header('ภาวะวิกฤตและอุบัติการณ์','แสดงค่ารายวันและสะสมแยกตามหน่วยงานและช่วงอ้างอิง')}${panel('รายการตามรายงาน',table(['หน่วยงาน','วันที่รายงาน','ช่วงสะสม','รายการ','รายวัน','สะสม'],rows.map(row=>row.map(escapeHtml))))}`;
}

function sourceWorkforcePage() {
  const units=U.filter(unit=>state.unit==='all'||unit.id===state.unit);
  return `${header('กำลังคนและ Productivity','ค่าตามรายงานต้นฉบับและฉบับร่างที่บันทึก')}${panel('ข้อมูลรายหน่วยงาน',table(['หน่วยงาน','วันที่รายงาน','รายการ','จำนวน / ค่า'],units.flatMap(unit=>{const report=dashboardReport(unit.id);return [...(report.staff||[]).map(row=>[unit.en,report.date,...row]),[unit.en,report.date,'Productivity',report.productivity||'ไม่มีข้อมูลในภาพ']];}).map(row=>row.map(escapeHtml))))}<div class="notice">Productivity เป็นค่าที่บันทึกจากต้นฉบับ สูตรคำนวณยังรอกำหนด</div>`;
}

function sourceApprovalsPage() {
  const rows=U.filter(unit=>state.unit==='all'||unit.id===state.unit).map(unit=>{
    try {const saved=readSourceDraft(unit.id);return [unit.en,thaiDate(SOURCE_DATES[unit.id]),saved?'ฉบับร่างที่บันทึก':'ภาพต้นฉบับ','รอระบบรับรอง'];}
    catch(error){return [unit.en,thaiDate(SOURCE_DATES[unit.id]),'ฉบับร่างอ่านไม่ได้','ตรวจข้อมูลในหน้าบันทึก'];}
  });
  return `${header('ตรวจสอบและรับรอง','สถานะการบันทึกข้อมูลแยกตามหน่วยงาน')}${panel('สถานะรายงาน',table(['หน่วยงาน','วันที่ (พ.ศ.)','ข้อมูล','การรับรอง'],rows.map(row=>row.map(escapeHtml))))}<div class="notice">ขั้นตอนส่งตรวจสอบและรับรองยังรอพัฒนา</div>`;
}
