<?php
// src/Controllers/ProfileController.php
require_once __DIR__ . '/../../includes/db.php';

class ProfileController {
    
    public function index() {
    global $conn;
    // รับค่า ID จาก URL (ถ้าไม่มีให้ดูของตัวเอง)
    $user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

    // 1. ดึงข้อมูลส่วนตัว + สรุปยอดเงิน (ใช้ View ที่เราเพิ่งสร้าง v_user_budget_summary)
    $sql_user = "SELECT u.*, p.*, d.thai_name AS department_name, 
                        b.current_year_budget, 
                        b.previous_year_budget, 
                        b.total_spent, 
                        b.remaining_balance
                 FROM users u
                 LEFT JOIN user_profiles p ON u.id = p.user_id
                 LEFT JOIN departments d ON p.department_id = d.id
                 LEFT JOIN v_user_budget_summary b ON u.id = b.user_id
                 WHERE u.id = $user_id";
    
    $res_user = mysqli_query($conn, $sql_user);
    $user_info = mysqli_fetch_assoc($res_user);

    // ถ้าไม่เจอผู้ใช้ ให้เด้งกลับ
    if (!$user_info) { header("Location: index.php?page=dashboard"); exit; }

    // 2. ดึงประวัติการรับเงิน (Budget Approvals)
    // เพิ่มการคำนวณปีงบประมาณ และสถานะหมดอายุ
    $sql_approvals = "SELECT *,
                        IF(MONTH(approved_date) >= 10, YEAR(approved_date) + 1, YEAR(approved_date)) + 543 AS fiscal_year_th,
                        IF(approved_date < DATE_SUB(CURDATE(), INTERVAL 2 YEAR), 'expire', 'active') AS status
                      FROM budget_approvals 
                      WHERE user_id = $user_id 
                      ORDER BY approved_date DESC";
    $approvals = mysqli_query($conn, $sql_approvals);

    // 3. ดึงประวัติการใช้จ่าย (Budget Expenses)
    $sql_expenses = "SELECT e.*, c.name_th as category_name 
                 FROM budget_expenses e
                 LEFT JOIN expense_categories c ON e.category = c.id
                 WHERE e.user_id = $user_id 
                 ORDER BY e.expense_date DESC";
    $expenses = mysqli_query($conn, $sql_expenses) or die("SQL Error: " . mysqli_error($conn));

    // ส่งข้อมูลไปที่ View
    require_once __DIR__ .'/../../views/profile/index.php'; 
}
}
?>