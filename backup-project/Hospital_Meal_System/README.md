# Hospital Meal System

ระบบสั่งอาหารผู้ป่วยในแบบ standalone web application สำหรับโรงพยาบาล โดยเชื่อมข้อมูล HOSxP แบบ read-only เพื่อดึงข้อมูลผู้ป่วย เตียง วอร์ด โรคประจำตัว BMI NPO allergy และ diet order มาใช้ใน workflow สั่งอาหาร 3 มื้อ

## สิ่งที่มีใน scaffold นี้

- Laravel 11 backend
- Vue 3 + Tailwind frontend
- MariaDB schema สำหรับ cache, orders, templates, logs
- RBAC แบบ role-based ผ่าน `roles` และ `users.role_id`
- Meal order workflow ตั้งแต่ draft ถึง completed
- Bulk action, ward-wide order, copy previous day, STAT order
- Sync log, audit log, telegram log
- Dashboard แยกมุมมองพยาบาล โภชนาการ ห้องครัว และผู้บริหาร

## เริ่มต้นใช้งาน

1. คัดลอก `.env.example` เป็น `.env`
2. ตั้งค่า `DB_*`, `HOSXP_*`, และ `TELEGRAM_*`
3. ติดตั้งแพ็กเกจ:

```bash
composer install
npm install
```

4. สร้าง key และ migrate:

```bash
php artisan key:generate
php artisan migrate --seed
```

5. เปิด dev server:

```bash
php artisan serve
npm run dev
```

## หมายเหตุสำคัญ

- โปรเจกต์นี้ตั้งใจให้ HOSxP เป็นแหล่งข้อมูลแบบ read-only
- ส่วน login/auth ยังไม่ได้ต่อ workflow เต็มรูปแบบใน scaffold รอบแรก แต่โครงสร้างรองรับการต่อยอดได้ทันที
- หากต้องการ production hardening เพิ่มเติม ควรต่อ Laravel Sanctum หรือ session login และเสริม policy/gates ให้ครบ
