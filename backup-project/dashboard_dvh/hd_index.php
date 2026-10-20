<?php 
require 'db.php';

// Enforce login and role security access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hd_user', 'both_user'])) {
    header('Location: login.php?sys=hd');
    exit;
}

// Fetch system settings for fiscal year
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt_settings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$default_fy = isset($settings['default_fiscal_year']) ? (int)$settings['default_fiscal_year'] : (int)date('Y');
$range_start = isset($settings['fiscal_year_range_start']) ? (int)$settings['fiscal_year_range_start'] : $default_fy - 5;
$range_end = isset($settings['fiscal_year_range_end']) ? (int)$settings['fiscal_year_range_end'] : $default_fy + 2;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบฟอกเลือดด้วยเครื่องไตเทียม | HD Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        hdblue: { light: '#3b82f6', DEFAULT: '#2563eb', dark: '#1d4ed8' },
                        ministry: {
                            light: '#008751',
                            DEFAULT: '#00603f',
                            dark: '#004d32',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f3f4f6; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="text-gray-800">

    <!-- Top Navigation -->
    <nav class="bg-hdblue text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 flex items-center bg-white p-1 rounded-full">
                        <img class="h-10 w-10 object-contain rounded-full" src="Logo.jpg" alt="Hospital Logo">
                    </div>
                    <span class="font-bold text-lg md:text-xl tracking-wide">
                        ระบบบันทึกงานฟอกเลือดด้วยเครื่องไตเทียม (HD)
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-white hover:text-gray-200 px-3 py-1.5 rounded-md text-sm font-medium transition flex items-center">
                        <i class="fas fa-home mr-1"></i> <span class="hidden sm:inline">หน้าหลัก</span>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <a href="admin_panel.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition flex items-center shadow-sm">
                                <i class="fas fa-cog mr-1"></i> <span class="hidden sm:inline">ตั้งค่าระบบ</span>
                            </a>
                        <?php endif; ?>
                        <span class="text-sm hidden md:inline-block font-medium"><i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition">ออกจากระบบ</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-white text-hdblue hover:bg-gray-100 px-3 py-1.5 rounded-md text-sm font-medium transition shadow-sm border border-transparent"><i class="fas fa-lock mr-1"></i> เข้าสู่ระบบเจ้าหน้าที่</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- Dashboard Summary Section -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-hdblue pl-3">ภาพรวมข้อมูลปีงบประมาณ</h2>
                <select id="fySelect" class="border-gray-300 rounded-md shadow-sm focus:border-hdblue focus:ring focus:ring-hdblue focus:ring-opacity-50 py-2 px-4" onchange="updateMonthSelect(); loadDashboard(); loadExcelTable();">
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8" id="dashboardCards">
                <!-- Summary cards will be injected here via JS -->
            </div>


            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-700">สถิติรายเดือน</h3>
                <div class="relative h-72 w-full">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Layout: Vertical Stack -->
        <div class="space-y-8">
            
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'hd_user'])): ?>
            <!-- Data Entry Form -->
            <div>
                <div class="glass-card rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-hdblue py-3 px-5 text-white font-semibold flex items-center">
                        <i class="fas fa-edit mr-2"></i> บันทึกข้อมูลประจำวัน
                    </div>
                    <form id="recordForm" class="p-6" onsubmit="submitForm(event)">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-5">
<div class="lg:col-span-2">
    <label for="record_date" class="block text-sm font-medium text-gray-700 mb-1">วันที่รับบริการ (จ.-ศ.)</label>
    <input type="text" id="record_date" name="record_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-hdblue focus:ring focus:ring-hdblue focus:ring-opacity-50 px-3 py-2 border bg-white">
