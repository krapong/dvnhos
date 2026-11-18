ปลายทางที่ระบุ: AlmaLinux 9 / Apache / 192.0.2.20
URL เป้าหมาย: https://192.0.2.20/
ต้องมี certificate ที่เชื่อถือได้และมี IP 192.0.2.20 ใน SAN; หรือใช้ชื่อโดเมนภายในที่ออก certificate ให้ตรงกัน
ฐาน lr_supplement ยังไม่ได้ยืนยัน IP ให้ตั้งค่าก่อนเปิดบริการ

# LR EMR — AlmaLinux + Apache

ชุดนี้เป็นโค้ดใช้งาน ไม่รวม config จริง รหัสผ่าน .private เดิม บัญชี root ของ MySQL ฐานทดสอบ logs หรือภาพทดสอบ

## 1. เครื่องปลายทาง

ต้องมี PHP 8.4 พร้อม pdo_mysql, iconv, session, json, openssl และ Apache + PHP-FPM ที่ทำงานกับ HTTPS แล้ว ตรวจเวอร์ชัน AlmaLinux และแหล่งแพ็กเกจของเครื่องก่อนติดตั้ง PHP อย่าใช้ PHP built-in server สำหรับบริการจริง

ตัวอย่างติดตั้งโค้ดที่ /var/www/html/LR_HOSxP_Live (ผู้ดูแลระบบเป็นผู้จัดการไฟล์):

    sudo install -d -o root -g apache -m 0750 /var/www/html/LR_HOSxP_Live
    sudo cp -a lr-emr/. /var/www/html/LR_HOSxP_Live/
    sudo chown -R root:apache /var/www/html/LR_HOSxP_Live
    sudo find /var/www/html/LR_HOSxP_Live -type d -exec chmod 0750 {} \;
    sudo find /var/www/html/LR_HOSxP_Live -type f -exec chmod 0640 {} \;
    sudo install -d -o apache -g apache -m 0700 /var/www/html/LR_HOSxP_Live/.private

ใช้ชื่อ group/user ของ PHP-FPM จริงหากต่างจาก apache ไม่ใช้ chmod 777

## 2. ตั้งค่าการเชื่อมต่อ

คัดลอก config.example.php เป็น config.php แล้วใส่ข้อมูลฐาน HOSxP โดยใช้บัญชีอ่านอย่างเดียว ส่วน supplement.config.example.php คัดลอกเป็น supplement.config.php และใส่การเชื่อมต่อฐาน lr_supplement แยกจาก HOSxP ห้ามนำรหัสบัญชีตัวอย่างไปใช้จริง

ให้สิทธิ์ไฟล์ config 0640 root:apache ไม่ commit หรือใส่ในโฟลเดอร์ดาวน์โหลด ห้ามนำ .private/local-db-admin.php จากเครื่องทดสอบขึ้นเซิร์ฟเวอร์

บัญชีเข้าเว็บใช้ opduser/passweb และกลุ่มพยาบาลตามระบบเดิม app_allowed_users คือบัญชีเพิ่มเติมที่ได้รับอนุญาต (ตั้ง pwwa ไว้ตามคำขอเดิม ตรวจสิทธิ์บัญชีนี้บนฐานจริงด้วย)

## 3. ฐานข้อมูลเสริม

ให้ DBA สร้าง lr_supplement ด้วย utf8mb4 แล้วนำเข้า database/supplement.sql:

    mysql -h DB_HOST -u PROVISION_USER -p lr_supplement < database/supplement.sql

รหัสผ่านกรอกเมื่อโปรแกรมถาม ไม่ใส่ลง command line บัญชีแอปควรเป็นบัญชีเฉพาะที่เชื่อมได้จาก IP เว็บเซิร์ฟเวอร์เท่านั้น ใช้ database/grants-example.sql เป็นตัวอย่างและเปลี่ยน WEB_SERVER_IP/ชื่อฐาน/บัญชีให้ตรงจริง

หากมีข้อมูลเสริมจากเครื่องทดสอบ ต้อง export/import ทั้ง supplement_records, supplement_fields, supplement_audit, supplement_meta ก่อนเปิดให้ใช้งาน ใช้ transaction-consistent dump และสำรองฐานปลายทางก่อนนำเข้าฐานที่มีข้อมูลเดิม อย่ารันชุดทดสอบที่เขียนข้อมูลบนฐาน production

หากติดตั้งเป็นฐานใหม่จาก schema ไม่จำเป็นต้องใช้ tools/setup-supplement.php บัญชีแอปไม่ควรได้รับ CREATE DATABASE หรือสิทธิ์เปลี่ยน schema

## 4. สิทธิ์ HOSxP

