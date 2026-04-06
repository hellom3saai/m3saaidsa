<?php
/**
 * SMS Configuration for ThaiBulkSMS
 * สมัครใช้งานและรับ API Key ได้ที่ https://www.thaibulksms.com/
 */

define('SMS_API_KEY', 'ld1cARo1mzdd1Gbq3IhLvsazyJkoA3'); // ใส่ API Key ที่นี่
define('SMS_API_SECRET', 'M4obSOUhpP7oUvrMKcHlrXQQOuaS1z'); // ใส่ API Secret ที่นี่
define('SMS_SENDER', 'MeloSMS'); // ลองชื่อแบรนด์รองของเค้าดูครับ อันนี้พบบ่อยมากในแอปใหม่

// ตั้งค่าหัวข้อ SMS
define('SMS_MSG_PREFIX', 'รหัส OTP ของคุณคือ: ');
define('SMS_MSG_SUFFIX', ' (อ้างอิง: M3SA)');
?>