</div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">มารับบริการ</label><input type="number" name="service_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ทำ HD</label><input type="number" name="hd_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Admit</label><input type="number" name="admit_count" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Refer OPD</label><input type="number" name="refer_opd" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Emergency</label><input type="number" name="emergency" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ส่ง ER</label><input type="number" name="refer_er" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">การให้เลือด</label><input type="number" name="blood_transfusion" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Off DLC</label><input type="number" name="off_dlc" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">off perm</label><input type="number" name="off_perm" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Insert DLC</label><input type="number" name="insert_dlc" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">น้ำ RO C/S bac.</label><input type="number" name="ro_cs_bacteria" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">น้ำ RO Endotoxin</label><input type="number" name="ro_endotoxin" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Counselling</label><input type="number" name="counselling" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ฉีดยา</label><input type="number" name="injection" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ล้างแผล</label><input type="number" name="wound_dressing" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">เย็บแผล</label><input type="number" name="suture" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">ตัดไหม</label><input type="number" name="remove_suture" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Hypotension</label><input type="number" name="hypotension" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Hypertension</label><input type="number" name="hypertension" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Muscle cramp</label><input type="number" name="muscle_cramp" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Headache</label><input type="number" name="headache" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Fever</label><input type="number" name="fever" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
<div><label class="block text-xs font-medium text-gray-700 mb-1">Fever หลังฟอก</label><input type="number" name="fever_after" min="0" value="0" required class="w-full rounded border-gray-300 px-2 py-2 border focus:ring-hdblue"></div>
</div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-hdblue hover:bg-hdblue-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-hdblue transition-colors duration-200">
                                <i class="fas fa-save mr-2 mt-1"></i> บันทึกข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Excel-like Summary Table -->
            <div>
                <div class="glass-card rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
                    <div class="bg-gray-50 border-b border-gray-200 py-3 px-5 text-gray-800 font-semibold flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span><i class="fas fa-table mr-2 text-hdblue"></i> ตารางสรุปรายเดือนและรายปีงบประมาณ</span>
                            <select id="monthSelect" class="border-gray-300 rounded-md shadow-sm focus:border-hdblue focus:ring focus:ring-hdblue focus:ring-opacity-50 py-1 px-2 text-sm" onchange="loadExcelTable()">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <button onclick="loadExcelTable()" class="text-sm text-hdblue hover:text-hdblue-dark">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                    </div>
                    <div class="overflow-x-auto flex-grow p-4">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100" id="excelTableHead">
                                <!-- Headers injected via JS -->
                            </thead>
                            <tbody id="excelTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Data rows injected via JS -->
                                <tr>
                                    <td class="px-4 py-8 text-center text-gray-500">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Records Table (Hidden, kept for logging logic if needed) -->
            <div class="hidden">
            
        </div>
    </main>

    <script>
        let myChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today if form exists
            const recordDateElem = document.getElementById('record_date');
            if (recordDateElem) {
                flatpickr("#record_date", {
                    locale: "th",
                    dateFormat: "Y-m-d",
                    defaultDate: "today",
                    altInput: true,
                    altFormat: "j F Y",
                    formatDate: (date, formatStr, locale) => {
                        if (formatStr === "j F Y") {
                            const d = date.getDate();
                            const m = locale.months.longhand[date.getMonth()];
                            const y = date.getFullYear() + 543;
                            return `${d} ${m} ${y}`;
                        }
                        return flatpickr.formatDate(date, formatStr);
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        const date = selectedDates[0];
                        if (date) {
                            const day = date.getDay();
                            if(day === 0 || day === 6) { // 0 = Sunday, 6 = Saturday
                                Swal.fire('คำเตือน', 'กรุณาเลือกวันทำการ (จันทร์ - ศุกร์) เท่านั้น', 'warning');
                                instance.clear();
                            }
                        }
                    }
                });
            }
            
            // Populate FY select from DB settings
            const defaultFy = <?= $default_fy ?>;
            const rangeStart = <?= $range_start ?>;
            const rangeEnd = <?= $range_end ?>;
            
            const fySelect = document.getElementById('fySelect');
            for(let i = rangeEnd; i >= rangeStart; i--) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = 'ปีงบประมาณ ' + (i + 543);
                if(i === defaultFy) option.selected = true;
                fySelect.appendChild(option);
            }

            // Populate month select with buddhist year
            const currentMonth = new Date().getMonth() + 1;
            updateMonthSelect();
            document.getElementById('monthSelect').value = currentMonth;

            initApp();

            // (Validation logic moved into flatpickr onChange)
        });

        async function initApp() {
            await loadDashboard();
            await loadExcelTable();
        }

        function updateMonthSelect() {
            const fyValue = parseInt(document.getElementById('fySelect').value);
            const fyBuddhist = fyValue + 543;
            const prevBuddhist = fyBuddhist - 1;
            
            const monthSelect = document.getElementById('monthSelect');
            const currentVal = monthSelect.value;
            monthSelect.innerHTML = `
                <option value="10">ตุลาคม ${prevBuddhist}</option>
                <option value="11">พฤศจิกายน ${prevBuddhist}</option>
                <option value="12">ธันวาคม ${prevBuddhist}</option>
                <option value="1">มกราคม ${fyBuddhist}</option>
                <option value="2">กุมภาพันธ์ ${fyBuddhist}</option>
                <option value="3">มีนาคม ${fyBuddhist}</option>
                <option value="4">เมษายน ${fyBuddhist}</option>
                <option value="5">พฤษภาคม ${fyBuddhist}</option>
                <option value="6">มิถุนายน ${fyBuddhist}</option>
                <option value="7">กรกฎาคม ${fyBuddhist}</option>
                <option value="8">สิงหาคม ${fyBuddhist}</option>
                <option value="9">กันยายน ${fyBuddhist}</option>
            `;
            if (currentVal) monthSelect.value = currentVal;
        }

        async function loadExcelTable() {
            const fy = document.getElementById('fySelect').value;
            const month = document.getElementById('monthSelect').value;
            
            try {
                const res = await fetch(`hd_api.php?action=get_excel_report&fy=${fy}&month=${month}`);
                const data = await res.json();
                
                if (data.success) {
                    const records = data.month_records;
                    const yearly = data.yearly_sums || {};
                    
                    const thead = document.getElementById('excelTableHead');
                    const tbody = document.getElementById('excelTableBody');
                    
                    let headHTML = '<tr><th class="px-4 py-2 border text-left font-bold text-gray-700 w-64 bg-gray-200">รายการ</th>';
                    if (records.length === 0) {
                        headHTML += '<th class="px-4 py-2 border text-center font-bold text-gray-700">ไม่มีข้อมูล</th></tr>';
                        thead.innerHTML = headHTML;
                        tbody.innerHTML = `<tr><td colspan="2" class="px-4 py-8 text-center text-gray-500">ยังไม่มีการบันทึกข้อมูลในเดือนที่เลือก</td></tr>`;
                        return;
                    }

                    records.forEach(r => {
                        const d = new Date(r.record_date);
                        headHTML += `<th class="px-2 py-2 border text-center font-semibold text-gray-700">วันที่ ${d.getDate()}</th>`;
                    });
                    headHTML += '<th class="px-4 py-2 border text-center font-bold text-hdblue bg-blue-50">รวมรายเดือน</th>';
                    headHTML += '<th class="px-4 py-2 border text-center font-bold text-hdblue bg-blue-50">รวมทั้งปีงบประมาณ</th></tr>';
                    thead.innerHTML = headHTML;
                    
                    const tableMetrics = [
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
    { key: 'fever', label: 'Fever' },
    { key: 'fever_after', label: 'Fever หลังฟอก' }
];

let bodyHTML = '';
tableMetrics.forEach(m => {
                        bodyHTML += `<tr><td class="px-4 py-2 border text-sm text-gray-800 font-medium whitespace-nowrap bg-gray-50">${m.label}</td>`;
                        
                        let monthlySum = 0;
                        records.forEach(r => {
                            const val = parseInt(r[m.key]) || 0;
                            monthlySum += val;
                            const displayVal = (val === 0 && m.key !== 'service_count') ? '-' : val;
                            const boldClass = (val > 0 && m.key !== 'service_count') ? 'font-bold text-orange-600' : 'text-gray-600';
                            bodyHTML += `<td class="px-2 py-2 border text-sm text-center ${boldClass}">${displayVal}</td>`;
                        });
                        
                        const yearlySum = parseInt(yearly['total_' + m.key]) || 0;
                        bodyHTML += `<td class="px-4 py-2 border text-sm text-center font-bold text-hdblue bg-blue-50">${monthlySum}</td>`;
                        bodyHTML += `<td class="px-4 py-2 border text-sm text-center font-bold text-hdblue bg-blue-50">${yearlySum}</td></tr>`;
                    });
                    
                    tbody.innerHTML = bodyHTML;
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function submitForm(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'save_record');

            try {
                const res = await fetch('hd_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    e.target.reset();
                    const recordDateElem = document.getElementById('record_date');
                    if (recordDateElem && recordDateElem._flatpickr) {
                        recordDateElem._flatpickr.setDate(new Date());
                    } else if (recordDateElem) {
                        recordDateElem.valueAsDate = new Date();
                    }
                    initApp();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }

        async function deleteRecord(id) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณไม่สามารถกู้คืนข้อมูลนี้ได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_record');
                formData.append('id', id);

                const res = await fetch('hd_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if(data.success) {
                    Swal.fire('ลบแล้ว!', data.message, 'success');
                    initApp();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            }
        }

        async function loadTable() {
            const tbody = document.getElementById('tableBody');
            try {
                const res = await fetch('hd_api.php?action=get_records');
                const data = await res.json();
                
                if(data.success) {
                    tbody.innerHTML = '';
                    if(data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูล</td></tr>';
                        return;
                    }
                    
                    data.data.forEach(item => {
                        // Format date to Thai format
                        const dateObj = new Date(item.record_date);
                        const thaiDate = dateObj.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
                        
                        // Format timestamp
                        const updatedObj = new Date(item.updated_at);
                        const updatedTime = updatedObj.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

                        const tr = document.createElement('tr');
                        tr.className = "hover:bg-gray-50 transition-colors";
                        tr.innerHTML = `
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-900 font-medium">${thaiDate}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.service_count}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-center font-bold ${item.admit_count > 0 ? 'text-orange-600' : 'text-gray-500'}">${item.admit_count}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-center ${item.refer_count > 0 ? 'text-red-600 font-bold' : 'text-gray-500'}">${item.refer_count}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.wound_dressing}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.epo_injection}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.transfer_line_change}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.emergency_refer}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.new_training}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.retraining}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.pet_collection}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.ktv_collection}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.pd_fluid_check}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.peritonitis}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-700 text-center">${item.exitsite_infection}</td>
                            <td class="px-2 py-3 whitespace-nowrap text-center text-xs font-medium">
                                <button onclick="deleteRecord(${item.id})" class="text-red-500 hover:text-red-700 px-1 py-1 rounded">
                                    <i class="fas fa-trash-alt"></i> ลบ
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            }
        }

        async function loadDashboard() {
            const fy = document.getElementById('fySelect').value;
            try {
                const res = await fetch(`hd_api.php?action=get_dashboard_data&fy=${fy}`);
                const data = await res.json();
                
                if(data.success) {
            
                    const s = data.summary;
                    
                    const dbMetrics = [
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
    { key: 'total_fever', label: 'Fever', val: s.total_fever, color: 'red', icon: 'temperature-high' },
    { key: 'total_fever_after', label: 'Fever หลังฟอก', val: s.total_fever_after, color: 'red', icon: 'temperature-high' }
];

let cardsHTML = '';
dbMetrics.forEach(m => {
                        const val = parseInt(m.val) || 0;
                        let colorClass = 'border-hdblue';
                        let bgIconClass = 'bg-green-100 text-hdblue';
                        
                        if (m.color === 'orange') { colorClass = 'border-orange-500'; bgIconClass = 'bg-orange-100 text-orange-600'; }
                        if (m.color === 'red') { colorClass = 'border-red-500'; bgIconClass = 'bg-red-100 text-red-600'; }
                        if (m.color === 'blue') { colorClass = 'border-blue-500'; bgIconClass = 'bg-blue-100 text-blue-600'; }
                        if (m.color === 'purple') { colorClass = 'border-purple-500'; bgIconClass = 'bg-purple-100 text-purple-600'; }
                        if (m.color === 'teal') { colorClass = 'border-teal-500'; bgIconClass = 'bg-teal-100 text-teal-600'; }
                        if (m.color === 'indigo') { colorClass = 'border-indigo-500'; bgIconClass = 'bg-indigo-100 text-indigo-600'; }
                        if (m.color === 'cyan') { colorClass = 'border-cyan-500'; bgIconClass = 'bg-cyan-100 text-cyan-600'; }

                        cardsHTML += `
                            <div class="glass-card rounded-xl p-4 border-l-4 ${colorClass}">
                                <div class="flex items-center">
                                    <div class="p-2 rounded-full ${bgIconClass} mr-3">
                                        <i class="fas fa-${m.icon} text-lg w-5 h-5 flex items-center justify-center"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-gray-500 font-medium leading-tight">${m.label}</p>
                                        <p class="text-xl font-bold text-gray-800">${val.toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('dashboardCards').innerHTML = cardsHTML;

                    // Render Chart
                    renderChart(data.chart_data);
                }
            } catch (err) {
                console.error("Dashboard error:", err);
            }
        }

        function renderChart(chartData) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            
            const labels = chartData.map(d => monthNames[d.month - 1] + ' ' + (d.year + 543).toString().substring(2));
            const serviceData = chartData.map(d => d.monthly_service);
const hdData = chartData.map(d => d.monthly_hd);

            if (myChart) {
                myChart.destroy();
            }

            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'รับบริการ (คน)',
                            data: serviceData,
                            backgroundColor: 'rgba(0, 96, 63, 0.8)',
                            borderColor: 'rgba(0, 96, 63, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'ทำ HD (คน)',
                            data: hdData,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: "'Prompt', sans-serif" } } },
                        tooltip: {
                            titleFont: { family: "'Prompt', sans-serif" },
                            bodyFont: { family: "'Prompt', sans-serif" }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: { font: { family: "'Prompt', sans-serif" } }
                        },
                        x: {
                            ticks: { font: { family: "'Prompt', sans-serif" } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
