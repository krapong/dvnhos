const xlsx = require('xlsx');
const mysql = require('mysql2/promise');

const monthMapping = {
    'ต.ค.68': { month: 10, year: 2025 },
    'พ.ย.68': { month: 11, year: 2025 },
    'ธ.ค.68': { month: 12, year: 2025 },
    'ม.ค.69': { month: 1, year: 2026 },
    'ก.พ.69': { month: 2, year: 2026 },
    'มี.ค.69': { month: 3, year: 2026 },
    'เม.ย.69': { month: 4, year: 2026 },
    'พ.ค.69': { month: 5, year: 2026 },
    'มิ.ย.69': { month: 6, year: 2026 },
    'ก.ค.69': { month: 7, year: 2026 },
    'ส.ค.69': { month: 8, year: 2026 },
    'ก.ย.69': { month: 9, year: 2026 }
};

const metricsMap = {
    'จำนวนผู้รับริการ': 'service_count',
    'ทำ HD': 'hd_count',
    'จำนวนผูู้้ทำ HD': 'hd_count',
    'จำนวนผู้ทำ HD': 'hd_count',
    'Admit': 'admit_count',
    'Refer OPD': 'refer_opd',
    'Emergency': 'emergency',
    'ส่ง ER': 'refer_er',
    'การให้เลือด': 'blood_transfusion',
    'Off DLC': 'off_dlc',
    'off perm': 'off_perm',
    'InsertDLC': 'insert_dlc',
    'ส่งน้ำ RO ตรวจ C/Sbacteria': 'ro_cs_bacteria',
    'ส่งน้ำ RO ตรวจ Endotoxin': 'ro_endotoxin',
    'Counselling': 'counselling',
    'ฉีดยา': 'injection',
    'ล้างแผล': 'wound_dressing',
    'เย็บแผล': 'suture',
    'ตัดไหม': 'remove_suture',
    'Hypotension': 'hypotension',
    'hypertension': 'hypertension',
    'Muscle cramp': 'muscle_cramp',
    'headache': 'headache',
    'Fever': 'fever'
};

function getDayFromValue(val) {
    if (typeof val === 'number') {
        if (val >= 1 && val <= 31) {
            return val;
        }
        if (val > 40000) {
            // Excel serial date format
            const utc_days = Math.floor(val - 25569);
            const utc_value = utc_days * 86400;
            const date = new Date(utc_value * 1000);
            return date.getUTCDate();
        }
    }
    return null;
}

