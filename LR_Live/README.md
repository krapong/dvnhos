# LR EMR

ระบบเวชระเบียนห้องคลอด PHP 8.4 เชื่อม HOSxP แบบอ่านอย่างเดียว และเก็บข้อมูลเพิ่มเติมใน MySQL/MariaDB ฐาน lr_supplement แยกต่างหาก

## ไฟล์ที่ใช้งาน

- index.php: หน้าจอ EMR, Dashboard, ทะเบียน, Lab และคัดกรองทารกเสี่ยง
- login.php / logout.php / security.php / auth-bootstrap.php / hosxp-auth.php / login-throttle.php: เข้าสู่ระบบและ Session
- api.php / report.php / report-fields.php / report-labs.php / providers.php: อ่านข้อมูล HOSxP และรายชื่อผู้ทำคลอด Active
- risk.php / infant-risk.php: คัดกรองตามเกณฑ์ APGAR และน้ำหนัก
- manual.php / manual-store.php: ตรวจสอบและบันทึกข้อมูลเพิ่มเติมพร้อม audit/version
- field-options.php + database/sheet-options-source.json: ตัวเลือกจากชีตสำเนาที่ผู้ใช้ระบุ
- config.php: การเชื่อมต่อ HOSxP และบัญชีที่อนุญาตเพิ่มเติม
- supplement.config.php: การเชื่อมต่อฐานเสริมที่ใช้อยู่บนเครื่องนี้
- database/supplement.sql / grants-example.sql: schema และตัวอย่างสิทธิ์ของฐานเสริม
- deploy/: คู่มือ AlmaLinux 9 และ Apache/SELinux สำหรับ /var/www/html/LR_HOSxP_Live
- tools/check-server.php: ตรวจ PHP, การเชื่อมต่อและสิทธิ์อ่านตาราง
- tools/setup-supplement.php: ติดตั้ง schema/ย้ายข้อมูล legacy ด้วยบัญชีติดตั้งที่ได้รับอนุญาต
- tools/start-local.ps1: เปิด PHP 8.4 และ MySQL ทดสอบบน Windows
- tools/build-release.ps1: สร้างชุดติดตั้งจากรายชื่อไฟล์ที่อนุญาต โดยไม่ใส่ค่าลับหรือข้อมูลจริง

## เปิดบนเครื่องพัฒนา

    powershell -ExecutionPolicy Bypass -File tools/start-local.ps1

เว็บ: http://127.0.0.1:8089/index.php
ฐานเสริมสำหรับเครื่องพัฒนา: ดู supplement.config.php (ไม่ได้เปลี่ยนเป็นฐานปลายทางอัตโนมัติ)
ตรวจความพร้อม: php tools/check-server.php

## นำขึ้นเซิร์ฟเวอร์

อ่าน deploy/README-server.md ก่อนติดตั้ง ตั้งค่าจริงจาก config.example.php และ supplement.config.example.php ไม่ใช้ PHP built-in server ใน production ใช้ HTTPS ที่เชื่อถือได้

ไฟล์ตั้งค่าสำหรับเซิร์ฟเวอร์ที่เตรียมไว้แยกอยู่ใน .private/server-configs/ ให้นำ config.php และ supplement.config.php จากโฟลเดอร์นี้ไปวางที่ root ของเว็บบนเซิร์ฟเวอร์เท่านั้น ห้ามอัปโหลดไฟล์ผู้ดูแล MySQL ของเครื่องพัฒนา

## ข้อมูลและการเข้าใช้

- บัญชี HOSxP ตรวจด้วย opduser.passweb และสิทธิ์กลุ่มพยาบาล/บัญชี app_allowed_users ตามที่กำหนด ไม่เก็บรหัสผ่านใน Session
- Session idle 30 นาที อายุรวม 8 ชั่วโมง ตรวจสิทธิ์ซ้ำทุก 60 วินาที Logout ใช้ POST+CSRF และจำกัดการลองรหัสซ้ำ
- ช่องว่างกรอกเพิ่มเติมได้ ข้อมูล HOSxP มีลำดับความสำคัญก่อน ยกเว้น G/TP/Pre/A/L และคู่ชื่อ/รหัสผู้ทำคลอดที่อนุญาตให้ override
- ผู้ทำคลอดต้องเป็นแพทย์หรือพยาบาล Active=Y ตรวจซ้ำตอนบันทึก ชื่อมาจากรหัสที่เลือกจริง
- รายชื่อห้องคลอดปัจจุบันใช้ ward=04 และยังไม่จำหน่าย ไม่จำกัดวัน Admit ส่วนประวัติใช้ช่วงวัน Admit
- คัดกรองทารกเสี่ยงใช้วันเกิด แยกแหล่ง ipt_labour_infant/ipt_newborn ไม่รวมยอดซ้ำ เกณฑ์ตามคำสั่งผู้ใช้และไม่แทนการวินิจฉัย
- ข้อมูล Lab ผูกกับ AN หรือ VN ตอน Admit ของมารดา ไม่ดึงทุกประวัติ HN และไม่รวมผลทารกโดยอัตโนมัติ

## โฟลเดอร์ .private

เก็บข้อมูล runtime ที่ไม่ควรเปิดผ่านเว็บ ได้แก่ login-attempts.php และ logs/ ส่วน local-db-admin.php เป็นบัญชีดูแล MySQL ทดสอบที่ยังจำเป็นสำหรับดูแลข้อมูลบนเครื่องนี้ ไม่ใช่บัญชีแอป production

