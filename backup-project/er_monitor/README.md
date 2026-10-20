# ER ESI Alert Monitor — HOSxP

ระบบแสดงผลผู้ป่วยห้องฉุกเฉิน (ER) แบบ real-time พร้อม ESI Level และ countdown timer

---

## โครงสร้างไฟล์

```
er_monitor/
├── index.php                  ← หน้าหลัก monitor
├── config/
│   └── database.php           ← ตั้งค่า DB และ App
├── includes/
│   ├── Database.php           ← PDO connection class
│   └── ErModel.php            ← HOSxP SQL query + logic
├── api/
│   └── patients.php           ← REST JSON endpoint
└── assets/
    ├── css/monitor.css        ← stylesheet
    └── js/er_monitor.js       ← live timer + fetch + render
```

---

## การติดตั้ง

### 1. วางไฟล์บน Web Server

```bash
# วางใน document root ของ Apache/Nginx
cp -r er_monitor/ /var/www/html/
# หรือ
cp -r er_monitor/ /home/hosxp/public_html/
```

### 2. แก้ไข config/database.php

```php
define('DB_HOST',     'CHANGE_ME_BEFORE_USE');   // IP MySQL HOSxP
define('DB_PORT',     '3306');
define('DB_NAME',     'CHANGE_ME_BEFORE_USE');       // หรือ 'his'
define('DB_USER',     'CHANGE_ME_BEFORE_USE');
define('DB_PASS',     'รหัสผ่าน');
define('HOSPITAL_NAME', 'โรงพยาบาลของคุณ');
define('REFRESH_SEC', 30);                // รีเฟรชทุก 30 วิ
```

### 3. ตรวจสอบ PHP และ Extension

```bash
php -v              # ต้องการ PHP >= 7.4
php -m | grep pdo   # ต้องมี pdo_mysql
```

### 4. Permission (Linux)

```bash
chmod 644 er_monitor/config/database.php
chmod -R 755 er_monitor/
```

---

## API Endpoint

```
GET /er_monitor/api/patients.php
GET /er_monitor/api/patients.php?date=2024-01-15
GET /er_monitor/api/patients.php?summary=1
GET /er_monitor/api/patients.php?token=your-token
```

**Response ตัวอย่าง:**
```json
{
  "success": true,
  "date": "2024-01-15",
  "total": 9,
  "updated_at": "2024-01-15 14:30:00",
  "data": [
    {
      "hn": "2341",
      "queue": 1,
      "time": "14:02:10",
      "p_name": "นาย สมชาย สุขเจริญ",
      "color": "1",
      "esi_class": "esi1",
      "esi_label": "Level 1 Resuscitation",
      "elapsed_min": 28,
      "remaining_min": 92,
      "los_status": "ok",
      "lab": "LB001",
      "xr": "",
      "observe": "1",
      "dx": "I21"
    }
  ]
}
```

---

## ESI Color Code (HOSxP)

| export_code | Level | ความหมาย |
|---|---|---|
| 1 หรือ R | Level 1 | Resuscitation — วิกฤต |
| 2 หรือ E | Level 2 | Emergent — ฉุกเฉิน |
| 3 หรือ U | Level 3 | Urgent — เร่งด่วน |
| 4 หรือ S | Level 4 | Less Urgent — ไม่เร่งด่วน |
| 5 หรือ N | Level 5 | Non-Urgent — ทั่วไป |

---

## Security (แนะนำ)

```php
// config/database.php
define('API_TOKEN',   'CHANGE_ME_BEFORE_USE');
define('ALLOWED_IPS', ['YOUR_PRIVATE_SERVER/24']);
```

---

## Troubleshooting

| ปัญหา | วิธีแก้ |
|---|---|
| `Database connection failed` | ตรวจสอบ DB_HOST, USER, PASS ใน config |
| ตารางว่าง ไม่มีข้อมูล | กด Demo เพื่อทดสอบ หรือตรวจ SQL ใน ErModel.php |
| `elapsed_min` ผิด | ตรวจ timezone ใน config (Asia/Bangkok) |
| ภาษาไทยเพี้ยน | ตรวจ `SET NAMES utf8` ใน Database.php |
