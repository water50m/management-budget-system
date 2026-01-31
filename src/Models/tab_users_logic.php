<?php

function showAndSearchUsers($conn)
{
    $data['title'] = "รายชื่อผู้ใช้งานทั้งหมด";
    $data['view_mode'] = 'admin_user_table'; // แก้ให้ตรงกับฝั่ง View

    // ---------------------------------------------------------
    // 2. รับค่าจากตัวกรอง (Filter Inputs)
    // ---------------------------------------------------------
    // รับค่า search_text (รวมชื่อและ username)
    $search_text = isset($_GET['search_text']) ? mysqli_real_escape_string($conn, $_GET['search_text']) : '';

    // รับค่า ภาควิชา
    $dept_user = isset($_GET['dept_user']) ? intval($_GET['dept_user']) : 0;

    // ✅ รับค่า Role (เพิ่มใหม่)
    $role_user = isset($_GET['role_user']) ? mysqli_real_escape_string($conn, $_GET['role_user']) : '';

    // ---------------------------------------------------------
    // 3. สร้าง SQL
    // ---------------------------------------------------------
    $sql = "SELECT u.*, p.*, d.thai_name AS department ,r.description AS role_user
                            FROM users u
                            LEFT JOIN roles r ON u.role_id = r.id
                            LEFT JOIN user_profiles p ON u.id = p.user_id
                            LEFT JOIN departments d ON p.department_id = d.id
                            WHERE 1=1 
                            ";

    // ---------------------------------------------------------
    // 4. ใส่ Logic Filter
    // ---------------------------------------------------------
    // filter deleted data
    $sql .= "AND p.deleted_at IS NULL";
    // filter for some admin
    $sql = applyPermissionFilter($sql);

    // ✅ 4.1 ค้นหาแบบรวม (Omni-search): ชื่อ OR นามสกุล OR Username
    if (!empty($search_text)) {
        $sql .= " AND (
                            p.first_name LIKE '%$search_text%' OR 
                            p.last_name LIKE '%$search_text%' OR 
                            u.username LIKE '%$search_text%'
                        ) ";
    }

    // 4.2 กรองภาควิชา
    if ($dept_user > 0) {
        $sql .= " AND d.id = $dept_user ";
    }

    // ✅ 4.3 กรอง Role
    if (!empty($role_user)) {
        $sql .= " AND u.role_id = '$role_user' ";
    }

    // ---------------------------------------------------------
    // 5. ประมวลผลข้อมูล
    // ---------------------------------------------------------
    $sql .= " ORDER BY d.id ASC, p.first_name ASC"; // เรียงตามภาควิชา -> ชื่อ

    $data['user_list'] = [];
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        // ดึงยอดเงินคงเหลือ (ใช้ Function เดิมของคุณ)
        $row['remaining_balance'] = getRemainingBalance($conn, $row['id']);
        $data['user_list'][] = $row;
    }


    // ---------------------------------------------------------
    // 6. ส่งค่าตัวกรองกลับไปที่ View (เพื่อให้ Component แสดงค่าเดิม)
    // ---------------------------------------------------------
    $data['filters'] = [
        'search_text' => $search_text,
        'dept_user'   => $dept_user,
        'role_user'   => $role_user
    ];
    return $data;
}