supplement-server.config.php และ server-configs/ เป็นค่าปลายทางที่เตรียมไว้ ไม่แสดงรหัสผ่านในคู่มือ อย่า commit หรือเผยแพร่โฟลเดอร์นี้

## การล้างไฟล์

ลบหน้าทดสอบ/ภาพทดสอบ ชุดติดตั้งเก่า tests/ ไฟล์ .bak log เก่าที่ root และ SQL สำเนาที่ระบบไม่ได้เรียกใช้แล้ว สำรองรายการเหล่านี้นอก web root ก่อนลบ พร้อมตรวจ SHA256 เทียบครบทุกไฟล์ ไม่ลบฐานข้อมูล ข้อมูลเสริม หรือค่าการเชื่อมต่อที่ใช้งาน

## ประวัติการใช้งาน

รุ่นนี้เพิ่ม usage-log.php, usage.php และ usage-history.php บันทึก login สำเร็จ/ไม่สำเร็จ, logout, session หมดอายุ, โหลด/ค้นหาข้อมูล, เปิดเวชระเบียน, เริ่มส่งออก CSV และบันทึกข้อมูลเพิ่มเติม โดยเก็บเวลาไทย ผู้ใช้ IP และ metadata ที่จำเป็น ไม่เก็บรหัสผ่านหรือข้อความค้นหา

เก็บแยกรายวันใน .private/usage-YYYY-MM-DD.php โดยล็อกไฟล์ขณะอ่าน/เขียน ไม่ต้องเพิ่มตารางฐานข้อมูล ประวัติเริ่มหลังติดตั้ง ไม่มีข้อมูลย้อนหลัง ควรสำรองและกำหนดอายุเก็บตามนโยบายหน่วยงาน ลบไฟล์ประวัติการใช้งานที่เกิน 60 วันรวมวันนี้อัตโนมัติเมื่อบันทึกหรือเปิดประวัติ ไม่มีหลักประกันป้องกันผู้ดูแลเซิร์ฟเวอร์แก้ไฟล์

เมนู “ประวัติการใช้งาน” แสดงเฉพาะประวัติของผู้ใช้ที่เข้าสู่ระบบ เลือกวันและแสดงล่าสุดไม่เกิน 500 รายการต่อวัน หากต้องการให้ผู้ดูแลดูทั้งหมด เพิ่มคีย์นี้ใน array ของ config.php โดยใช้บัญชีที่ได้รับมอบหมายจริง:

    'audit_view_users' => ['ชื่อผู้ใช้ผู้ดูแล'],

ไม่เพิ่มบัญชีใดเป็นผู้ดูแลอัตโนมัติ .private ต้องเขียนได้โดย PHP-FPM และห้ามเปิดผ่านเว็บ หาก VirtualHost มี FilesMatch จำกัด endpoint ให้เพิ่ม usage และ usage-history ในรายชื่อไฟล์ที่อนุญาตทั้งส่วน Require และ SetHandler ตาม deploy/apache-lr-emr.conf รุ่นนี้ การอัปโหลด .htaccess อย่างเดียวไม่แทนที่ข้อจำกัดระดับ VirtualHost

กิจกรรมเปิดเวชระเบียนและเริ่มส่งออกเป็นข้อมูลจาก browser ไม่รับประกันว่าผู้ใช้เห็นหน้าหรือบันทึกไฟล์สำเร็จ การส่งออกจะหยุดหากบันทึกประวัติไม่ได้ ส่วนงานอื่นยังทำต่อและแจ้งข้อผิดพลาดลง PHP error log เมื่อเขียนไม่ได้ ประวัติแก้ข้อมูลรายช่องก่อน/หลังยังอยู่ใน supplement_audit เดิม

อัปเดตไฟล์: usage-log.php, usage.php, usage-history.php, auth-bootstrap.php, security.php, login.php, logout.php, api.php, manual.php, index.php และ .htaccess โดยคง config.php, supplement.config.php และ .private ของเซิร์ฟเวอร์เดิมไว้

Dashboard: การ์ดทารกแรกเกิดแผนกกุมารนับ AN ไม่ซ้ำจาก ipt โดยแผนกกุมารเวชกรรมและประเภทรับไว้แรกเกิด ใช้ช่วงวัน Admit ทารก ไม่ต้องมีทะเบียนคลอดมารดา ส่วนมารดา น้ำหนัก/APGAR และกราฟยังอิงทะเบียนคลอดตามเกณฑ์เดิม อัปเดต api.php, report.php และ index.php พร้อมกัน

ความสัมพันธ์แม่–ลูก: family.php อ่านคู่ AN จาก ipt_newborn.mother_an/an และ ipt_labour.an/ipt_labour_infant.infant_an แสดง AN ลูกทั้งหมดในข้อมูลแม่และ AN แม่ในรายการทารก (กดการ์ดทารกแรกเกิดแผนกกุมาร) รองรับลูกหลายคน หาก AN ลูกเชื่อมแม่หลาย AN จะแจ้งข้อมูลขัดแย้งและไม่ยืนยันคู่ ไม่เขียนกลับ HOSxP อัปเดต family.php, api.php และ index.php พร้อมกัน

GitHub edition uses placeholder hospital branding and documentation-only server addresses. Configure branding.config.php and add an authorized logo under assets/ on your server. Real deployment configuration and data are excluded.
