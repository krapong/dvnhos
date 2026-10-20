# เว็บไซต์โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา

ระบบเว็บไซต์สำหรับโรงพยาบาลชุมชน (รพช.) ระดับ F2 ขนาด 60 เตียง
พัฒนาด้วย Laravel 10 + Bootstrap 5 + Spatie Permission

---

## ความต้องการของระบบ (System Requirements)

- PHP >= 8.1
- MySQL >= 5.7 หรือ MariaDB >= 10.3
- Composer >= 2.0
- Node.js >= 16 (สำหรับ npm ถ้าต้องการ compile assets)
- PHP Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD/Imagick

---

## วิธีติดตั้ง (Installation)

### 1. Clone โปรเจกต์

```bash
git clone <repository-url> dvhospital
cd dvhospital
```

### 2. ติดตั้ง Dependencies

```bash
composer install
```

### 3. ตั้งค่า Environment

```bash
cp .env.example .env
php artisan key:generate
```

แก้ไขไฟล์ `.env` ให้ตรงกับการตั้งค่าฐานข้อมูลของคุณ:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dvhospital
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. สร้างฐานข้อมูล

```bash
# สร้าง database ใน MySQL ก่อน
mysql -u root -p -e "CREATE DATABASE dvhospital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# รัน migration
php artisan migrate

# สร้างข้อมูลเริ่มต้น (Roles, Permissions, Admin User)
php artisan db:seed
```

### 5. สร้าง Storage Link

```bash
php artisan storage:link
```

### 6. เริ่มใช้งาน

```bash
php artisan serve
```

เข้าเว็บไซต์: http://localhost:8000
เข้า Admin Panel: http://localhost:8000/login

---

## บัญชีเริ่มต้น (Default Accounts)

| Email | Password | Role |
|---|---|---|
| admin@dvhospital.go.th | password | Super Admin |
| pr@dvhospital.go.th | password | Staff PR (ประชาสัมพันธ์) |
| procurement@dvhospital.go.th | password | Staff Procurement (จัดซื้อจัดจ้าง) |
| ita@dvhospital.go.th | password | Staff ITA & Ethics |
| events@dvhospital.go.th | password | Staff Events (กิจกรรม) |

**สำคัญ: กรุณาเปลี่ยนรหัสผ่านทันทีหลังติดตั้ง!**

---

## ระบบสิทธิ์การเข้าถึง (Role-Based Access Control)

| Role | สิทธิ์การเข้าถึง |
|---|---|
| Super Admin | เข้าถึงทุกเมนู, จัดการผู้ใช้, ตั้งค่าระบบ, ดู Activity Log |
| Staff PR | ข่าวประชาสัมพันธ์, Banner, ประกาศรับสมัครงาน |
| Staff Procurement | ประกาศจัดซื้อจัดจ้าง, สรุปผลจัดซื้อ (สขร.1) |
| Staff ITA & Ethics | ITA, ชมรมจริยธรรม, OIT, หน้าเพจ |
| Staff Events | กิจกรรม, ปฏิทินกิจกรรม, ภาพข่าวลงพื้นที่ |

---

## โครงสร้างเว็บไซต์ (Sitemap)

### Frontend (สาธารณะ)
- หน้าแรก - Banner, Quick Links, ข่าวล่าสุด, ตารางแพทย์
- รู้จักองค์กร - ประวัติ, วิสัยทัศน์, ผู้บริหาร, โครงสร้าง
- บริการของเรา - รายชื่อแผนก, ขีดความสามารถ
- ตารางแพทย์ออกตรวจ
- ข่าวประชาสัมพันธ์ (Blog)
- ประกาศรับสมัครงาน (Blog)
- ประกาศจัดซื้อจัดจ้าง (Blog)
- กิจกรรมหน่วยงาน (Blog)
- ITA - คุณธรรมความโปร่งใส
- ชมรมจริยธรรม
- ปฏิทินกิจกรรม
- ติดต่อเรา + แผนที่ Google Maps

### Backend (Admin Panel)
- Dashboard พร้อมสถิติ
- จัดการข่าว/ประกาศทุกประเภท (CRUD)
- จัดการ Banner Slider
- จัดการตารางแพทย์ออกตรวจ
- จัดการปฏิทินกิจกรรม (FullCalendar)
- จัดการหน้าเพจ (ITA, Ethics, OIT)
- จัดการผู้ใช้งาน + กำหนด Role
- ดู Activity Log

---

## คุณสมบัติเด่น

- **Responsive Design** - รองรับมือถือ, แท็บเล็ต, PC
- **Accessibility** - ปุ่มปรับขนาดตัวอักษร, ฟอนต์อ่านง่ายสำหรับผู้สูงอายุ
- **PDPA Ready** - Cookie Consent Banner
- **ITA Ready** - เมนูประกาศจัดซื้อจัดจ้าง, สรุปผลจัดซื้อ
- **โทนสีเขียวสาธารณสุข** - สะอาด น่าเชื่อถือ
- **Activity Log** - บันทึกประวัติการใช้งานทั้งหมด
- **ปุ่มฉุกเฉิน 1669** - โดดเด่น เข้าถึงง่าย

---

## เทคโนโลยี (Tech Stack)

- **Backend**: Laravel 10 (PHP 8.1+)
- **Frontend**: Blade Templates + Bootstrap 5.3
- **Database**: MySQL / MariaDB
- **Permission**: Spatie Laravel Permission
- **Icons**: Bootstrap Icons
- **Font**: Sarabun (Google Fonts)
- **Calendar**: FullCalendar 6
- **Maps**: Google Maps Embed

---

## การ Deploy บน Production

```bash
# ตั้งค่า .env สำหรับ production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.go.th

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# ตั้ง SSL Certificate (HTTPS)
# ตั้งค่า Cron Job สำหรับ Laravel Scheduler (ถ้าต้องการ)
```

---

## License

MIT License
