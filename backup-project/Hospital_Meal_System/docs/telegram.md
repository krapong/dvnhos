# Telegram

## Use cases

- แจ้งเตือนวอร์ดที่ยังสั่งไม่ครบ
- แจ้งเตือน cut-off time
- แจ้งเตือน STAT order
- แจ้งเตือน sync failure
- ส่ง daily summary เวลา 05:00

## Environment variables

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `TELEGRAM_ENABLED`

## Admin schedule

- Admin ตั้งเวลาแจ้งเตือนได้ 3 รอบต่อวันจากหน้าเว็บ: มื้อเช้า, มื้อกลางวัน, มื้อเย็น
- ระบบเก็บค่าในตาราง `telegram_notification_schedules`
- Command `hospital-meal:telegram-send-due` ตรวจรอบที่ถึงเวลาและส่ง Telegram
- Laravel scheduler เรียก command นี้ทุก 1 นาที และกันส่งซ้ำไม่เกิน 1 ครั้งต่อมื้อต่อวัน
- Production ต้องเปิด cron หรือ process `php artisan schedule:work` เพื่อให้ scheduler ทำงานต่อเนื่อง

## Message style

- สั้น
- ชัดเจน
- ระบุ ward, date, and action required

## Error handling

- บันทึกทุกข้อความลง `telegram_logs`
- ถ้า Telegram ไม่พร้อมใช้งาน ให้ fallback เป็น log only
