# คู่มือการติดตั้งระบบ DVHospital ขึ้นโฮสต์จริง (Production Deployment Guide)

คู่มือนี้จัดทำขึ้นเพื่อให้คุณสามารถนำระบบเว็บไซต์ **โรงพยาบาลเทพรัตนเวชชานุกูล** ขึ้นใช้งานบนโฮสต์จริงได้อย่างราบรื่นและปลอดภัย โดยครอบคลุมทั้งรูปแบบ **Shared Hosting (cPanel/DirectAdmin)** และ **VPS / Cloud Server**

---

## 📦 แพ็กเกจที่ระบบสร้างให้คุณในโฟลเดอร์หลัก (Root Folder)

หลังจากจัดระเบียบเสร็จแล้ว ระบบได้จัดเตรียมไฟล์ที่พร้อมใช้งานให้คุณดังนี้:

1. **`dvhospital_shared_production.zip` (สำหรับ Shared Hosting / cPanel)**
   - **แนะนำสำหรับ:** โฮสติ้งทั่วไปที่ไม่มี SSH/Terminal
   - **รายละเอียด:** รวมโฟลเดอร์ `vendor` (ไลบรารี PHP ทั้งหมด) ไว้เรียบร้อยแล้ว สามารถอัปโหลดและแตกไฟล์แล้วใช้งานได้ทันทีโดยไม่ต้องรันคำสั่งใดๆ
2. **`dvhospital_vps_production.zip` (สำหรับ VPS / Dedicated Server)**
   - **แนะนำสำหรับ:** VPS เช่น Ubuntu, CentOS ที่คุณสามารถรีโมตเข้าไปได้
   - **รายละเอียด:** เป็นไฟล์ขนาดเล็กที่ตัดโฟลเดอร์ `vendor` ออก เพื่อให้คุณรันคำสั่ง `composer install` บนเซิร์ฟเวอร์จริงได้รวดเร็วขึ้น
3. **`database/hospital_db_backup.sql` (ไฟล์ฐานข้อมูล)**
   - **รายละเอียด:** ไฟล์ฐานข้อมูล MySQL สำรองล่าสุดที่มีข้อมูลโครงสร้างและการตั้งค่า รวมถึงข้อมูลเริ่มต้นทั้งหมด รองรับภาษาไทยสมบูรณ์แบบ (`utf8mb4`)

---

## 🛠️ ขั้นตอนที่ 1: การเตรียมฐานข้อมูล (Database Setup)

1. เข้าสู่ระบบควบคุมโฮสติ้งของคุณ (เช่น cPanel, DirectAdmin หรือ phpMyAdmin)
2. สร้างฐานข้อมูล MySQL ใหม่ (เช่น `dvhospital_db`)
3. สร้างผู้ใช้ฐานข้อมูล (Database User) และกำหนดรหัสผ่านที่ปลอดภัย จากนั้นมอบสิทธิ์ทั้งหมด (Privileges) ให้กับฐานข้อมูลที่สร้างขึ้น
4. เข้าไปที่ **phpMyAdmin** เลือกฐานข้อมูลที่สร้างขึ้นใหม่
5. คลิกแท็บ **Import (นำเข้า)** เลือกไฟล์ **`database/hospital_db_backup.sql`** ที่อยู่ในแพ็กเกจ
6. ตรวจสอบว่าระบบ Encoding เป็น **UTF-8** แล้วกด **Import/Go** ฐานข้อมูลจะถูกนำเข้าอย่างสมบูรณ์แบบโดยอัตโนมัติ

---

## 📁 ขั้นตอนที่ 2: การอัปโหลดไฟล์ (Uploading Files)

### 📌 วิธีที่ A: สำหรับ Shared Hosting (เช่น cPanel / DirectAdmin)

> [!IMPORTANT]
> เพื่อความปลอดภัยสูงสุดของ Laravel **ห้าม** แตกไฟล์โค้ดทั้งหมดลงในโฟลเดอร์ `public_html` โดยตรงเด็ดขาด เพราะจะทำให้ผู้อื่นสามารถเข้าถึงไฟล์คอนฟิกและฐานข้อมูลหลักได้

1. อัปโหลดไฟล์ **`dvhospital_shared_production.zip`** ไปยัง **โฟลเดอร์หลักนอก `public_html`** (เช่น โฟลเดอร์ `/home/username/` หรือระดับเดียวกับ `public_html`)
2. แตกไฟล์ (Extract) ออกมา คุณจะได้โครงสร้างโฟลเดอร์ Laravel ทั้งหมดอยู่นอก `public_html`
3. ย้ายหรือทำสำเนาไฟล์และโฟลเดอร์ทั้งหมดที่อยู่ในโฟลเดอร์ **`public/`** ไปไว้ในโฟลเดอร์ **`public_html/`** ของโฮสติ้งของคุณ
4. เปิดไฟล์ **`public_html/index.php`** และแก้ไขเส้นทางการเข้าถึงระบบให้ถูกต้อง (ถ้าติดตั้งอยู่นอกโฟลเดอร์ `public_html` 1 ระดับ):
   - แก้ไขบรรทัดที่ 34:
     ```php
     // จากเดิม
     require __DIR__.'/../vendor/autoload.php';
     // เปลี่ยนเป็น (หากวางโค้ดไว้นอก public_html)
     require __DIR__.'/../vendor/autoload.php'; 
     ```
   - แก้ไขบรรทัดที่ 47:
     ```php
     // จากเดิม
     $app = require_once __DIR__.'/../bootstrap/app.php';
     // เปลี่ยนเป็น
     $app = require_once __DIR__.'/../bootstrap/app.php';
     ```

