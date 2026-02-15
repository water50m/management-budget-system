<?php
// src/Helper/DashboardHelper.php



// 2. สรุปยอดรวม (KPI Cards)
function getDashboardStats($conn, $year, $dept_sql)
{

    // รับค่าปีและ ภาควิชา





    // 1. หาปีงบประมาณก่อนหน้า
    $prev_year = $year - 1;

    // --- ส่วนที่ 1: ข้อมูลของปีปัจจุบัน (โค้ดเดิม) ---
    // ยอดรับ (ปีปัจจุบัน)
    $sql_rec = "SELECT COALESCE(SUM(b.amount), 0) as total FROM budget_received b LEFT JOIN user_profiles u ON b.user_id = u.user_id WHERE b.fiscal_year = '$year' AND b.deleted_at IS NULL $dept_sql";
    $received = $conn->query($sql_rec)->fetch_assoc()['total'];

    // ยอดจ่าย (ปีปัจจุบัน)
    $sql_exp = "SELECT COALESCE(SUM(b.amount), 0) as total FROM budget_expenses b LEFT JOIN user_profiles u ON b.user_id = u.user_id WHERE b.fiscal_year = '$year' AND b.deleted_at IS NULL $dept_sql";
    $spent = $conn->query($sql_exp)->fetch_assoc()['total'];


    // --- ✅ ส่วนที่ 2: เพิ่มการคำนวณยอดคงเหลือจาก "ปีที่แล้ว" ---
    // รับปีที่แล้ว
    $sql_prev_rec = "SELECT COALESCE(SUM(b.amount), 0) as total FROM budget_received b LEFT JOIN user_profiles u ON b.user_id = u.user_id WHERE b.fiscal_year = '$prev_year' AND b.deleted_at IS NULL $dept_sql";
    $prev_received = $conn->query($sql_prev_rec)->fetch_assoc()['total'];

    // จ่ายปีที่แล้ว
    $sql_prev_exp = "SELECT COALESCE(SUM(b.amount), 0) as total FROM budget_expenses b LEFT JOIN user_profiles u ON b.user_id = u.user_id WHERE b.fiscal_year = '$prev_year' AND b.deleted_at IS NULL $dept_sql";
    $prev_spent = $conn->query($sql_prev_exp)->fetch_assoc()['total'];

    // เงินคงเหลือยกยอด (รับปีก่อน - จ่ายปีก่อน)
    $carry_over = $prev_received - $prev_spent;


    // --- ส่วนที่ 3: สรุปผล ---
    // ยอดคงเหลือสุทธิ (คงเหลือปีนี้ + ยอกยอดมา)
    $balance = ($received + $carry_over) - $spent;

    // อัตราการเบิกจ่าย (คิดจาก เงินปีนี้ + เงินยกยอด)
    $total_budget = $received + $carry_over;
    $utilization = ($total_budget > 0) ? ($spent / $total_budget) * 100 : 0;




    return [
        'received' => $received,       // งบใหม่ปีนี้
        'carry_over' => $carry_over,   // ✅ เงินยกยอดจากปีที่แล้ว
        'total_budget' => $total_budget, // ✅ งบรวมสุทธิ (ใหม่+เก่า)
        'spent' => $spent,
        'balance' => $balance,
        'utilization' => $utilization,
        'prev_year' => $prev_year      // ส่งเลขปีที่แล้วออกไปโชว์ด้วย
    ];
}

