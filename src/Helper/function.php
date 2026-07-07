<?php


function dateToThai($strDate) {
    if (!$strDate || $strDate == "0000-00-00") return "-";

    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    
    // Array เดือนย่อภาษาไทย
    $strMonthCut = Array(
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", 
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    );
    
    $strMonthThai = $strMonthCut[$strMonth];
    
    return "$strDay $strMonthThai $strYear";
}

function applyPermissionFilter($sql)
{

    // เช็คว่ามีค่า seer_filter ส่งมาไหม
    if (isset($_SESSION['seer'])) {

        $user_id = $_SESSION['user_id'];
        $seer = $_SESSION['seer'];
        
        // เริ่มต้นด้วย WHERE 1=1 เพื่อให้ง่ายต่อการต่อ String (AND ...)
        // และเป็นการเริ่ม Block WHERE ของ Query นี้

        if ($seer == 0) {
            // ✅ กรณี 0 (High Admin): เห็นทั้งหมด
            // ไม่ต้องเติม AND อะไร ปล่อยผ่านเลย
        } elseif ($seer == 7) {
            // ✅ กรณี 7 (User): เห็นเฉพาะของตัวเอง
            // กรองจากตาราง received (a.user_id) หรือ profiles (p.user_id) ก็ได้
            $sql .= " AND p.user_id = " . intval($user_id);
        } else {
            // ✅ กรณีอื่นๆ (Admin ภาควิชา): เห็นเฉพาะภาควิชาตัวเอง
            // ค่า seer_filter ในเคสนี้คือ Department ID
            $sql .= " AND p.department_id = " . intval($seer);
        }
    } else {
        // ❌ Safety: ถ้าไม่มีตัวแปร seer_filter ส่งมา ให้ปิดการมองเห็น
        $sql .= " WHERE 1=0 ";
    }
    
    return $sql;
}

// คืน department_id ของ user (จาก user_profiles) หรือ null ถ้าไม่พบ
function getDepartmentIdForUser($conn, $user_id)
{
    $user_id = intval($user_id);
    $sql = "SELECT department_id FROM user_profiles WHERE user_id = $user_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    return (int) mysqli_fetch_assoc($res)['department_id'];
}

// ตรวจสิทธิ์ฝั่ง server ก่อนแก้ไข/ลบข้อมูลงบของ user คนหนึ่ง
// ใช้ semantics เดียวกับ applyPermissionFilter: seer=0 -> high-admin (ผ่านทุกกรณี),
// seer=7 -> user (แก้ได้เฉพาะของตัวเอง), อื่นๆ -> admin ภาควิชา (แก้ได้เฉพาะคนในภาควิชาตัวเอง)
function canManageBudgetForUser($conn, $target_user_id)
{
    if (!isset($_SESSION['seer'])) {
        return false;
    }
    $seer = $_SESSION['seer'];

    if ($seer == 0) {
        return true;
    }
    if ($seer == 7) {
        return intval($target_user_id) === intval($_SESSION['user_id'] ?? -1);
    }
    $dept_id = getDepartmentIdForUser($conn, $target_user_id);
    return $dept_id !== null && intval($dept_id) === intval($seer);
}

function current_fiscal_year()
{
    $current_fiscal_year = (date('n') >= 10) ? date('Y') + 544 : date('Y') + 543;
    return $current_fiscal_year;
}

function getRemainingBalance($conn, $user_id)
{
    $user_id = intval($user_id);

    // 1. ยอดรับคงเหลือ นับแบบรายก้อน (หักเฉพาะยอดที่ใช้จริงจากก้อนนั้นๆ ก่อนเช็คหมดอายุ)
    // ใช้ logic เดียวกับ getAvailableBudgetForUser() ใน tab_expense_logic.php
    // ห้ามใช้วิธี "รวมรับทั้งหมด - รวมจ่ายทั้งหมด" เพราะจะทำให้ยอดผิดถ้ามีก้อนที่หมดอายุ
    // แต่เคยถูกใช้จ่ายไปแล้ว (เงินที่ใช้จากก้อนหมดอายุจะถูกหักซ้ำจากก้อนที่ยังไม่หมดอายุ)
    $sql_available = "SELECT COALESCE(SUM(available_amount), 0) as total_available
                    FROM (
                        SELECT GREATEST(
                            a.amount - COALESCE((
                                SELECT SUM(amount_used)
                                FROM budget_usage_logs
                                WHERE approval_id = a.id
                                AND deleted_at IS NULL
                            ), 0),
                            0
                        ) as available_amount
                        FROM budget_received a
                        WHERE a.user_id = $user_id
                        AND a.deleted_at IS NULL
                        AND CURDATE() <=
                            CASE
                                WHEN MONTH(a.approved_date) >= 10
                                THEN CONCAT(YEAR(a.approved_date) + 3, '-09-30')
                                ELSE CONCAT(YEAR(a.approved_date) + 2, '-09-30')
                            END
                    ) available_budget";

    $res_available = mysqli_query($conn, $sql_available);
    $available_budget = floatval(mysqli_fetch_assoc($res_available)['total_available']);

    // 2. หักยอดตัดที่ยังไม่มีแหล่งเงินรองรับ (pending, ยังไม่ถูกผูกกับก้อนไหน)
    $sql_pending = "SELECT COALESCE(SUM(unallocated_amount), 0) as total_unallocated
                    FROM (
                        SELECT GREATEST(
                            e.amount - COALESCE(SUM(bul.amount_used), 0),
                            0
                        ) as unallocated_amount
                        FROM budget_expenses e
                        LEFT JOIN budget_usage_logs bul
                            ON bul.expense_id = e.id
                            AND bul.deleted_at IS NULL
                        WHERE e.user_id = $user_id
                        AND e.deleted_at IS NULL
                        GROUP BY e.id, e.amount
                        HAVING unallocated_amount > 0
                    ) pending_expenses";

    $res_pending = mysqli_query($conn, $sql_pending);
    $pending_unallocated = floatval(mysqli_fetch_assoc($res_pending)['total_unallocated']);

    // ไม่ clamp ที่ 0 เพราะค่าติดลบมีความหมาย = ใช้จ่ายเกินยอดรับที่ยังไม่หมดอายุ (เกินงบ)
    // และหน้า users_view.php ใช้เครื่องหมายนี้แสดงสีแดงเตือนอยู่
    return $available_budget - $pending_unallocated;
}


// =================================================================================================
// ---------------------------------------------------------
// 1. ฟังก์ชันคำนวณ Offset (ใช้ฝั่ง Logic/Database)
// ---------------------------------------------------------
function getPaginationParams($default_limit = 10)
{
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : $default_limit;
    if ($limit < 1 && $limit != 0) $limit = $default_limit; // กันค่าติดลบ (ยกเว้น 0 ที่แปลว่าทั้งหมด)

    $page  = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
    if ($page < 1) $page = 1;

    $offset = ($page - 1) * $limit;

    return [
        'limit'  => $limit,
        'page'   => $page,
        'offset' => $offset
    ];
}


function getAllAdminRole($conn)
{
    $sql_roles = "SELECT role_name FROM roles WHERE id != 7";
    $res_roles = mysqli_query($conn, $sql_roles);

    // 2. สร้าง Array เพื่อเก็บชื่อ Role
    $role_list = [];

    if ($res_roles) {
        while ($row = mysqli_fetch_assoc($res_roles)) {
            // เก็บเฉพาะชื่อ role ลงใน Array
            $role_list[] = $row['role_name'];
        }
    }
    return $role_list;
}