บัญชี HOSxP ใช้ SELECT เท่านั้น รายชื่อตารางที่ต้องอ่านตรวจได้จาก tools/check-server.php รวม doctor_position, lab_head, lab_order, lab_items, labour_lab และคอลัมน์ loginname/passweb/groupname/account_disable/doctorcode ของ opduser ไม่ให้ UPDATE/INSERT/DELETE ฐาน HOSxP

## 5. Apache / HTTPS

ปรับ deploy/apache-lr-emr.conf ให้เป็น certificate จริงสำหรับ 192.0.2.20 หรือเปลี่ยนเป็นโดเมนภายใน แล้วติดตั้งใน /etc/httpd/conf.d/ หลังจากตรวจว่า PHP-FPM handler ของระบบพร้อมแล้ว ตัวอย่างนี้ใช้ socket /run/php-fpm/www.sock ต้องตรวจว่าตรงกับ PHP 8.4 pool ของเครื่อง (แพ็กเกจต่างแหล่งอาจใช้ path คนละตำแหน่ง)

    sudo apachectl configtest
    sudo systemctl reload httpd

ใช้ URL HTTPS ที่ certificate เชื่อถือได้ หากผ่าน reverse proxy ต้องส่ง HTTPS มาถึง backend หรือกำหนดตัวแปร HTTPS ใน proxy ที่เชื่อถือได้ แอปไม่เชื่อ X-Forwarded-Proto ที่ผู้ใช้ส่งมาเอง

## 6. SELinux

ดู deploy/selinux-almalinux.sh เป็นตัวอย่างคำสั่ง ต้องตรวจ path ก่อนรัน ให้ httpd อ่านโค้ดและเขียนได้เฉพาะ .private และให้ PHP เชื่อมฐานข้อมูลได้ ไม่ปิด SELinux

## 7. ตรวจหลังติดตั้ง

    cd /var/www/html/LR_HOSxP_Live
    sudo -u apache php tools/check-server.php

ตรวจด้วย PHP executable/version เดียวกับ PHP-FPM ด้วย CLI อาจใช้ php.ini คนละไฟล์กับเว็บ

- เปิด /login.php ต้องแสดงหน้า login, /index.php ที่ยังไม่ login ต้อง redirect
- /api.php และ /risk.php ที่ยังไม่ login ต้องตอบ JSON 401
- /config.php, /supplement.config.php, /.private/, /tools/ และ /database/ ต้องเข้าไม่ได้
- ลองบัญชีพยาบาลจริงและ pwwa ที่ได้รับสิทธิ์ ทดสอบ logout และ Session timeout
- เลือก AN ทดสอบที่ได้รับอนุญาตแล้วบันทึกช่องข้อมูลเสริม ตรวจว่าโหลดกลับได้และ audit มีผู้บันทึก โดยไม่มีการเขียน HOSxP
- เทียบรายชื่อห้องคลอดรหัส 04 และวัน Admit ล่าสุดกับ HOSxP XE เครื่องใช้งานจริง
- เทียบผล Lab และคัดกรองทารกเสี่ยงในช่วงวันที่เดียวกัน

ผลตรวจล่าสุดวันที่ 2026-11-05: ฐาน HOSxP ที่เครื่องพัฒนาเชื่อมมีข้อมูล Admit ล่าสุด 2026-11-04 แล้ว ฐานเสริมมี 0 รายการ จึงติดตั้ง schema เปล่าได้หากยังไม่มีการเพิ่มข้อมูลใหม่ก่อนย้าย ต้องเทียบข้อมูลบนเครื่องปลายทางอีกครั้ง และยืนยันว่า passweb ใช้งานได้กับบัญชีจริง ไม่ได้ทดสอบรหัสผ่านบุคลากรจริงอัตโนมัติ

## 8. สำรองและอัปเดต

สำรองฐาน lr_supplement, config ทั้งสองไฟล์ และไฟล์ตั้งค่า Apache/certificate ตามนโยบายโรงพยาบาลก่อนอัปเดต โค้ดชุดนี้ไม่รวมข้อมูลเสริมจริง เมื่ออัปเดตห้ามเขียนทับ config จริงหรือ .private ของเซิร์ฟเวอร์ เก็บ release เดิมไว้สำหรับ rollback และแยกการ rollback โค้ดจากการ restore ข้อมูล

ฐานเสริมบนเซิร์ฟเวอร์ที่มี init_connect='SET NAMES tis620' ต้องสั่ง SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci หลังเชื่อมต่อ (รวมไว้ใน manual-store.php แล้ว) ทดสอบข้อความไทย/UTF-8 และ audit แบบ rollback ผ่าน ไม่เปลี่ยนค่า global และไม่เปลี่ยน charset ของการอ่านฐาน HOSxP ที่มี collation เดิม

