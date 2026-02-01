<?php
// src/Helper/DashboardHelper.php

// 1. ดึงปีงบประมาณที่มีทั้งหมด
function getFiscalYears($conn)
{
    $sql = "SELECT DISTINCT fiscal_year FROM budget_received ORDER BY fiscal_year DESC";
    $result = mysqli_query($conn, $sql);
    $years = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $years[] = $row['fiscal_year'];
    }
    if (empty($years)) $years[] = date('Y') + 543;
    return $years;
}

// 2. สรุปยอดรวม (KPI Cards)
function getDashboardStats($conn, $year) {
    // 1. หาปีงบประมาณก่อนหน้า
    $prev_year = $year - 1;

    // --- ส่วนที่ 1: ข้อมูลของปีปัจจุบัน (โค้ดเดิม) ---
    // ยอดรับ (ปีปัจจุบัน)
    $sql_rec = "SELECT COALESCE(SUM(amount), 0) as total FROM budget_received WHERE fiscal_year = '$year' AND deleted_at IS NULL";
    $received = mysqli_fetch_assoc(mysqli_query($conn, $sql_rec))['total'];

    // ยอดจ่าย (ปีปัจจุบัน)
    $sql_exp = "SELECT COALESCE(SUM(amount), 0) as total FROM budget_expenses WHERE fiscal_year = '$year' AND deleted_at IS NULL";
    $spent = mysqli_fetch_assoc(mysqli_query($conn, $sql_exp))['total'];

    
    // --- ✅ ส่วนที่ 2: เพิ่มการคำนวณยอดคงเหลือจาก "ปีที่แล้ว" ---
    // รับปีที่แล้ว
    $sql_prev_rec = "SELECT COALESCE(SUM(amount), 0) as total FROM budget_received WHERE fiscal_year = '$prev_year' AND deleted_at IS NULL";
    $prev_received = mysqli_fetch_assoc(mysqli_query($conn, $sql_prev_rec))['total'];

    // จ่ายปีที่แล้ว
    $sql_prev_exp = "SELECT COALESCE(SUM(amount), 0) as total FROM budget_expenses WHERE fiscal_year = '$prev_year' AND deleted_at IS NULL";
    $prev_spent = mysqli_fetch_assoc(mysqli_query($conn, $sql_prev_exp))['total'];

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
function getExpenseByDepartment($conn, $year)
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
                ) AS total_received,
                
                -- 2. หาผลรวมยอดใช้จ่าย (Total Spent)
                (SELECT COALESCE(SUM(be.amount), 0)
                 FROM budget_expenses be
                 JOIN user_profiles u ON be.user_id = u.user_id
                 WHERE u.department_id = d.id 
                 AND be.fiscal_year = '$year' 
                 AND be.deleted_at IS NULL
                ) AS total_spent

            FROM departments d
            -- แสดงเฉพาะภาควิชาที่มีความเคลื่อนไหว (รับ หรือ จ่าย มากกว่า 0)
            HAVING total_received > 0 OR total_spent > 0
            ORDER BY total_received DESC";

    return mysqli_query($conn, $sql);
}

// 4. ข้อมูลกราฟแยกตามหมวดหมู่
function getExpenseByCategory($conn, $year)
{
    // **แก้ตรงนี้ให้ตรงกับชื่อฟิลด์ใน DB จริงของคุณ** (เช่น category หรือ category_id)
    $sql = "SELECT ec.name_th, COALESCE(SUM(be.amount), 0) as total_spent
            FROM expense_categories ec
            JOIN budget_expenses be ON ec.name_en = be.category 
            WHERE be.fiscal_year = '$year' AND be.deleted_at IS NULL
            GROUP BY ec.id, ec.name_th ORDER BY total_spent DESC";

    return mysqli_query($conn, $sql);
}

// 5. Top 5 ผู้ใช้จ่ายสูงสุด
function getTopSpenders($conn, $year)
{
    $sql = "SELECT u.first_name, u.last_name, d.thai_name as dept_name, COALESCE(SUM(be.amount), 0) as total_spent
            FROM user_profiles u
            LEFT JOIN departments d ON u.department_id = d.id
            JOIN budget_expenses be ON u.user_id = be.user_id
            WHERE be.fiscal_year = '$year' AND be.deleted_at IS NULL
            GROUP BY u.user_id ORDER BY total_spent DESC LIMIT 5";
    return mysqli_query($conn, $sql);
}



function showAndSearchOverview($conn)
{
    $data = [];

    // 1. ดึงรายการปีงบประมาณ
    $year_list = getFiscalYears($conn);

    // 2. หาปีที่เลือก (Filter Logic)
    // เช็คจาก $_GET ถ้าไม่มีให้ใช้ปีล่าสุดจากรายการ หรือปีปัจจุบัน
    $selected_year = isset($_GET['year']) ? $_GET['year'] : ($year_list[0] ?? (date('Y') + 543));

    // 3. เรียกฟังก์ชันย่อยดึงข้อมูลตามปีที่เลือก
    $stats    = getDashboardStats($conn, $selected_year);
    $res_dept = getExpenseByDepartment($conn, $selected_year);
    $res_cat  = getExpenseByCategory($conn, $selected_year);
    $res_top  = getTopSpenders($conn, $selected_year);

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
