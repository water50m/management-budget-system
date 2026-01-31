<?php
function logActivity($conn, $actor_id, $target_id, $action, $desc, $data_target_id = 0) {
    // 1. ตรวจสอบการเชื่อมต่อ Database ก่อนเสมอ
    if (!$conn) {
        error_log("LogActivity Error: Database connection is missing.");
        return false;
    }

    try {
        // 2. แปลงค่า ID ให้เป็นตัวเลขแน่นอน (ป้องกัน SQL Injection)
        $actor_id = intval($actor_id);
        $target_id = intval($target_id);
        $data_target_id = intval($data_target_id);

        // 3. Escape String ป้องกันอักขระพิเศษ
        $desc = mysqli_real_escape_string($conn, $desc);
        $action = mysqli_real_escape_string($conn, $action); // Escape action ด้วยเผื่อมีใครส่งแปลกๆ มา

        // 4. เตรียม SQL
        $sql = "INSERT INTO activity_logs (actor_id, target_id, action_type, description, data_id, created_at) 
                VALUES ($actor_id, $target_id, '$action', '$desc', $data_target_id, NOW())";
        
        // *หมายเหตุ: ผมเติม created_at = NOW() ให้ด้วย ปกติตาราง Log มักจะมี

        // 5. สั่งทำงาน และเช็คผลลัพธ์
        if (!mysqli_query($conn, $sql)) {
            // กรณี Query พัง: ให้บันทึก error ลงไฟล์ log ของ Server (ไม่แสดงหน้าเว็บ)
            error_log("LogActivity SQL Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        return true; // สำเร็จ

    } catch (Exception $e) {
        // กรณีเกิดข้อผิดพลาดรุนแรงอื่นๆ
        error_log("LogActivity Exception: " . $e->getMessage());
        return false;
    }
}