# Architecture

## ภาพรวม

Hospital Meal System เป็นเว็บแอปแบบ standalone ที่รับข้อมูลผู้ป่วยจาก HOSxP แบบอ่านอย่างเดียว แล้วเก็บสำเนา cache เฉพาะข้อมูลที่จำเป็นสำหรับงานอาหารผู้ป่วย

```text
HOSxP read-only
  -> Sync Agent / API bridge
  -> Meal System cache DB
  -> Vue dashboard / APIs / Telegram
```

## หลักการออกแบบ

- แยกฐานข้อมูล HOSxP ออกจากฐานข้อมูลระบบอาหาร
- ใช้ `patients_cache` และ `admissions` เป็นข้อมูลทำงานหลัก
- เก็บ audit log ทุกครั้งที่มีการแก้ไข order
- ใช้ order workflow เพื่อแยกงานพยาบาล โภชนาการ และครัว
- รองรับการขยายไปเป็น production ด้วย queue, scheduler, และ external notification

## โมดูลหลัก

- Patient cache and sync
- Meal ordering
- Diet templates
- Nutrition review
- Kitchen production
- Telegram notifications
- Daily summary and executive dashboard

## Non-functional goals

- Read-only integration
- Traceability
- Patient safety
- Role-based access
- Easy deployment on Apache + MariaDB
