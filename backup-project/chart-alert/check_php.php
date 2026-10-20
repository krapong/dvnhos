<?php
echo "<h3>วิธีแก้ปัญหาอัปโหลดไฟล์ไม่ผ่าน</h3>";
echo "<b>1. ไฟล์ตั้งค่า PHP ของคุณอยู่ที่:</b> " . php_ini_loaded_file() . "<br><br>";
echo "<b>2. ค่าปัจจุบัน:</b><br>";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "- post_max_size: " . ini_get('post_max_size') . "<br><br>";
echo "<b>วิธีแก้ไขผ่านโปรแกรม Bitvise SFTP:</b><br>";
echo "1. ไปที่ช่อง Remote files (ฝั่งขวา) พิมพ์พาธนี้ในช่องด้านบนสุดแล้วกด Enter: <b>" . dirname(php_ini_loaded_file()) . "</b><br>";
echo "2. ค้นหาไฟล์ชื่อ <b>php.ini</b> แล้วคลิกขวาเลือก <b>Edit</b><br>";
echo "3. ค้นหาคำว่า <code>upload_max_filesize</code> และเปลี่ยนเป็น <code>128M</code><br>";
echo "4. ค้นหาคำว่า <code>post_max_size</code> และเปลี่ยนเป็น <code>128M</code><br>";
echo "5. กด Save แล้วปิดหน้าต่าง<br>";
echo "6. รีสตาร์ทเซิร์ฟเวอร์ (ถ้าใช้ XAMPP/LAMPP ให้รีสตาร์ท Apache, หรือถ้าเป็นเซิร์ฟเวอร์ปกติ พิมพ์คำสั่ง <code>systemctl restart apache2</code> หรือ <code>systemctl restart httpd</code> ในหน้าต่าง Terminal ของ Bitvise)";
?>
