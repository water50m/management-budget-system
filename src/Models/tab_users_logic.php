<?php

function showAndSearchUsers($conn)
{
    $data['title'] = "รายชื่อผู้ใช้งานทั้งหมด";
    $data['view_mode'] = 'admin_user_table';

    // ---------------------------------------------------------
    // 1. รับค่า Pagination (ใช้ Function Helper แทนของเดิม)
    // ---------------------------------------------------------
    // --- ❌ ลบของเดิมออก ---
    // $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    // $page  = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
    // if ($page < 1) $page = 1;
    // $offset = ($page - 1) * $limit;

    // --- ✅ ใส่ของใหม่เข้าไป (บรรทัดเดียวจบ) ---
    $pg = getPaginationParams(10); // ค่า Default 10
    $limit  = $pg['limit'];
    $page   = $pg['page'];
    $offset = $pg['offset'];


    // ---------------------------------------------------------
    // 2. รับค่าจากตัวกรอง (Filter Inputs)
    // ---------------------------------------------------------
    $search_text = isset($_GET['search_text']) ? mysqli_real_escape_string($conn, $_GET['search_text']) : '';
    $dept_user   = isset($_GET['dept_user']) ? intval($_GET['dept_user']) : 0;
    $role_user   = isset($_GET['role_user']) ? mysqli_real_escape_string($conn, $_GET['role_user']) : '';


    // ---------------------------------------------------------
    // 3. Query นับจำนวน (Count Logic)
    // ---------------------------------------------------------
    $count_sql = "SELECT COUNT(*) as total FROM users u 
                  LEFT JOIN user_profiles p ON u.id = p.user_id 
                  LEFT JOIN departments d ON p.department_id = d.id
                  WHERE p.deleted_at IS NULL ";
    
    $count_sql = applyPermissionFilter($count_sql);
    // Filter Logic (เหมือนเดิม)
    if (!empty($search_text)) {
        $count_sql .= " AND (p.first_name LIKE '%$search_text%' OR p.last_name LIKE '%$search_text%' OR u.username LIKE '%$search_text%') ";
    }
    if ($dept_user > 0) $count_sql .= " AND d.id = $dept_user ";
    if (!empty($role_user)) $count_sql .= " AND u.role_id = '$role_user' ";

    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, $count_sql))['total'];
    
    // ✅ คำนวณหน้าทั้งหมด (รองรับกรณี limit = 0 หรือ ดูทั้งหมด)
    if ($limit > 0) {
        $total_pages = ceil($total_rows / $limit);
    } else {
        $total_pages = 1;
    }


    // ---------------------------------------------------------
    // 4. Query หลัก (Main Query)
    // ---------------------------------------------------------
    $sql = "SELECT u.*, p.*, d.thai_name AS department, r.description AS role_user
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_profiles p ON u.id = p.user_id
            LEFT JOIN departments d ON p.department_id = d.id
            WHERE p.deleted_at IS NULL ";

    // Filter Logic (เหมือนเดิม)
    $sql = applyPermissionFilter($sql);

    if (!empty($search_text)) {
        $sql .= " AND (p.first_name LIKE '%$search_text%' OR p.last_name LIKE '%$search_text%' OR u.username LIKE '%$search_text%') ";
    }
    if ($dept_user > 0) {
        $sql .= " AND d.id = $dept_user ";
    }
    if (!empty($role_user)) {
        $sql .= " AND u.role_id = '$role_user' ";
    }

    $sql .= " ORDER BY d.id ASC, p.first_name ASC";

    // ✅ ใส่ Limit/Offset (รองรับกรณี limit = 0 หรือ ดูทั้งหมด)
    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }

    // ---------------------------------------------------------
    // 5. ประมวลผลและส่งค่ากลับ
    // ---------------------------------------------------------
    $data['user_list'] = [];
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['remaining_balance'] = getRemainingBalance($conn, $row['id']);
        $data['user_list'][] = $row;
    }

    $data['pagination'] = [
        'current_page' => $page,
        'total_pages'  => $total_pages,
        'total_rows'   => $total_rows,
        'limit'        => $limit
    ];
    
    $data['filters'] = [
        'search_text' => $search_text,
        'dept_user'   => $dept_user,
        'role_user'   => $role_user,
        'limit'       => $limit
    ];
    
    return $data;
}