### 📌 วิธีที่ B: สำหรับ VPS / Cloud Server (เช่น Ubuntu, Nginx / Apache)

1. อัปโหลดไฟล์ **`dvhospital_vps_production.zip`** ไปยังโฟลเดอร์เว็บเซิร์ฟเวอร์ของคุณ (เช่น `/var/www/dvhospital`)
2. แตกไฟล์โดยใช้คำสั่ง:
   ```bash
   unzip dvhospital_vps_production.zip
   ```
3. ติดตั้งไลบรารี PHP (ต้องมี Composer ติดตั้งบน VPS):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. กำหนด Document Root ของ Nginx/Apache ให้ชี้ไปที่โฟลเดอร์ `/var/www/dvhospital/public`
5. กำหนดสิทธิ์การเข้าถึงไฟล์ (File Permissions):
   ```bash
   chown -R www-data:www-data /var/www/dvhospital
   find /var/www/dvhospital -type f -exec chmod 644 {} \;
   find /var/www/dvhospital -type d -exec chmod 755 {} \;
   chmod -R 775 /var/www/dvhospital/storage
   chmod -R 775 /var/www/dvhospital/bootstrap/cache
   ```

---

## ⚙️ ขั้นตอนที่ 3: การตั้งค่าระบบหลัก (`.env` Configuration)

1. ค้นหาไฟล์ **`.env.production`** ที่ระบบเตรียมไว้ให้ในโฟลเดอร์ราก (Root Folder) ของคุณ
2. เปลี่ยนชื่อไฟล์นี้เป็น **`.env`**
3. เปิดแก้ไขไฟล์ `.env` และกำหนดค่าดังต่อไปนี้ให้ตรงกับระบบจริง:
   ```env
   APP_NAME="โรงพยาบาลเทพรัตนเวชชานุกูล"
   APP_ENV=production
   APP_DEBUG=false                # ปิด Debug เพื่อความปลอดภัยและการแสดงผลที่ถูกต้อง
   APP_URL=https://yourdomain.go.th # เปลี่ยนเป็นโดเมนจริงของคุณ (เช่น https://dvhospital.go.th)

   # ตั้งค่าฐานข้อมูลจริงของโฮสต์
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ชื่อฐานข้อมูลที่คุณสร้างบนโฮสต์
   DB_USERNAME=ชื่อผู้ใช้งานฐานข้อมูล
   DB_PASSWORD=รหัสผ่านผู้ใช้งานฐานข้อมูล
   ```
4. ทำการสุ่มสร้าง **APP_KEY** เพื่อความปลอดภัยในการเข้ารหัส:
   - **สำหรับ VPS:** รันคำสั่ง `php artisan key:generate`
   - **สำหรับ Shared Hosting:** หากคุณไม่สามารถรันคำสั่งได้ ให้พิมพ์คีย์แบบสุ่มความยาว 32 ตัวอักษร เช่น `base64:รหัสที่สุ่มมา32ตัว=` หรือใช้เว็บช่วยเจนฯ เช่น [Laravel Key Generator](https://laravelkey.g7.dev/) แล้วคัดลอกมาวางในช่อง `APP_KEY=`

---

## 🔗 ขั้นตอนที่ 4: การเชื่อมโยงโฟลเดอร์อัปโหลดภาพ (`storage:link`)

ในระบบ Laravel รูปภาพข่าวกิจกรรม ป็อปอัพ และข้อมูลประชาสัมพันธ์จะถูกบันทึกไว้ในโฟลเดอร์ `storage/app/public` แต่การแสดงผลบนเว็บจะอ่านจากโฟลเดอร์ `public/storage` ดังนั้นต้องทำการสร้างทางลัด (Symbolic Link) เชื่อมต่อโฟลเดอร์เข้าหากัน

### 📌 วิธีที่ A: สำหรับ VPS
รันคำสั่งผ่าน SSH:
```bash
php artisan storage:link
```

### 📌 วิธีที่ B: สำหรับ Shared Hosting (ที่ไม่มี SSH/Terminal)
ทางเราได้สร้างสคริปต์อัตโนมัติพิเศษให้คุณเรียบร้อยแล้ว:
1. เข้าไปที่เบราว์เซอร์แล้วพิมพ์: `https://yourdomain.go.th/storage_link.php` (แทนที่ด้วยโดเมนจริงของคุณ)
2. ระบบจะทำการรันคำสั่งทางลัดให้ทันทีและแสดงข้อความว่าสร้างสำเร็จ!
3. **สำคัญมาก:** หลังจากสร้างสำเร็จแล้ว ให้เข้าไปลบไฟล์ `public/storage_link.php` ออกจากโฮสติ้งเพื่อความปลอดภัยสูงสุด

---

## 🚀 ขั้นตอนที่ 5: การเคลียร์แคชและเพิ่มความเร็วระบบ (Optimization)

รันคำสั่งเหล่านี้ (สำหรับ VPS) เพื่อให้ระบบทำงานได้รวดเร็วขึ้น:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

หากเป็น Shared Hosting ระบบจะทำงานได้ดีอยู่แล้วโดยไม่จำเป็นต้องรันคำสั่งนี้

---

🔒 **ยินดีด้วย! ระบบ DVHospital ของคุณพร้อมเปิดให้บริการสำหรับประชาชนทั่วไปแล้วอย่างสมบูรณ์และปลอดภัย**
