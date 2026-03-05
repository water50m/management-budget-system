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


function submitDeleteUser($conn) {
    // 1. รับค่าและกำหนดค่าเริ่มต้น
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $actor_id       = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    // รับค่าหน้าที่จะให้เด้งกลับ (ถ้าไม่มีให้กลับไปหน้า dashboard)
    $submit_page    = isset($_POST['submit_page']) ? $_POST['submit_page'] : 'dashboard';
    $submit_tab     = isset($_POST['submit_tab']) ? $_POST['submit_tab'] : '';

    try {
        // เริ่มต้น Transaction (เพื่อความปลอดภัย ถ้าพังกลางทาง ข้อมูลจะไม่หาย)
        mysqli_begin_transaction($conn);

        // ---------------------------------------------------------
        // 🛑 Validation Checks (ตรวจสอบความถูกต้อง)
        // ---------------------------------------------------------
        
        // เช็คว่า ID ถูกต้องหรือไม่
        if ($target_user_id <= 0) {
            throw new Exception("ไม่พบรหัสผู้ใช้งาน (Invalid User ID)");
        }

        // ✅ Step 1: ดึงข้อมูลเก่ามาตรวจสอบ
        $sql_check = "SELECT prefix, first_name, last_name FROM user_profiles WHERE user_id = '$target_user_id'";
        $result_check = mysqli_query($conn, $sql_check);

        // เช็คว่า Query ผ่านไหม
        if (!$result_check) {
            throw new Exception("Database Error (Check): " . mysqli_error($conn));
        }

        $old_data = mysqli_fetch_assoc($result_check);

        // เช็คว่ามีข้อมูลจริงไหม
        if (!$old_data) {
            throw new Exception("ไม่พบข้อมูลผู้ใช้งานในระบบ id=". $target_user_id);
        }

        // 🚨 CRITICAL CHECK: ห้ามลบ Admin
        if (trim($old_data['first_name']) === 'Admin') {
            throw new Exception("ไม่สามารถลบผู้ใช้งานระบบ (Admin) ได้");
        }

        // เตรียมข้อมูลสำหรับ Log
        $deleted_name = $old_data['prefix'] . $old_data['first_name'] . ' ' . $old_data['last_name'];


        // ---------------------------------------------------------
        // 🗑️ Step 2: ทำ Soft Delete (UPDATE deleted_at)
        // ---------------------------------------------------------
        $sql_delete = "UPDATE user_profiles SET deleted_at = NOW() WHERE user_id = '$target_user_id'";
        
        if (!mysqli_query($conn, $sql_delete)) {
            throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn));
        }

        // ---------------------------------------------------------
        // 📝 Step 3: บันทึก Log
        // ---------------------------------------------------------
        $log_message = "ลบข้อมูลบุคลากร: " . $deleted_name;
        
        // สมมติว่าฟังก์ชัน logActivity คืนค่า true/false (ถ้าฟังก์ชันนี้ไม่มี return ก็ไม่เป็นไรครับ)
        // logActivity($conn, $actor_id, $target_user_id, 'delete_user', $log_message); 
        
        // เพื่อความชัวร์ในการทำ Transaction ควรเช็คการ insert log ด้วย (ถ้าทำได้)
        // แต่ในที่นี้เรียกใช้ฟังก์ชันเดิมตามโค้ดเก่า
        logActivity($conn, $actor_id, $target_user_id, 'delete_user', $log_message);


        // ---------------------------------------------------------
        // ✅ Commit: ยืนยันการทำงานทั้งหมด
        // ---------------------------------------------------------
        mysqli_commit($conn);

        // Redirect สำเร็จ
        $msg = "ลบข้อมูลของ $deleted_name เรียบร้อยแล้ว";
        header("Location: index.php?page=$submit_page&tab=$submit_tab&status=delete&toastMsg=" . urlencode($msg));
        exit();

    } catch (Exception $e) {
        // ---------------------------------------------------------
        // ❌ Rollback: ยกเลิกการกระทำทั้งหมดถ้ามี Error
        // ---------------------------------------------------------
        mysqli_rollback($conn);

        // ดึงข้อความ Error ที่เรา throw ไว้ หรือ Error จากระบบ
        $error_msg = $e->getMessage();

        // Redirect กลับไปแจ้งเตือน Error
        header("Location: index.php?page=$submit_page&tab=$submit_tab&status=error&toastMsg=" . urlencode($error_msg));
        exit();
    }
}

function getDepartmentName($conn, $user_id) {
    // ป้องกัน SQL Injection ด้วยการรับค่าเป็น Integer
    $id = intval($user_id);
    
    $sql = "SELECT d.name_th 
            FROM user_profiles p 
            JOIN departments d ON p.department_id = d.id 
            WHERE p.id = $id 
            LIMIT 1";
            
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['name_th'];
    }
    
    return "ไม่ระบุสังกัด"; // กรณีไม่พบข้อมูล
}

function getAllDepartment($conn){
    $dept_sql = "SELECT * FROM departments ORDER BY thai_name ASC";
    $dept_res = mysqli_query($conn, $dept_sql);
    $dep_list = [];

    if ($dept_res) {
        while ($row = mysqli_fetch_assoc($dept_res)) {
            $dep_list[] = $row;
        }
    }
    return $dep_list;
}

function getBudgetYears($start = 2567, $end = 2580) {
    return range($start, $end);
}

// วิธีใช้