// 3. ข้อมูลกราฟแยกตามภาควิชา
function getExpenseByDepartment($conn, $year, $dept_sql)
{
    // ใช้ Subquery เพื่อคำนวณยอดรับและยอดจ่ายแยกกันตามภาควิชา
    $sql = "SELECT 
                d.thai_name,
                -- 1. หาผลรวมงบที่ได้รับ (Total Received)
                (SELECT COALESCE(SUM(br.amount), 0)
                 FROM budget_received br
                 JOIN user_profiles u ON br.user_id = u.user_id
                 WHERE u.department_id = d.id 
                 AND br.fiscal_year = '$year' 
                 AND br.deleted_at IS NULL
                 $dept_sql
                ) AS total_received,
                
                -- 2. หาผลรวมยอดใช้จ่าย (Total Spent)
                (SELECT COALESCE(SUM(be.amount), 0)
                 FROM budget_expenses be
                 JOIN user_profiles u ON be.user_id = u.user_id
                 WHERE u.department_id = d.id 
                 AND be.fiscal_year = '$year' 
                 AND be.deleted_at IS NULL
                 $dept_sql
                ) AS total_spent

            FROM departments d
            -- แสดงเฉพาะภาควิชาที่มีความเคลื่อนไหว (รับ หรือ จ่าย มากกว่า 0)
            HAVING total_received > 0 OR total_spent > 0
            ORDER BY total_received DESC";

    return $conn->query($sql);
}

// 4. ข้อมูลกราฟแยกตามหมวดหมู่
function getExpenseByCategory($conn, $year, $dept_sql)
{
    // **แก้ตรงนี้ให้ตรงกับชื่อฟิลด์ใน DB จริงของคุณ** (เช่น category หรือ category_id)
    $sql = "SELECT ec.name_th, COALESCE(SUM(be.amount), 0) as total_spent
            FROM expense_categories ec
            JOIN budget_expenses be ON ec.id = be.category_id 
            LEFT JOIN user_profiles u ON be.user_id = u.user_id
            WHERE be.fiscal_year = '$year' AND be.deleted_at IS NULL $dept_sql
            GROUP BY ec.id, ec.name_th ORDER BY total_spent DESC";

    return $conn->query($sql);
}

// 5. Top 5 ผู้ใช้จ่ายสูงสุด
function getTopSpenders($conn, $year, $dept_sql)
{
    $sql = "SELECT u.first_name, u.last_name, d.thai_name as dept_name, COALESCE(SUM(be.amount), 0) as total_spent
            FROM user_profiles u
            LEFT JOIN departments d ON u.department_id = d.id
            JOIN budget_expenses be ON u.user_id = be.user_id
            WHERE be.fiscal_year = '$year' AND be.deleted_at IS NULL 
            $dept_sql
            GROUP BY u.user_id ORDER BY total_spent DESC LIMIT 5";
    return $conn->query($sql);
}



function showAndSearchOverview($conn)
{
    $data = [];

    // 1. ดึงรายการปีงบประมาณ
    $year_list = getFiscalYearOptions($conn);

    // 2. หาปีที่เลือก (Filter Logic)
    // เช็คจาก $_GET ถ้าไม่มีให้ใช้ปีล่าสุดจากรายการ หรือปีปัจจุบัน
    $selected_year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : (date('Y') + 543);
    $raw_dept = $_GET['department_id'] ?? '';
    $dept_id = ($raw_dept !== '') ? (int)$raw_dept : ''; // แก้ Logic ให้ถูกต้องตามที่คุยกัน
    $role = $_SESSION['role'];
    if ($dept_id > 0 && $role != 'user') {
        $seleted_dmp = " AND u.department_id = $dept_id ";
    } else if ($role == 'high-admin') {// --ทั้งหมด-- -> 0
        $seleted_dmp = "";
    } else if ($dept_id = '' && $role != 'high-admin') {
        $seleted_dmp = "AND 1 = 0 ";
    } else {
        $seleted_dmp = "AND 1 = 0 ";
    } 
    // 3. เรียกฟังก์ชันย่อยดึงข้อมูลตามปีที่เลือก
    $stats    = getDashboardStats($conn, $selected_year, $seleted_dmp);
    $res_dept = getExpenseByDepartment($conn, $selected_year, $seleted_dmp);
    $res_cat  = getExpenseByCategory($conn, $selected_year, $seleted_dmp);
    $res_top  = getTopSpenders($conn, $selected_year, $seleted_dmp);

    // 4. ห่อรวมกันใน Array
    $data['overview_data'] = [
        'year_list'     => $year_list,
        'selected_year' => $selected_year,
        'stats'         => $stats,
        'res_dept'      => $res_dept,
        'res_cat'       => $res_cat,
        'res_top'       => $res_top
    ];

    return $data;
}



