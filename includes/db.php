<?php
include_once __DIR__ . '/../inc/func.php';

$host = '10.10.99.67';
$user = 'medsci_userweb66';
$pass = 'isylmedsci2023';
$dbname = 'medsci_db_fpa';
$port = 3306;
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);


// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Service temporarily unavailable.');
}

// ตั้งค่าภาษาไทยให้แสดงผลถูกต้อง
mysqli_set_charset($conn, "utf8");

// (Optional) ตั้งค่า Timezone เป็นไทย
date_default_timezone_set('Asia/Bangkok');

?>
