const fs = require('fs');

let content = fs.readFileSync('pd_index.php', 'utf8');

// Replace Title
content = content.replace(/ระบบงานล้างไตทางช่องท้อง \| PD Dashboard/g, 'ระบบฟอกเลือดด้วยเครื่องไตเทียม | HD Dashboard');
content = content.replace(/ระบบบันทึกงานล้างไตทางช่องท้อง \(PD\)/g, 'ระบบบันทึกงานฟอกเลือดด้วยเครื่องไตเทียม (HD)');

// Change colors: replace focus:ring-ministry with focus:ring-blue-600 etc.
// Keep ministry colors for nav, but let's just change the API
content = content.replace(/pd_api\.php/g, 'hd_api.php');

// Form Replacement
const formHtml = `
<div class="lg:col-span-2">
    <label for="record_date" class="block text-sm font-medium text-gray-700 mb-1">วันที่รับบริการ (จ.-ศ.)</label>
    <input type="date" id="record_date" name="record_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-ministry focus:ring focus:ring-ministry focus:ring-opacity-50 px-3 py-2 border">
</div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">มารับบริการ</label><input type="number" name="service_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ทำ HD</label><input type="number" name="hd_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Admit</label><input type="number" name="admit_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Refer OPD</label><input type="number" name="refer_opd" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Emergency</label><input type="number" name="emergency" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ส่ง ER</label><input type="number" name="refer_er" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">การให้เลือด</label><input type="number" name="blood_transfusion" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Off DLC</label><input type="number" name="off_dlc" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">off perm</label><input type="number" name="off_perm" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Insert DLC</label><input type="number" name="insert_dlc" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">น้ำ RO C/S bac.</label><input type="number" name="ro_cs_bacteria" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">น้ำ RO Endotoxin</label><input type="number" name="ro_endotoxin" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Counselling</label><input type="number" name="counselling" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ฉีดยา</label><input type="number" name="injection" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ล้างแผล</label><input type="number" name="wound_dressing" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">เย็บแผล</label><input type="number" name="suture" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ตัดไหม</label><input type="number" name="remove_suture" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Hypotension</label><input type="number" name="hypotension" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Hypertension</label><input type="number" name="hypertension" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Muscle cramp</label><input type="number" name="muscle_cramp" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Headache</label><input type="number" name="headache" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Fever</label><input type="number" name="fever" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-ministry"></div>
`;