async function importData() {
    console.log("Starting robust HD data import...");
    
    const connection = await mysql.createConnection({
        host: 'YOUR_PRIVATE_SERVER',
        user: 'sa',
        password: 'CHANGE_ME_BEFORE_USE',
        database: 'pd_dashboard_db'
    });
    console.log("Connected to MySQL Database.");

    const workbook = xlsx.readFile('รายงานประจำวัน หน่วย Hemodialssis ปี 2569.xlsx');
    const sheetNames = workbook.SheetNames;

    let recordsProcessed = 0;

    for (const sheetName of sheetNames) {
        if (!monthMapping[sheetName]) {
            console.log(`Skipping unknown sheet: ${sheetName}`);
            continue;
        }
        const { month, year } = monthMapping[sheetName];
        const worksheet = workbook.Sheets[sheetName];
        const data = xlsx.utils.sheet_to_json(worksheet, { header: 1 });

        if (data.length < 5) continue;

        let dateRowIndex = -1;
        // Robustly search for the date row
        for (let i = 0; i < Math.min(15, data.length); i++) {
            const row = data[i];
            if (!row) continue;
            let dayCount = 0;
            for (let col = 1; col < row.length; col++) {
                const val = row[col];
                const day = getDayFromValue(val);
                if (day !== null) {
                    dayCount++;
                }
            }
            if (dayCount >= 20) {
                dateRowIndex = i;
                break;
            }
        }
        
        if (dateRowIndex === -1) {
            console.log(`❌ Could not find date row in sheet ${sheetName}`);
            continue;
        }

        console.log(`Found date row at index ${dateRowIndex} in sheet ${sheetName}`);
        const dateRow = data[dateRowIndex];
        
        // Map day number to column index
        const dayColMap = {};
        for (let col = 1; col < dateRow.length; col++) {
            const val = dateRow[col];
            const day = getDayFromValue(val);
            if (day !== null) {
                dayColMap[day] = col;
            }
        }

        // Loop through each day of the month
        for (const [day, colIndex] of Object.entries(dayColMap)) {
            const recordDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const fiscal_year = month >= 10 ? year + 1 : year;
            
            const rowData = {
                service_count: 0, hd_count: 0, admit_count: 0, refer_opd: 0, emergency: 0, refer_er: 0, 
                blood_transfusion: 0, off_dlc: 0, off_perm: 0, insert_dlc: 0, ro_cs_bacteria: 0, ro_endotoxin: 0, 
                counselling: 0, injection: 0, wound_dressing: 0, suture: 0, remove_suture: 0, hypotension: 0, 
                hypertension: 0, muscle_cramp: 0, headache: 0, fever: 0
            };

            // Read the metric rows
            for (let i = dateRowIndex; i < data.length; i++) {
                if (!data[i] || data[i].length === 0) continue;
                
                let rowHeader = String(data[i][0] || '').trim();
                // Normalize "จำนวนผู้รับบริการ" row header in cases where it is empty but it's the date row itself (some sheets put dates on the service count row)
                if (rowHeader === '' && i === dateRowIndex) {
                    rowHeader = 'จำนวนผู้รับริการ';
                }

                if (metricsMap[rowHeader]) {
                    const dbKey = metricsMap[rowHeader];
                    rowData[dbKey] = parseInt(data[i][colIndex]) || 0;
                }
            }

            const query = `
                INSERT INTO hd_daily_records (
                    record_date, fiscal_year, service_count, hd_count, admit_count, refer_opd, emergency, refer_er, blood_transfusion, off_dlc, off_perm, insert_dlc, ro_cs_bacteria, ro_endotoxin, counselling, injection, wound_dressing, suture, remove_suture, hypotension, hypertension, muscle_cramp, headache, fever
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    service_count=VALUES(service_count), hd_count=VALUES(hd_count), admit_count=VALUES(admit_count), refer_opd=VALUES(refer_opd), emergency=VALUES(emergency), refer_er=VALUES(refer_er), blood_transfusion=VALUES(blood_transfusion), off_dlc=VALUES(off_dlc), off_perm=VALUES(off_perm), insert_dlc=VALUES(insert_dlc), ro_cs_bacteria=VALUES(ro_cs_bacteria), ro_endotoxin=VALUES(ro_endotoxin), counselling=VALUES(counselling), injection=VALUES(injection), wound_dressing=VALUES(wound_dressing), suture=VALUES(suture), remove_suture=VALUES(remove_suture), hypotension=VALUES(hypotension), hypertension=VALUES(hypertension), muscle_cramp=VALUES(muscle_cramp), headache=VALUES(headache), fever=VALUES(fever)
            `;

            try {
                await connection.execute(query, [
                    recordDate, fiscal_year, rowData.service_count, rowData.hd_count, rowData.admit_count, rowData.refer_opd, rowData.emergency, rowData.refer_er, rowData.blood_transfusion, rowData.off_dlc, rowData.off_perm, rowData.insert_dlc, rowData.ro_cs_bacteria, rowData.ro_endotoxin, rowData.counselling, rowData.injection, rowData.wound_dressing, rowData.suture, rowData.remove_suture, rowData.hypotension, rowData.hypertension, rowData.muscle_cramp, rowData.headache, rowData.fever
                ]);
                recordsProcessed++;
            } catch (err) {
                console.error(`Failed to insert for ${recordDate}:`, err.message);
            }
        }
    }

    console.log(`\nImport completed! Processed ${recordsProcessed} records.`);
    await connection.end();
}

importData().catch(console.error);