ประวัติการใช้งาน

รุ่นนี้เพิ่ม usage-log.php, usage.php และ usage-history.php บันทึก login สำเร็จ/ไม่สำเร็จ, logout, session หมดอายุ, โหลด/ค้นหาข้อมูล, เปิดเวชระเบียน, เริ่มส่งออก CSV และบันทึกข้อมูลเพิ่มเติม โดยเก็บเวลาไทย ผู้ใช้ IP และ metadata ที่จำเป็น ไม่เก็บรหัสผ่านหรือข้อความค้นหา

เก็บแยกรายวันใน .private/usage-YYYY-MM-DD.php โดยล็อกไฟล์ขณะอ่าน/เขียน ไม่ต้องเพิ่มตารางฐานข้อมูล ประวัติเริ่มหลังติดตั้ง ไม่มีข้อมูลย้อนหลัง ควรสำรองและกำหนดอายุเก็บตามนโยบายหน่วยงาน ไม่มีการลบอัตโนมัติหรือหลักประกันป้องกันผู้ดูแลเซิร์ฟเวอร์แก้ไฟล์

เมนู “ประวัติการใช้งาน” แสดงเฉพาะประวัติของผู้ใช้ที่เข้าสู่ระบบ เลือกวันและแสดงล่าสุดไม่เกิน 500 รายการต่อวัน หากต้องการให้ผู้ดูแลดูทั้งหมด เพิ่มคีย์นี้ใน array ของ config.php โดยใช้บัญชีที่ได้รับมอบหมายจริง:

    'audit_view_users' => ['ชื่อผู้ใช้ผู้ดูแล'],

ไม่เพิ่มบัญชีใดเป็นผู้ดูแลอัตโนมัติ .private ต้องเขียนได้โดย PHP-FPM และห้ามเปิดผ่านเว็บ หาก VirtualHost มี FilesMatch จำกัด endpoint ให้เพิ่ม usage และ usage-history ในรายชื่อไฟล์ที่อนุญาตทั้งส่วน Require และ SetHandler ตาม deploy/apache-lr-emr.conf รุ่นนี้ การอัปโหลด .htaccess อย่างเดียวไม่แทนที่ข้อจำกัดระดับ VirtualHost

กิจกรรมเปิดเวชระเบียนและเริ่มส่งออกเป็นข้อมูลจาก browser ไม่รับประกันว่าผู้ใช้เห็นหน้าหรือบันทึกไฟล์สำเร็จ การส่งออกจะหยุดหากบันทึกประวัติไม่ได้ ส่วนงานอื่นยังทำต่อและแจ้งข้อผิดพลาดลง PHP error log เมื่อเขียนไม่ได้ ประวัติแก้ข้อมูลรายช่องก่อน/หลังยังอยู่ใน supplement_audit เดิม

อัปเดตไฟล์: usage-log.php, usage.php, usage-history.php, auth-bootstrap.php, security.php, login.php, logout.php, api.php, manual.php, index.php และ .htaccess โดยคง config.php, supplement.config.php และ .private ของเซิร์ฟเวอร์เดิมไว้


## กรณีไม่มีตัวเลือกเพศ / ฐานข้อมูลเสริมไม่พร้อมใช้งาน

หากหน้า EMR มีแถบแจ้งฐานข้อมูลเสริมไม่พร้อมใช้งาน ระบบจะปิดการแก้ไขและบันทึก รวมถึงเพศ การอัปโหลด PHP อย่างเดียวไม่สร้างหรือย้ายฐาน lr_supplement ให้

1. รักษา config.php, supplement.config.php และ .private เดิมของเซิร์ฟเวอร์ ห้ามใช้ค่าฐานทดสอบจาก Windows แทน
2. ตรวจ supplement.config.php ว่า host/port/database/username/password ตรงกับฐานเสริมบนเซิร์ฟเวอร์จริง ไม่ใช่ฐาน HOSxP
3. หากเป็นฐานใหม่ ให้ DBA สร้างฐานและนำเข้า database/supplement.sql พร้อมสิทธิ์ตาม database/grants-example.sql หากมีฐานเดิมแล้ว ให้สำรองและตรวจตารางก่อน ไม่ล้างฐาน
4. ในโฟลเดอร์เว็บจริง (เช่น /var/www/html/LR_Live) รัน sudo -u apache php tools/check-server.php ด้วย PHP รุ่นเดียวกับ PHP-FPM เครื่องที่ใช้ user อื่นให้เปลี่ยน apache ตามจริง ผลต้องผ่าน Separate supplementary database and schema และ Infant sex dropdown and edit permission metadata
5. SQLSTATE/driver code ที่ตรวจได้: 1045 รหัสหรือ host ของบัญชีไม่ถูกต้อง, 1044/1142 สิทธิ์ไม่ครบ, 1049 ไม่พบฐาน, 1146 ไม่พบตาราง, 2002/2003 ติดต่อ MySQL ไม่ได้ (ตรวจพอร์ต/ไฟร์วอลล์/SELinux ด้วย)
6. อัปโหลดโค้ดครบชุด โดยเฉพาะ index.php, api.php, manual.php, manual-store.php, report.php, report-fields.php, family.php, field-options.php และ database/sheet-options-source.json จากนั้นกดโหลดข้อมูลจริงและ Ctrl+F5 ถ้า OPcache ปิดการตรวจไฟล์ให้ผู้ดูแล reload PHP-FPM

