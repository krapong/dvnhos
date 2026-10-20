# OPD Dashboard

แดชบอร์ดสรุปการให้บริการผู้ป่วยนอกจาก HOSxP พร้อมข้อมูลคลินิกเวชศาสตร์ครอบครัวและชุมชน

## การติดตั้ง

1. ติดตั้งบนเว็บเซิร์ฟเวอร์ที่รองรับ PHP และ PDO MySQL เช่น Apache/XAMPP
2. สร้างไฟล์ config จาก `config/opd_dashboard_config.example.php` และเก็บไว้นอก document root
3. กำหนด `OPD_CONFIG_FILE` ให้ชี้ไปยังไฟล์ config จริง และใช้บัญชีฐานข้อมูลที่อ่านข้อมูลได้เท่านั้น
4. กำหนด private directory ที่เว็บเซิร์ฟเวอร์เขียนได้ โดยเก็บไว้นอก document root
5. เปิด `opd_dashboard.html` ผ่าน HTTP/HTTPS

ตัวอย่างสำหรับ XAMPP: `http://localhost:8080/opd_dashboard/opd_dashboard.html`

ดูรายละเอียดหัวข้อคลินิกเพิ่มเติมใน [clinic12-README.md](clinic12-README.md)

## ไฟล์หลัก

- `opd_dashboard.html`: หน้าจอแดชบอร์ด
- `api.php`: API สำหรับ OPD
- `clinic12.js`: หน้าจอคลินิกหัวข้อ 12
- `clinic12-api.php`: API สำหรับคลินิกหัวข้อ 12

Repository นี้ไม่รวมฐานข้อมูลผู้ป่วย ข้อมูลสถิติขณะใช้งาน หรือรหัสผ่านจริง การใช้งานต้องเชื่อมต่อฐานข้อมูลที่ได้รับอนุญาตและจำกัดการเข้าถึงแดชบอร์ดตามสิทธิ์ของหน่วยงาน
