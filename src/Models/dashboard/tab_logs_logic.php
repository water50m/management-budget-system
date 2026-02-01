<?php

function showAndManageLogs($conn)
{
    // === [ใหม่] แท็บที่ 4: ประวัติการใช้งาน (System Logs) ===
    $data['title'] = "ประวัติการทำงานของระบบ (Activity Logs)";
    $data['view_mode'] = 'admin_activity_logs';

    // ---------------------------------------------------------
    // 1. รับค่า Pagination & Filter
    // ---------------------------------------------------------
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // Default 20 รายการ
    $page  = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Filter User Permission
    $safe_seer_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    // ถ้าไม่ใช่ user_id = 1 (Super Admin) ให้เห็นแค่ของตัวเอง (หรือตาม Logic เดิมของคุณ)
    $where_sql = ($safe_seer_id == 1) ? "WHERE 1=1" : "WHERE l.actor_id = '$safe_seer_id'";

    // ---------------------------------------------------------
    // 2. Query นับจำนวนทั้งหมด (Count Total)
    // ---------------------------------------------------------
    $count_sql = "SELECT COUNT(*) as total FROM activity_logs l $where_sql";
    $res_count = mysqli_query($conn, $count_sql);
    $total_rows = ($res_count) ? mysqli_fetch_assoc($res_count)['total'] : 0;
    
    // คำนวณจำนวนหน้าทั้งหมด
    if ($limit > 0) {
        $total_pages = ceil($total_rows / $limit);
    } else {
        $total_pages = 1; // กรณี limit=0 (ทั้งหมด)
    }

    // ---------------------------------------------------------
    // 3. Query ดึงข้อมูล (Main Query)
    // ---------------------------------------------------------
    // SQL: ดึงข้อมูล Log + ชื่อคนทำ (Actor) + ชื่อคนโดน (Target)
    $sql = "SELECT 
                l.id, l.action_type, l.description, l.created_at,
                
                -- ข้อมูลคนทำ (Actor)
                u_actor.username AS actor_username,
                u_actor.role_id AS actor_role,
                CONCAT(pa.prefix, pa.first_name, ' ', pa.last_name) AS actor_name,
                
                -- ข้อมูลคนโดน (Target)
                u_target.username AS target_username,
                CONCAT(pt.prefix, pt.first_name, ' ', pt.last_name) AS target_name,
                l.target_id AS target_id,
                l.status AS status

            FROM activity_logs l
            -- JOIN ครั้งที่ 1: หาคนทำ (Actor)
            LEFT JOIN users u_actor ON l.actor_id = u_actor.id
            LEFT JOIN user_profiles pa ON l.actor_id = pa.user_id
            
            -- JOIN ครั้งที่ 2: หาคนโดน (Target)
            LEFT JOIN users u_target ON l.target_id = u_target.id
            LEFT JOIN user_profiles pt ON l.target_id = pt.user_id
            
            $where_sql
            ORDER BY l.created_at DESC ";

    // เพิ่ม LIMIT offset (ถ้า limit > 0)
    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }

    // Run Query
    $data['logs'] = [];
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // แปลงวันที่ให้สวยงาม
            $row['thai_datetime'] = date('d/m/Y H:i', strtotime($row['created_at']));
            $data['logs'][] = $row;
        }
    }

    // ---------------------------------------------------------
    // 4. ส่งค่า Pagination กลับไปที่ View
    // ---------------------------------------------------------
    $data['pagination'] = [
        'current_page' => $page,
        'total_pages'  => $total_pages,
        'total_rows'   => $total_rows,
        'limit'        => $limit
    ];

    return $data;
}

function restoreData($conn)
{
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $data_id     = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
    $target_id   = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $log_id  = isset($_POST['logId']) ? intval($_POST['logId']) : 0;
    $actor_id    = $_SESSION['user_id']; // ID คนกดกู้คืน

    // ตัวแปรสำหรับ Query
    $sql_restore = "";
    $log_msg = "";
    $redirect_tab = "logs"; // Default tab

    // 2. เลือกคำสั่ง SQL ตาม action_type
    switch ($action_type) {

        case 'delete_expense':
            // กู้คืนรายจ่าย (budget_expense)
            if ($data_id > 0) {
                $sql_restore = "UPDATE budget_expenses SET deleted_at = NULL WHERE id = '$data_id'";
                $log_msg = "กู้คืนข้อมูลรายจ่าย (Expense ID: $data_id)";
                $redirect_tab = "expense"; // กู้เสร็จอาจจะอยากกลับไปดูหน้า Expense
            }
            break;

        case 'delete_received':
            // กู้คืนรายรับ (budget_received)
            if ($data_id > 0) {
                $sql_restore = "UPDATE budget_received SET deleted_at = NULL WHERE id = '$data_id'";
                $log_msg = "กู้คืนข้อมูลรายรับ (Received ID: $data_id)";
                $redirect_tab = "received";
            }
            break;

        case 'delete_user':
            // กู้คืนผู้ใช้งาน (user_profiles)
            if ($target_id > 0) {
                // เช็คดีๆ ว่าใน DB ชื่อตาราง user_profile หรือ user_profiles (ปกติมักมี s)
                // ตามโจทย์ให้ใช้ user_profiles.id = target_id
                $sql_restore = "UPDATE user_profiles SET deleted_at = NULL WHERE user_id = '$target_id'";

                // *เพิ่มเติม: ถ้ามีตาราง users ที่ใช้ login อาจต้องกู้คืนด้วย
                // mysqli_query($conn, "UPDATE users SET deleted_at = NULL WHERE upid = '$target_id'");

                $log_msg = "กู้คืนข้อมูลผู้ใช้งาน (User Profile ID: $target_id)";
                $redirect_tab = "users";
            }
            break;

        default:
            // กรณี Action type ไม่ถูกต้อง
            header("Location: index.php?page=dashboard&tab=logs&status=error&msg=" . urlencode("ไม่พบประเภทข้อมูลที่ต้องการกู้คืน"));
            exit();
    }

    // 3. รันคำสั่ง SQL
    if (!empty($sql_restore) && mysqli_query($conn, $sql_restore)) {

        if ($log_id > 0) {
            $sql_update_log = "UPDATE activity_logs SET status = 'restored' WHERE id = '$log_id'";
            mysqli_query($conn, $sql_update_log);
        }
        // บันทึก Log ว่ามีการกู้คืน
        logActivity($conn, $actor_id, ($data_id > 0 ? $data_id : $target_id), 'restore_data', $log_msg);

        // Redirect กลับไปหน้า Logs หรือหน้าที่เกี่ยวข้อง พร้อม Toast สีฟ้า (restore)
        header("Location: index.php?page=dashboard&tab=logs&status=restore&toastMsg=" . urlencode($log_msg));
        exit();
    } else {
        $error = "กู้คืนไม่สำเร็จ: " . mysqli_error($conn);
        header("Location: index.php?page=dashboard&tab=logs&status=error&toastMsg=" . urlencode($error));
        exit();
    }
    require_once __DIR__ . '/../../views/dashboard/index.php';
}

