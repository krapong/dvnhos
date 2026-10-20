# AGENTS.md

## Project
ระบบติดตามยาฉุกเฉินและแจ้งเตือนวันหมดอายุสำหรับโรงพยาบาล

## Scope
ระบบนี้ใช้กับการจัดการข้อมูลระดับหน่วยงาน ไม่ได้ผูกกับ HOSxP โดยตรง

### ข้อมูลหลัก
- ยา
- เวชภัณฑ์
- กล่องยา
- LAB / Reagent / Test kit

## Core Features
- Login + Role
- Dashboard
- จัดการรายการ
- จัดการ Lot / วันหมดอายุ
- Import Excel
- Export Excel/PDF
- Telegram Alert
- Alert Log
- ตั้งการแจ้งเตือน 30/60/90 วัน

## Tech
- Node.js
- HTML / CSS / JavaScript
- MySQL หรือ PostgreSQL ตาม environment
- Apache 2.4
- Telegram Bot API

## Rules
- ห้ามลบไฟล์เดิมโดยไม่จำเป็น
- แก้ไขทีละ phase
- ทุก migration ต้องไม่ทำให้ข้อมูลเดิมหาย
- ถ้ามี schema ใหม่ ให้เพิ่ม migration หรือรองรับย้อนหลังเสมอ
- ถ้าต้องอ้างอิงเวลา ให้ใช้รูปแบบ `YYYY-MM-DD`
- Telegram token และข้อมูลลับเก็บใน `.env` เท่านั้น

## Working Style
ก่อนลงมือแก้ ให้:
1. สำรวจโครงสร้างเดิม
2. สรุปผลกระทบ
3. เสนอแผน
4. ค่อยแก้ทีละจุด

## Phases

### Phase 1: ตรวจและจัดโครงสร้าง
- ตรวจ flow ระบบทั้งหมด
- แยกส่วน frontend / backend / database
- ระบุจุดที่ยังขาด
- วางแผนการแก้ไขเป็นลำดับ

### Phase 2: ฐานข้อมูล
- ออกแบบ schema สำหรับ
  - categories
  - items
  - item_lots
  - alert_settings
  - alert_logs
  - users
  - roles
- เขียน migration สำหรับ MariaDB / MySQL / PostgreSQL ให้รองรับข้อมูลเดิม

### Phase 3: Dashboard
- สร้าง dashboard แสดง
  - จำนวนทั้งหมด
  - หมดอายุแล้ว
  - ใกล้หมดอายุ 30/60/90 วัน
  - แยกตามประเภท
  - แยกตามหน่วยงาน
  - ตารางรายการแจ้งเตือน

### Phase 4: Telegram
- เพิ่มระบบแจ้งเตือน Telegram
- อ่าน token / chat_id จาก `.env`
- ตรวจรายการใกล้หมดอายุ
- ส่งข้อความแจ้งเตือนอัตโนมัติ
- บันทึกผลลง `alert_logs`

### Phase 5: Export / Import
- เพิ่ม import Excel
- เพิ่ม export Excel / PDF
- ตรวจรูปแบบข้อมูลก่อนนำเข้า

### Phase 6: Audit / Log / Safety
- เพิ่ม audit log ทุกการเปลี่ยนแปลง
- เพิ่ม validation ก่อนบันทึก
- ป้องกันการลบข้อมูลสำคัญแบบเผลอ
- เพิ่ม test สำหรับ logic สำคัญ

## Notes for Codex
- ถ้าเริ่มงานใหม่ ให้ยึด AGENTS.md เป็นแนวทางหลัก
- ถ้าข้อกำหนดในภาพกับโค้ดจริงไม่ตรงกัน ให้ยึดโค้ดจริงและรายงานความต่างก่อนแก้
- ถ้าต้องเสนอการปรับใหญ่ ให้เสนอแผนก่อนเสมอ
