const xlsx = require('xlsx');

const workbook = xlsx.readFile('รายงานประจำวัน หน่วย Hemodialysis ปีงบประมาณ 2569.xlsx');
const sheetNames = workbook.SheetNames;

const uniqueHeaders = new Set();

for (const sheetName of sheetNames) {
    const worksheet = workbook.Sheets[sheetName];
    const data = xlsx.utils.sheet_to_json(worksheet, { header: 1 });
    
    for (let i = 0; i < data.length; i++) {
        if (data[i] && data[i][0]) {
            uniqueHeaders.add(String(data[i][0]).trim());
        }
    }
}

console.log("Unique Headers:", Array.from(uniqueHeaders));