content = content.replace(/<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-5">([\s\S]*?)<\/div>\s*<button type="submit"/m, '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-5">' + formHtml + '</div>\n                            <button type="submit"');

// Metrics array
const metricsCode = `const metrics = [
    { key: 'service_count', label: 'จำนวนผู้รับบริการ' },
    { key: 'hd_count', label: 'ทำ HD' },
    { key: 'admit_count', label: 'Admit' },
    { key: 'refer_opd', label: 'Refer OPD' },
    { key: 'emergency', label: 'Emergency' },
    { key: 'refer_er', label: 'ส่ง ER' },
    { key: 'blood_transfusion', label: 'การให้เลือด' },
    { key: 'off_dlc', label: 'Off DLC' },
    { key: 'off_perm', label: 'off perm' },
    { key: 'insert_dlc', label: 'Insert DLC' },
    { key: 'ro_cs_bacteria', label: 'ส่งน้ำ RO ตรวจ C/Sbacteria' },
    { key: 'ro_endotoxin', label: 'ส่งน้ำ RO ตรวจ Endotoxin' },
    { key: 'counselling', label: 'Counselling' },
    { key: 'injection', label: 'ฉีดยา' },
    { key: 'wound_dressing', label: 'ล้างแผล' },
    { key: 'suture', label: 'เย็บแผล' },
    { key: 'remove_suture', label: 'ตัดไหม' },
    { key: 'hypotension', label: 'Hypotension' },
    { key: 'hypertension', label: 'hypertension' },
    { key: 'muscle_cramp', label: 'Muscle cramp' },
    { key: 'headache', label: 'headache' },
    { key: 'fever', label: 'Fever' }
];`;

content = content.replace(/const metrics = \[([\s\S]*?)\];/g, metricsCode);

// Dashboard Cards
const dashboardCardsCode = `const metricsList = [
    { key: 'total_service_count', label: 'รับบริการ', val: s.total_service_count, color: 'green', icon: 'users' },
    { key: 'total_hd_count', label: 'ทำ HD', val: s.total_hd_count, color: 'blue', icon: 'procedures' },
    { key: 'total_admit_count', label: 'Admit', val: s.total_admit_count, color: 'orange', icon: 'bed-pulse' },
    { key: 'total_refer_opd', label: 'Refer OPD', val: s.total_refer_opd, color: 'teal', icon: 'hospital' },
    { key: 'total_emergency', label: 'Emergency', val: s.total_emergency, color: 'red', icon: 'truck-medical' },
    { key: 'total_refer_er', label: 'ส่ง ER', val: s.total_refer_er, color: 'red', icon: 'ambulance' },
    { key: 'total_blood_transfusion', label: 'ให้เลือด', val: s.total_blood_transfusion, color: 'red', icon: 'droplet' },
    { key: 'total_off_dlc', label: 'Off DLC', val: s.total_off_dlc, color: 'indigo', icon: 'xmark' },
    { key: 'total_off_perm', label: 'off perm', val: s.total_off_perm, color: 'indigo', icon: 'xmark-circle' },
    { key: 'total_insert_dlc', label: 'Insert DLC', val: s.total_insert_dlc, color: 'cyan', icon: 'syringe' },
    { key: 'total_ro_cs_bacteria', label: 'น้ำ RO C/S', val: s.total_ro_cs_bacteria, color: 'teal', icon: 'vial' },
    { key: 'total_ro_endotoxin', label: 'น้ำ RO Endotoxin', val: s.total_ro_endotoxin, color: 'teal', icon: 'vials' },
    { key: 'total_counselling', label: 'Counselling', val: s.total_counselling, color: 'blue', icon: 'comments' },
    { key: 'total_injection', label: 'ฉีดยา', val: s.total_injection, color: 'purple', icon: 'syringe' },
    { key: 'total_wound_dressing', label: 'ล้างแผล', val: s.total_wound_dressing, color: 'orange', icon: 'band-aid' },
    { key: 'total_suture', label: 'เย็บแผล', val: s.total_suture, color: 'red', icon: 'scissors' },
    { key: 'total_remove_suture', label: 'ตัดไหม', val: s.total_remove_suture, color: 'blue', icon: 'scissors' },
    { key: 'total_hypotension', label: 'Hypotension', val: s.total_hypotension, color: 'red', icon: 'arrow-down' },
    { key: 'total_hypertension', label: 'Hypertension', val: s.total_hypertension, color: 'red', icon: 'arrow-up' },
    { key: 'total_muscle_cramp', label: 'Muscle cramp', val: s.total_muscle_cramp, color: 'orange', icon: 'bolt' },
    { key: 'total_headache', label: 'Headache', val: s.total_headache, color: 'orange', icon: 'head-side-virus' },
    { key: 'total_fever', label: 'Fever', val: s.total_fever, color: 'red', icon: 'temperature-high' }
];

let cardsHTML = '';
metricsList.forEach(m => {`;

content = content.replace(/const metrics = \[([\s\S]*?)\];\s*let cardsHTML = '';\s*metrics.forEach/m, dashboardCardsCode);

// Fix the array map name in dashboard
content = content.replace(/const serviceData = chartData.map\(d => d.monthly_service\);\s*const admitData = chartData.map\(d => d.monthly_admit\);/, 'const serviceData = chartData.map(d => d.monthly_service);\nconst admitData = chartData.map(d => d.monthly_hd);');

fs.writeFileSync('hd_index.php', content);
console.log('Generated hd_index.php successfully.');
