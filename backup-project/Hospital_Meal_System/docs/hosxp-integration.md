# HOSxP Integration

## Mode

ระบบนี้ออกแบบให้ HOSxP เป็น read-only source of truth สำหรับข้อมูลผู้ป่วยใน

## Data that should be synced

- HN / AN
- ชื่อผู้ป่วย
- ward / room / bed
- admission status
- diagnosis
- weight / height / BMI
- NPO
- allergy
- diet order

## Recommended integration pattern

1. ใช้บัญชีฐานข้อมูลแบบ read-only
2. จำกัด IP ที่เข้าถึง HOSxP
3. อ่านเฉพาะ view หรือ stored query ที่อนุมัติแล้ว
4. sync เข้า cache ภายในระบบอาหาร
5. บันทึก sync logs ทุกครั้ง

## Failure handling

- ถ้า sync ล้มเหลว ระบบต้องยังเปิดแดชบอร์ดจาก cache เดิมได้
- แสดงเวลาซิงก์ล่าสุด
- แจ้งเตือนเมื่อ sync failure เกิดซ้ำ

## Security

- ห้าม expose DB HOSxP ออกอินเทอร์เน็ต
- ห้ามเก็บรหัสผ่านจริงใน source code
- ใช้ secret manager หรือ environment variable
