# เปิดระบบอัตโนมัติบน Windows

ระบบเว็บใช้ Waitress และเปิดเฉพาะ http://127.0.0.1:8088

## เปิดเอง
ดับเบิลคลิก `Start-System.cmd` ไม่ต้องเปิด PowerShell ค้างไว้

## เปิดอัตโนมัติหลังไฟดับ/รีบูต
เปิด PowerShell ด้วย Run as administrator แล้วรัน:

```powershell
cd D:\laragon\www\hospital_backup_center
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\Install-Autostart.ps1
```

ตัวติดตั้งสร้างงานชื่อ HospitalBackupCenter-Autostart ใน Windows Task Scheduler ใช้บัญชีผู้ใช้เดิมแบบ S4U สิทธิ์ Limited ไม่เก็บรหัสผ่าน Windows มี trigger ตอนบูต (หน่วง 30 วินาที) และตอนเข้าสู่ระบบ ทำงานต่อเนื่องโดยไม่จำกัดเวลา และเริ่มใหม่เมื่อโปรแกรมหยุดผิดปกติ Python supervisor ป้องกันเปิดซ้ำและเรียกเว็บใหม่หลัง process หยุด ไม่เปลี่ยนเวลาสำรองบนเซิร์ฟเวอร์

เครื่องต้องเห็นไดรฟ์ D: และโฟลเดอร์โปรเจกต์หลังบูต ถ้าไดรฟ์เข้ารหัสต้องปลดล็อกก่อนใช้งาน

## ตรวจสอบ

```powershell
Get-ScheduledTask -TaskName HospitalBackupCenter-Autostart
Get-ScheduledTaskInfo -TaskName HospitalBackupCenter-Autostart
```

เปิด http://127.0.0.1:8088/login หลังบูต งานต้องอยู่สถานะ Running
Log ของโปรแกรมอยู่ `data/runtime/server.log` และ `data/runtime/supervisor.log` ส่วนประวัติการใช้งานเก็บใน SQLite ตามเดิม

## ไฟดับแล้วเครื่องเปิดเอง
ต้องตั้ง BIOS/UEFI หัวข้อเช่น Restore on AC Power Loss / AC Back / After Power Failure เป็น Power On ชื่อเมนูต่างกันตามยี่ห้อ การตั้งนี้ทำจากสคริปต์ Windows ทั่วไปไม่ได้ แนะนำใช้ UPS เพื่อให้ Windows ปิดอย่างถูกต้อง

ตารางสำรองวันศุกร์ 16:00 และเก็บ 3 ชุดทำงานที่เซิร์ฟเวอร์ปลายทาง แม้เครื่องศูนย์กลางปิดอยู่ แต่หน้าเว็บต้องรอเครื่องนี้เปิดและเริ่มระบบ

## ปิดการเริ่มอัตโนมัติ
ใช้ Task Scheduler ปิดงาน HospitalBackupCenter-Autostart หากต้องหยุดเว็บด้วย ให้หยุด process python ที่รัน serve.py ของโปรเจกต์นี้โดยตรวจตำแหน่งก่อน ไม่ควรหยุด Python ทั้งเครื่อง
