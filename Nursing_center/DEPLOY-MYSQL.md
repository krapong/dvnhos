# ติดตั้ง Nursing Command Center บนเซิร์ฟเวอร์ภายใน

## ข้อกำหนด

- PHP 8.1 ขึ้นไป พร้อม pdo_mysql, mbstring, session, json
- MySQL 8.x หรือ MariaDB 10.6 ขึ้นไป (รอบนี้ทดสอบจริงด้วย MySQL 8.4)
- Apache 2.4 พร้อม mod_rewrite และ AllowOverride All หรือ Nginx + PHP-FPM
- ให้บริการผ่าน HTTPS สำหรับผู้ใช้ในเครือข่ายภายใน

## ย้ายระบบและบัญชีเดิม

1. แตก `nursing-center-mysql-deploy.zip` ลงโฟลเดอร์เว็บบนเซิร์ฟเวอร์ ใช้เป็น DocumentRoot หรือโฟลเดอร์ย่อย เช่น `/nursing/` ได้
2. สร้างฐานข้อมูลว่างชื่อ `nursing_center` เลือก charset `utf8mb4` และ collation `utf8mb4_unicode_ci`
3. สร้างผู้ใช้ฐานข้อมูลเฉพาะระบบนี้ ให้สิทธิ์ SELECT, INSERT, UPDATE, DELETE บนฐานข้อมูลนี้เท่านั้น ใช้บัญชีติดตั้งแยกสำหรับสร้างตาราง
4. นำเข้าไฟล์สำรอง `.private/nursing-center-mysql.sql` จากเครื่องเดิมด้วย phpMyAdmin หรือ mysql CLI ลงฐานข้อมูล **ว่าง** ไฟล์นี้มีตาราง บัญชีเดิม รหัสผ่านแบบ hash บทบาท และรายงานทั้งหมด ไม่ต้องรัน schema.sql หรือ setup-users.php ซ้ำ
5. คัดลอก `database.example.php` เป็น `.private/database.php` แล้วตั้ง host, port, database, username, password ตามเซิร์ฟเวอร์จริง ห้ามใช้ค่าพอร์ต 3308 ของเครื่องพัฒนาแทนพอร์ตเซิร์ฟเวอร์โดยอัตโนมัติ
6. ตั้งสิทธิ์ไฟล์ให้ PHP อ่าน config ได้ ห้ามผู้ใช้เว็บดาวน์โหลด `.private`, PHP source, SQL หรือไฟล์สำรอง Apache ต้องอ่าน `.htaccess` ได้จริง การ rewrite ทุกคำขอผ่าน router.php เป็นสิ่งจำเป็น
7. เปิด `/login` หรือ `/nursing/login` แล้วใช้บัญชีและรหัสผ่านเดิมที่ใช้อยู่ได้ทันที
8. หากมีรายงานเก่าในเบราว์เซอร์ ให้เข้าสู่บัญชีเดิมจากเครื่องและ browser origin เดิม เปิดหน้าบันทึก แล้วกด “นำเข้าฉบับร่างเดิมจากเครื่องนี้” ก่อนย้ายไป URL ใหม่ ข้อมูลเดิมในเบราว์เซอร์ไม่ถูกลบ และข้อมูลส่วนกลางที่มีอยู่แล้วจะไม่ถูกเขียนทับ

หากติดตั้งใหม่โดยไม่มีข้อมูลเดิม ให้ import `schema.sql` ด้วยบัญชีติดตั้ง จากนั้นตั้ง config และรัน `php setup-users.php` เพื่อสร้าง 17 บัญชีเริ่มต้น รหัสผ่านสุ่มอยู่ใน `.private/initial-accounts.txt` ให้ส่งแก่ผู้ใช้ผ่านช่องทางภายในและนำไฟล์ออกหลังแจกจ่ายแล้ว

สามารถใช้ environment variables แทน config ได้: `NCC_DB_HOST`, `NCC_DB_PORT`, `NCC_DB_DATABASE`, `NCC_DB_USERNAME`, `NCC_DB_PASSWORD` และกำหนด `NCC_BASE_PATH=/nursing` หากเซิร์ฟเวอร์ไม่ส่ง SCRIPT_NAME ของ router.php ตามจริง

## Apache

เปิด mod_rewrite และตั้ง `AllowOverride All` ใน Directory ของโปรเจกต์ ใช้ `.htaccess` ที่ให้มา ห้ามตั้ง fallback ให้ส่งไฟล์ HTML/JS โดยข้าม router เพราะจะข้ามการตรวจสิทธิ์

## Nginx (ตัวอย่างติดตั้งที่ root ของ virtual host)

ปรับ root และ socket ให้ตรงกับเครื่องจริง ห้ามนำไปใช้เป็นโฟลเดอร์ย่อยโดยไม่ปรับ routing และ SCRIPT_NAME

```nginx
server {
    listen 80;
    server_name nursing.internal;
    root /var/www/nursing;
    location ~ /\. { deny all; }
    location / { rewrite ^ /router.php last; }
    location = /router.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/router.php;
        fastcgi_param SCRIPT_NAME /router.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ \.php$ { return 404; }
}
```

เพิ่ม TLS ตามมาตรฐานเซิร์ฟเวอร์ภายในของหน่วยงานก่อนใช้งานจริง

## ตรวจสอบและสำรอง

- เปิดหน้า OPD โดยไม่ล็อกอิน: ต้องไปหน้า login
- บัญชี opd_staff: ต้องเปิด DM และหน้าจัดการผู้ใช้ไม่ได้
- บันทึกรายงาน แล้วเปิดจากอีกเครื่องด้วยผู้ใช้ที่มีสิทธิ์: ต้องเห็นข้อมูลจาก MySQL
- คำขอไป `/.private/database.php`, `/schema.sql`, `/security.php` ต้องไม่ได้เนื้อหาไฟล์
- สำรองด้วย `php export-database.php` จะสร้าง `.private/nursing-center-mysql.sql` ใหม่จาก MySQL ที่ตั้งค่าอยู่ ไฟล์นี้เป็น snapshot ของตาราง InnoDB เก็บสำเนานอกโฟลเดอร์เว็บและทดสอบ restore ลงฐานข้อมูลว่าง

## สถานะของระบบ

บัญชี สิทธิ์ รายงานรายวัน ประวัติการแก้ไข และการป้องกันบันทึกทับพร้อมกัน ใช้ MySQL แล้ว รหัสผ่านขั้นต่ำ 8 ตัวอักษร รายงานแชร์ระหว่างผู้ใช้ตามสิทธิ์หน่วยงาน ข้อมูลตัวอย่างใน dashboard ยังคงแสดงเมื่อหน่วยงานนั้นไม่มีรายงานในฐานข้อมูล ข้อมูลรายงานต่างวันและต่างช่วงสะสมไม่ใช่ยอดรวมวันเดียวกัน

หน้าตรวจสอบ/รับรองยังเป็นหน้าต้นแบบ ไม่ได้เพิ่ม workflow อนุมัติจริงในงานย้ายฐานข้อมูลนี้ ข้อมูลความเสี่ยง/กำลังคนบางส่วนยังเป็นต้นแบบตามระบบเดิม

เครื่องพัฒนารอบนี้ใช้ MySQL แยกที่ `127.0.0.1:3308` ข้อมูลอยู่ `.private/mysql-data` อย่าคัดลอกโฟลเดอร์ data ไปทับ MySQL บนเซิร์ฟเวอร์ ให้ใช้ไฟล์ SQL สำหรับย้ายระบบ
