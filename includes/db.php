<?php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$user = "root";       // ปกติ XAMPP ใช้ root
$pass = "";           // ปกติ XAMPP ไม่ใส่รหัส
$dbname = "rms_db"; // ชื่อฐานข้อมูลของคุณ
$port = 3306;

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตั้งค่าภาษาไทยให้แสดงผลถูกต้อง
mysqli_set_charset($conn, "utf8");

// (Optional) ตั้งค่า Timezone เป็นไทย
date_default_timezone_set('Asia/Bangkok');
?>