เพศมีตัวเลือก ชาย / หญิง / ระบุไม่ได้ และบันทึกลงฐานเสริม ไม่แก้ข้อมูล HOSxP ข้อมูลที่เคยกรอกบนเครื่องทดสอบจะไม่ติดไปกับ ZIP ต้องย้ายฐานเสริมแยกตามข้อ 3 ของคู่มือนี้ ห้ามส่งรหัสผ่านหรือข้อมูลผู้ป่วยในแชตเพื่อวิเคราะห์ปัญหา

## อายุประวัติการใช้งาน 60 วัน
อัปเดต usage-log.php, usage-history.php และ tools/prune-usage.php ระบบลบเฉพาะ .private/usage-YYYY-MM-DD.php ที่เกิน 60 วันรวมวันนี้ตามเวลาไทย เมื่อบันทึกกิจกรรมหรือเปิดหน้าประวัติ ไม่ลบเวชระเบียนหรือ supplement_audit หากต้องการลบแม้ไม่มีผู้ใช้งาน ให้เพิ่ม cron ของ apache (sudo crontab -u apache -e) วันละครั้ง โดยตรวจ path PHP CLI ให้ตรงระบบ:

    5 0 * * * /usr/bin/php /var/www/html/LR_Live/tools/prune-usage.php

เวลา cron ใช้ timezone ของเซิร์ฟเวอร์ ส่วนเกณฑ์อายุใน PHP ใช้ Asia/Bangkok สามารถรันคำสั่ง PHP นี้ด้วยตนเองหลังอัปเดตเพื่อล้างไฟล์เก่าได้ทันที
## MFA และรายงานฉบับปรับปรุง

ต้องเปิด PHP extension openssl และอัปเดต endpoint mfa.php ใน FilesMatch ทั้งส่วน Require และ SetHandler ตาม apache-lr-emr.conf รุ่นปัจจุบัน อัปโหลด mfa.php, mfa-store.php และ export-report.php พร้อม login.php, security.php, api.php, index.php และ usage-history.php โดยคง config.php, supplement.config.php และ .private เดิมไว้

หลังติดตั้งทุกบัญชีต้องตั้งค่า Authenticator ในการเข้าสู่ระบบครั้งแรก ตรวจสอบตัวเจ้าของบัญชีในการเปิดใช้ครั้งแรก ให้ PHP-FPM เขียน .private/mfa และ .private/exports ได้ และห้ามเข้าถึง .private ทางเว็บ สำรอง .private/mfa/accounts.php พร้อม key.php อย่างปลอดภัย ห้ามนำคีย์จากเครื่องทดสอบไปแทนไฟล์เซิร์ฟเวอร์ คีย์ที่สูญหายไม่สามารถสร้างทดแทนเพื่อถอดรหัสข้อมูลเดิมได้

การกู้คืน MFA: ใช้รหัสกู้คืนครั้งเดียว หรือให้ผู้ดูแลเซิร์ฟเวอร์ยืนยันตัวเจ้าของบัญชีแล้วรัน `php tools/reset-mfa.php --user=USERNAME --confirm=USERNAME` คำสั่งนี้เพิกถอน session เดิมของบัญชีนั้นและบังคับลงทะเบียนใหม่ รายละเอียดการใช้งานพิมพ์และ Excel อยู่ใน README.md


รายงานทั้งหมดขอ PHP memory_limit อย่างน้อย 512 MB และเวลาทำงาน 180 วินาที ตรวจค่า php_admin_value และ request_terminate_timeout ของ PHP-FPM รวมถึง timeout ของ Apache ให้สอดคล้องกับปริมาณข้อมูล ระบบจะไม่ตัดแถวเพื่อลดขนาดไฟล์เอง
