<?php
// inc/func.php

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // ข้ามบรรทัดที่เป็น Comment (#)
        if (strpos(trim($line), '#') === 0) continue;

        // แยก Key และ Value ด้วยเครื่องหมาย =
        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);

        // นำค่าไปใส่ใน $_ENV และ putenv เพื่อให้ดึงไปใช้ง่ายๆ
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
    return true;



}


// ตรวจสอบว่ามีการส่ง Form Action 'delete_user' มาหรือไม่
function submitDeleteUser($conn){
    // 1. รับค่า ID
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $actor_id = $_SESSION['user_id'];

    if ($target_user_id > 0) {

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่ามาตรวจสอบก่อน
        // ---------------------------------------------------------
        $sql_check = "SELECT prefix, first_name, last_name FROM user_profiles WHERE user_id = '$target_user_id'";
        $result_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($result_check);

        // ถ้าไม่พบข้อมูล
        if (!$old_data) {
            header("Location: index.php?page=dashboard&tab=users&status=error&msg=" . urlencode("ไม่พบข้อมูลผู้ใช้งาน"));
            exit();
        }

        // 🚨 CRITICAL CHECK: ห้ามลบ Admin 🚨
        if (trim($old_data['first_name']) === 'Admin') {
            // ส่งกลับไปหน้าเดิมพร้อม Error
            $error_msg = "ไม่สามารถลบผู้ใช้งานระบบ (Admin) ได้";
            header("Location: index.php?page=dashboard&tab=users&status=error&msg=" . urlencode($error_msg));
            exit();
        }

        // เตรียมชื่อสำหรับ Log
        $deleted_name = $old_data['prefix'] . $old_data['first_name'] . ' ' . $old_data['last_name'];

        // ---------------------------------------------------------
        // ✅ Step 2: ทำ Soft Delete (UPDATE deleted_at)
        // ---------------------------------------------------------
        $sql_delete = "UPDATE user_profiles SET deleted_at = NOW() WHERE user_id = '$target_user_id'";

        if (mysqli_query($conn, $sql_delete)) {

            // ---------------------------------------------------------
            // ✅ Step 3: บันทึก Log
            // ---------------------------------------------------------
            $log_message = "ลบข้อมูลบุคลากร: " . $deleted_name;
            logActivity($conn, $actor_id, $target_user_id, 'delete_user', $log_message);

            // ---------------------------------------------------------
            // ✅ Step 4: Redirect สำเร็จ
            // ---------------------------------------------------------
            $msg = "ลบข้อมูลของ $deleted_name เรียบร้อยแล้ว";
            header("Location: index.php?page=dashboard&tab=users&status=delete&msg=" . urlencode($msg));
            exit();
        } else {
            $error_msg = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
            header("Location: index.php?page=dashboard&tab=users&status=error&msg=" . urlencode($error_msg));
            exit();
        }
    } else {
        header("Location: index.php?page=dashboard&tab=users&status=error&msg=" . urlencode("ไม่พบรหัสผู้ใช้งาน"));
        exit();
    }
}
