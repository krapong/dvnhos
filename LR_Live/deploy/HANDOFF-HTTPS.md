# ส่งต่อผู้ดูแล: เปิดใช้งาน LR EMR บน 192.0.2.20

ระบบ: AlmaLinux 9 + Apache + PHP 8.4
โฟลเดอร์: /var/www/html/LR_HOSxP_Live

## ผลตรวจจากเครื่องลูกข่าย

- http://192.0.2.20/LR_HOSxP_Live/login.php ตอบ 403 พร้อมข้อความ HTTPS required
- https://192.0.2.20:443 ยังเชื่อมต่อไม่ได้
- จึงยังไม่ได้ถึงขั้นตรวจรหัสผ่าน HOSxP ไม่ควรแก้ด้วยการปิดเงื่อนไข HTTPS ในแอป

## งานที่ต้องทำ

1. ตั้งค่า Apache SSL ให้ชี้โฟลเดอร์ด้านบน และเปิดพอร์ต 443 ตามนโยบายเครือข่าย
2. ใช้ certificate ที่เครื่องลูกข่ายเชื่อถือ และมี SAN สำหรับ IP 192.0.2.20 หรือเปลี่ยนเป็นชื่อโดเมนภายในที่ตรงกับ certificate
3. ตรวจ PHP-FPM, สิทธิ์อ่าน config.php/supplement.config.php และ SELinux ที่อนุญาตเชื่อมฐานข้อมูล
4. .private ต้องเขียนได้โดย user ของ PHP-FPM (ค่าปกติคือ apache) และมี SELinux type httpd_sys_rw_content_t ไฟล์ login-attempts.php ต้องเขียนได้ด้วย ไม่เปลี่ยน permission โค้ดทั้งหมดเป็น 777

มีสคริปต์ตัวอย่าง deploy/enable-https.sh ให้ผู้ดูแลตรวจและรันด้วย sudo:

    sudo bash /var/www/html/LR_HOSxP_Live/deploy/enable-https.sh

ต้องอัปโหลดสคริปต์ฉบับนี้ก่อน คำสั่งตั้งค่าเปลี่ยน Apache, เพิ่ม mod_ssl หากยังไม่มี, เปิดบริการ https ใน firewalld หากกำลังทำงาน และตั้งค่า SELinux สำหรับแอป สคริปต์เก็บสำรอง VirtualHost เดิมชื่อเดียวกันก่อนแก้ ไม่ได้รันทดสอบบนเครื่องเซิร์ฟเวอร์จริงจากแชต

หากยังไม่มี certificate สคริปต์สร้าง self-signed สำหรับ IP นี้ ซึ่งผู้ดูแลต้องทำให้เครื่องลูกข่ายเชื่อถือหรือแทนด้วยใบรับรองโรงพยาบาลก่อนเปิดใช้จริง ต้องตรวจว่าการเปลี่ยน VirtualHost ไม่กระทบเว็บไซต์อื่นบน IP เดียวกัน

## ตรวจหลังทำ

    sudo apachectl configtest
    sudo ss -lntp | grep ':443'
    curl -I https://192.0.2.20/LR_HOSxP_Live/login.php
    cd /var/www/html/LR_HOSxP_Live
    sudo -u apache php tools/check-server.php

หน้า login ต้องตอบ 200 ผ่าน HTTPS ที่ตรวจ certificate ผ่าน หากยังผิดพลาด กรุณาส่งข้อความท้าย /var/log/httpd/lr-emr-error.log และสถานะ php-fpm/httpd โดยไม่ส่งรหัสผ่าน

ข้อมูลเชื่อมต่อฐานมีอยู่ในไฟล์ config บนเซิร์ฟเวอร์แล้ว ไม่รวมรหัสผ่านในเอกสารนี้

ตรวจเว็บ IP ใหม่วันที่ 2026-11-05: HTTP login ตอบ 403 และ header แจ้ง PHP/7.4.32 ต้องอัปเกรด PHP ที่ Apache ใช้เป็น 8.4 ก่อนใช้งานระบบนี้ ไม่ใช่เพียงเปลี่ยน PHP CLI ตรวจทั้ง php -v และ PHP-FPM handler/pool ของ VirtualHost หลีกเลี่ยงเปลี่ยน PHP ของเว็บไซต์อื่นโดยไม่ตรวจผลกระทบ ยังเชื่อม HTTPS ไม่ได้จากเครื่องทดสอบ
