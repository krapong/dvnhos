const xlsx = require('xlsx');
const mysql = require('mysql2/promise');

const monthMapping = {
    'ตุลาคม': { month: 10, year: 2025 },
    'พฤศจิกายน': { month: 11, year: 2025 },
    'ธันวาคม': { month: 12, year: 2025 },
    'มกราคม': { month: 1, year: 2026 },
    'กุมภาพันธ์': { month: 2, year: 2026 },
    'มีนาคม': { month: 3, year: 2026 },
    'เมษายน': { month: 4, year: 2026 },
    'พฤษภาคม': { month: 5, year: 2026 },
    'มิถุนายน': { month: 6, year: 2026 },
    'กรกฎาคม': { month: 7, year: 2026 },
    'สิงหาคม': { month: 8, year: 2026 },
    'กันยายน': { month: 9, year: 2026 }
};

async function importData() {
    console.log("Starting data import...");
    
    // Connect to Database
    const connection = await mysql.createConnection({
        host: 'YOUR_PRIVATE_SERVER',
        user: 'sa',
        password: 'CHANGE_ME_BEFORE_USE',
        database: 'pd_dashboard_db'
    });

    console.log("Connected to MySQL Database.");

    const workbook = xlsx.readFile('รายงานประจำวัน งานล้างไตทางช่องท้อง ปีงบประมาณ 2569.xlsx');
    const sheetNames = workbook.SheetNames;

    let recordsProcessed = 0;

    for (const sheetName of sheetNames) {
        if (!monthMapping[sheetName]) continue;

        const { month, year } = monthMapping[sheetName];
        const worksheet = workbook.Sheets[sheetName];
        const data = xlsx.utils.sheet_to_json(worksheet, { header: 1 });

        // Ensure we have enough rows
        if (data.length < 5) continue;

        const headers = data[2] || [];
        const serviceCounts = data[3] || [];
        const admitCounts = data[4] || [];
        const referCounts = data[5] || [];
        const woundCounts = data[6] || [];
        const epoCounts = data[7] || [];
        const transferCounts = data[8] || [];
        const emergencyCounts = data[9] || [];
        const newTrainingCounts = data[10] || [];
        const retrainingCounts = data[11] || [];
        const petCounts = data[12] || [];
        const ktvCounts = data[13] || [];
        const pdFluidCounts = data[14] || [];
        const peritonitisCounts = data[15] || [];
        const exitsiteCounts = data[16] || [];

        for (let i = 1; i < headers.length; i++) {
            const headerStr = headers[i];
            
            if (headerStr && typeof headerStr === 'string' && headerStr.startsWith('วันที่')) {
                const dayStr = headerStr.replace('วันที่', '').trim();
                const day = parseInt(dayStr);
                
                if (isNaN(day)) continue;

                // Build Date (YYYY-MM-DD)
                const recordDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                
                // Parse values
                const service = parseInt(serviceCounts[i]) || 0;
                const admit = parseInt(admitCounts[i]) || 0;
                const refer = parseInt(referCounts[i]) || 0;
                const wound = parseInt(woundCounts[i]) || 0;
                const epo = parseInt(epoCounts[i]) || 0;
                const transfer = parseInt(transferCounts[i]) || 0;
                const emergency = parseInt(emergencyCounts[i]) || 0;
                const newTraining = parseInt(newTrainingCounts[i]) || 0;
                const retraining = parseInt(retrainingCounts[i]) || 0;
                const pet = parseInt(petCounts[i]) || 0;
                const ktv = parseInt(ktvCounts[i]) || 0;
                const pdFluid = parseInt(pdFluidCounts[i]) || 0;
                const peritonitis = parseInt(peritonitisCounts[i]) || 0;
                const exitsite = parseInt(exitsiteCounts[i]) || 0;

                // Calculate fiscal year
                const fiscal_year = month >= 10 ? year + 1 : year;

                // Insert into DB
                const query = `
                    INSERT INTO pd_daily_records (
                        record_date, fiscal_year, service_count, admit_count, refer_count, wound_dressing, epo_injection, transfer_line_change, emergency_refer, new_training, retraining, pet_collection, ktv_collection, pd_fluid_check, peritonitis, exitsite_infection
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        service_count = VALUES(service_count), 
                        admit_count = VALUES(admit_count),
                        refer_count = VALUES(refer_count),
                        wound_dressing = VALUES(wound_dressing),
                        epo_injection = VALUES(epo_injection),
                        transfer_line_change = VALUES(transfer_line_change),
                        emergency_refer = VALUES(emergency_refer),
                        new_training = VALUES(new_training),
                        retraining = VALUES(retraining),
                        pet_collection = VALUES(pet_collection),
                        ktv_collection = VALUES(ktv_collection),
                        pd_fluid_check = VALUES(pd_fluid_check),
                        peritonitis = VALUES(peritonitis),
                        exitsite_infection = VALUES(exitsite_infection)
                `;
                
                try {
                    await connection.execute(query, [
                        recordDate, fiscal_year, service, admit, refer, wound, epo, transfer, emergency, newTraining, retraining, pet, ktv, pdFluid, peritonitis, exitsite
                    ]);
                    console.log(`Inserted/Updated: ${recordDate}`);
                    recordsProcessed++;
                } catch (err) {
                    console.error(`Failed to insert for ${recordDate}:`, err.message);
                }
            }
        }
    }

    console.log(`\nImport completed! Processed ${recordsProcessed} records.`);
    await connection.end();
}

importData().catch(console.error);
