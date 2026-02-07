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

function current_fiscal_year()
{
    $current_fiscal_year = (date('n') >= 10) ? date('Y') + 544 : date('Y') + 543;
    return $current_fiscal_year;
}

function getRemainingBalance($conn, $user_id)
{
    $today = date('Y-m-d');

    // 1. หา "เงินเข้า"
    $sql_income = "SELECT COALESCE(SUM(amount), 0) as total_approved 
                    FROM budget_received 
                    WHERE user_id = $user_id 
                    AND approved_date >= DATE_SUB('$today', INTERVAL 2 YEAR)
                    AND deleted_at IS NULL
                    ";

    $res_in = mysqli_query($conn, $sql_income);
    $row_in = mysqli_fetch_assoc($res_in);
    $total_approved = floatval($row_in['total_approved']);

    // 2. หา "เงินออก"
    $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total_spent 
                        FROM budget_expenses 
                        WHERE user_id = $user_id
                        AND deleted_at IS NULL";

    $res_ex = mysqli_query($conn, $sql_expense);
    $row_ex = mysqli_fetch_assoc($res_ex);
    $total_spent = floatval($row_ex['total_spent']);


    return $total_approved - $total_spent;
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