function getFpaSummary($conn, $year, $dept_id = 0)
{
    
    $sql_filter = "WHERE 1=1";
    $sql_filter = applyPermissionFilter($sql_filter);
    if ($dept_id > 0) {
        $sql_filter .= " AND p.department_id = $dept_id";
    } else if ($dept_id = '') {
        $sql_filter .= " AND 1=0 ";
    }



    // SQL Query ที่เราคุยกัน
    $sql = "SELECT 
                p.user_id AS user_id,
                CONCAT(COALESCE(p.prefix, ' '), p.first_name, ' ', p.last_name) AS name,

                SUM(CASE WHEN b.category_id = 1 AND b.fiscal_year = $year THEN b.amount ELSE 0 END) AS travel,
                
                SUM(CASE WHEN b.category_id = 2 AND b.fiscal_year = $year THEN b.amount ELSE 0 END) AS book,
                
                SUM(CASE WHEN b.category_id = 3 AND b.fiscal_year = $year THEN b.amount ELSE 0 END) AS comp,
                
                SUM(CASE WHEN b.category_id = 4 AND b.fiscal_year = $year THEN b.amount ELSE 0 END) AS sci
                
            FROM user_profiles p
            LEFT JOIN budget_expenses b ON p.user_id = b.user_id

            $sql_filter
            GROUP BY p.user_id, p.prefix, p.first_name, p.last_name
            HAVING (travel + book + comp + sci) > 0
            ORDER BY p.user_id ASC";

    $result = $conn->query($sql);

    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}


function getFiscalYearOptions($conn)
{
    // 1. หาปีน้อยสุดและมากสุดที่มีในฐานข้อมูล
    $sql = "SELECT MIN(fiscal_year) as min_year, MAX(fiscal_year) as max_year FROM budget_received";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $currentYear = date('Y') + 543; // ถ้าเก็บเป็น พ.ศ. ให้ +543 (ถ้าเก็บ ค.ศ. เอา +543 ออก)
    // หรือถ้าใน DB เก็บเป็น ค.ศ. ให้ใช้: $currentYear = date('Y');

    // ตรวจสอบว่ามีข้อมูลใน DB ไหม (ถ้าไม่มี ให้เริ่มจากปีปัจจุบัน)
    $dbMin = $row['min_year'] ? $row['min_year'] : $currentYear;
    $dbMax = $row['max_year'] ? $row['max_year'] : $currentYear;

    // 2. คำนวณขอบเขตล่าง (Start Year)
    // เอาค่าที่น้อยที่สุด ระหว่าง (ปีใน DB - 1) กับ (ปีปัจจุบัน - 1)
    $startYear = min($dbMin - 1, $currentYear - 1);

    // 3. คำนวณขอบเขตบน (End Year)
    // เอาค่าที่มากที่สุด ระหว่าง (ปีใน DB + 1) กับ (ปีปัจจุบัน + 1)
    $endYear = max($dbMax + 1, $currentYear + 1);

    // 4. สร้าง Array ช่วงปี
    $years = [];
    // วนลูปจากมากไปน้อย (ปีใหม่สุดอยู่บน) หรือ น้อยไปมาก ตามชอบ
    for ($y = $endYear; $y >= $startYear; $y--) {
        $years[] = $y;
    }

    return $years;
}


function getDepartments($conn)
{
    $sql = "SELECT id, thai_name FROM departments ORDER BY thai_name ASC";
    $result = $conn->query($sql);

    $departments = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
    return $departments;
}


