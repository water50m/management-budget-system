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
    $safe_seer_role = mysqli_real_escape_string($conn, $_SESSION['role']);
    // ถ้าไม่ใช่ user_id = 1 (Super Admin) ให้เห็นแค่ของตัวเอง (หรือตาม Logic เดิมของคุณ)
    $where_sql = ($safe_seer_role == 'high-admin') ? "WHERE 1=1" : "WHERE 1=0";

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
                CONCAT(pa.prefix, ' ', pa.first_name, ' ', pa.last_name) AS actor_name,
                
                -- ข้อมูลคนโดน (Target)
                u_target.username AS target_username,
                CONCAT(pt.prefix, ' ', pt.first_name, ' ', pt.last_name) AS target_name,
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
            $row['thai_datetime'] = dateToThai($row['created_at']);
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

    $log_id  = isset($_POST['logId']) ? intval($_POST['logId']) : 0;
    $actor_id    = $_SESSION['user_id']; // ID คนกดกู้คืน

    // 1. ดึงข้อมูลจาก Log เก่าก่อน
    $data_id     = 0;
    $target_id   = 0;
    $sql_log = "SELECT target_id, data_id FROM activity_logs WHERE id= $log_id";
    $result = mysqli_query($conn, $sql_log);
    if ($row = mysqli_fetch_assoc($result)) {
        $data_id = $row['data_id'];
        $target_id = $row['target_id'];
    }

    // ตัวแปรสำหรับ Redirect และ UX
    $log_msg = "";
    $redirect_tab = "logs";
    $fiscal_year = 0;

    // =========================================================
    // 🔴 เริ่มต้น TRANSACTION
    // =========================================================
    mysqli_begin_transaction($conn);

    try {
        // 2. เลือกคำสั่ง SQL ตาม action_type
        switch ($action_type) {

            case 'delete_expense':
                // =================================================
                // ✅ กู้คืนรายจ่าย (แบบ Full Restore)
                // =================================================
                if ($data_id > 0) {
                    // 2.1 กู้คืนตารางแม่ (budget_expenses)
                    $sql_restore_exp = "UPDATE budget_expenses SET deleted_at = NULL WHERE id = '$data_id'";
                    if (!mysqli_query($conn, $sql_restore_exp)) {
                        throw new Exception("กู้คืน Expense ไม่สำเร็จ: " . mysqli_error($conn));
                    }

                    // 2.2 กู้คืนตารางลูก (budget_usage_logs) ⭐ เพิ่มส่วนนี้
                    $sql_restore_logs = "UPDATE budget_usage_logs SET deleted_at = NULL WHERE expense_id = '$data_id'";
                    if (!mysqli_query($conn, $sql_restore_logs)) {
                        throw new Exception("กู้คืน Usage Logs ไม่สำเร็จ: " . mysqli_error($conn));
                    }

                    // 2.3 ดึง Fiscal Year (เพื่อ UX)
                    $res_fy = mysqli_query($conn, "SELECT fiscal_year FROM budget_expenses WHERE id = '$data_id'");
                    if ($row_fy = mysqli_fetch_assoc($res_fy)) {
                        $fiscal_year = $row_fy['fiscal_year'];
                    }

                    $log_msg = "กู้คืนข้อมูลรายจ่าย (Expense ID: $data_id)";
                    $redirect_tab = "expense";
                }
                break;

            case 'delete_received':
                // กู้คืนรายรับ
                if ($data_id > 0) {
                    $sql_restore = "UPDATE budget_received SET deleted_at = NULL WHERE id = '$data_id'";
                    if (!mysqli_query($conn, $sql_restore)) {
                        throw new Exception("กู้คืน Received ไม่สำเร็จ");
                    }

                    $res_fy = mysqli_query($conn, "SELECT fiscal_year FROM budget_received WHERE id = '$data_id'");
                    if ($row_fy = mysqli_fetch_assoc($res_fy)) {
                        $fiscal_year = $row_fy['fiscal_year'];
                    }

                    $log_msg = "กู้คืนข้อมูลรายรับ (Received ID: $data_id)";
                    $redirect_tab = "received";
                }
                break;

            case 'delete_user':
                // กู้คืนผู้ใช้งาน
                if ($target_id > 0) {
                    $sql_restore = "UPDATE user_profiles SET deleted_at = NULL WHERE user_id = '$target_id'";
                    if (!mysqli_query($conn, $sql_restore)) {
                        throw new Exception("กู้คืน User ไม่สำเร็จ");
                    }
                    // *ถ้าต้องกู้ตาราง Users หลักด้วย ให้ใส่เพิ่มตรงนี้

                    $log_msg = "กู้คืนข้อมูลผู้ใช้งาน (User Profile ID: $target_id)";
                    $redirect_tab = "users";
                }
                break;

            default:
                throw new Exception("ไม่พบประเภทข้อมูลที่ต้องการกู้คืน");
        }

        // 3. อัปเดตสถานะใน Log เดิมว่า "restored" (กู้คืนแล้ว)
        if ($log_id > 0) {
            $sql_update_log = "UPDATE activity_logs SET status = 'restored' WHERE id = '$log_id'";
            mysqli_query($conn, $sql_update_log);
        }

        // 4. บันทึก Log ใหม่ ว่ามีการกู้คืนเกิดขึ้น
        if (function_exists('logActivity')) {
            logActivity($conn, $actor_id, $target_id, 'restore_data', $log_msg);
        }

        // =========================================================
        // ✅ COMMIT TRANSACTION (บันทึกข้อมูลจริง)
        // =========================================================
        mysqli_commit($conn);

        // ตั้งค่า Session สำหรับ UX
        $_SESSION['show_btn'] = true;
        $_SESSION['tragettab'] = $redirect_tab;
        $_SESSION['tragetfilters']  = $data_id;
        $_SESSION['fiscal_year'] = $fiscal_year;

        // Redirect Success
        header("Location: index.php?page=dashboard&tab=logs&status=restore&toastMsg=" . urlencode($log_msg));
        exit();

    } catch (Exception $e) {
        // =========================================================
        // ⚫ ROLLBACK TRANSACTION (ยกเลิกทั้งหมดถ้ามี error)
        // =========================================================
        mysqli_rollback($conn);

        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=dashboard&tab=logs&status=error&toastMsg=" . urlencode($error));
        exit();
    }
}