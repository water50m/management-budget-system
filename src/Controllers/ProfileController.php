<?php
// src/Controllers/ProfileController.php
require_once __DIR__ . '/../../includes/db.php';

class ProfileController {
    
    public function index() {
        global $conn;
        $user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

        // 1. ดึงข้อมูลส่วนตัว (เหมือนเดิม)
        $sql_user = "SELECT u.*, p.*, d.thai_name AS department_name, 
                            b.remaining_balance, b.previous_year_budget, b.current_year_budget
                     FROM users u
                     LEFT JOIN user_profiles p ON u.id = p.user_id
                     LEFT JOIN departments d ON p.department_id = d.id
                     LEFT JOIN v_user_budget_summary b ON u.id = b.user_id
                     WHERE u.id = $user_id";
        $user_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_user));
        if (!$user_info) { header("Location: index.php?page=dashboard"); exit; }

        // 2. คำนวณยอดรวมต่างๆ (เหมือนเดิม)
        $sql_total_rec = "SELECT SUM(approved_amount) as total FROM budget_approvals WHERE user_id = $user_id";
        $user_info['total_received_all'] = mysqli_fetch_assoc(mysqli_query($conn, $sql_total_rec))['total'] ?? 0;

        $cur_month = date('n'); $cur_year_ad = date('Y');
        if ($cur_month >= 10) {
            $start_fiscal = $cur_year_ad . '-10-01'; $end_fiscal = ($cur_year_ad + 1) . '-09-30';
            $current_fiscal_year = $cur_year_ad + 1 + 543;
        } else {
            $start_fiscal = ($cur_year_ad - 1) . '-10-01'; $end_fiscal = $cur_year_ad . '-09-30';
            $current_fiscal_year = $cur_year_ad + 543;
        }
        $sql_spent_year = "SELECT SUM(amount) as total FROM budget_expenses WHERE user_id = $user_id AND approved_date BETWEEN '$start_fiscal' AND '$end_fiscal'";
        $user_info['total_spent_this_year'] = mysqli_fetch_assoc(mysqli_query($conn, $sql_spent_year))['total'] ?? 0;

        // 3. เตรียมตัวแปร Filter
        $years_list = [];
        $res_y = mysqli_query($conn, "SELECT DISTINCT IF(MONTH(approved_date)>=10, YEAR(approved_date)+1, YEAR(approved_date))+543 as fy FROM budget_approvals WHERE user_id = $user_id ORDER BY fy DESC");
        while($y = mysqli_fetch_assoc($res_y)) { $years_list[] = $y['fy']; }
        
        $cats_list = [];
        $res_c = mysqli_query($conn, "SELECT * FROM expense_categories");
        while($c = mysqli_fetch_assoc($res_c)) { $cats_list[] = $c; }

        // รับค่า Filter
        $f_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $f_year   = isset($_GET['year']) ? intval($_GET['year']) : ($years_list[0] ?? $current_fiscal_year);
        $f_cat    = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
        $f_min    = isset($_GET['min_amount']) ? floatval($_GET['min_amount']) : '';
        $f_max    = isset($_GET['max_amount']) ? floatval($_GET['max_amount']) : '';
        
        // ✅ เพิ่ม Filter Type
        $f_type   = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, income, expense

        // 4. สร้าง SQL
        $where_inc = " WHERE user_id = $user_id ";
        $where_exp = " WHERE e.user_id = $user_id ";

        // Apply Filters
        if(!empty($f_search)){
            $where_inc .= " AND (remark LIKE '%$f_search%') ";
            $where_exp .= " AND (e.description LIKE '%$f_search%') ";
        }
        if($f_year > 0){
            $fy_logic = "(IF(MONTH(approved_date)>=10, YEAR(approved_date)+1, YEAR(approved_date))+543)";
            $where_inc .= " AND $fy_logic = $f_year ";
            $where_exp .= " AND $fy_logic = $f_year ";
        }
        if($f_cat > 0){
            $where_inc .= " AND 1=0 "; 
            $where_exp .= " AND e.category_id = $f_cat ";
        }
        if($f_min !== '' && $f_min > 0) {
            $where_inc .= " AND approved_amount >= $f_min ";
            $where_exp .= " AND e.amount >= $f_min ";
        }
        if($f_max !== '' && $f_max > 0) {
            $where_inc .= " AND approved_amount <= $f_max ";
            $where_exp .= " AND e.amount <= $f_max ";
        }

        // Combine Query based on Type
        $sql_parts = [];

        // ส่วนรายรับ (Income)
        if ($f_type == 'all' || $f_type == 'income') {
            $sql_parts[] = "(SELECT 
                                id, approved_date as txn_date, remark as description, approved_amount as amount,
                                'income' as type, NULL as category_name,
                                IF(MONTH(approved_date) >= 10, YEAR(approved_date) + 1, YEAR(approved_date)) + 543 as fiscal_year_num
                             FROM budget_approvals $where_inc)";
        }

        // ส่วนรายจ่าย (Expense)
        if ($f_type == 'all' || $f_type == 'expense') {
            $sql_parts[] = "(SELECT 
                                e.id, e.approved_date as txn_date, e.description, -e.amount as amount,
                                'expense' as type, c.name_th as category_name,
                                IF(MONTH(e.approved_date) >= 10, YEAR(e.approved_date) + 1, YEAR(e.approved_date)) + 543 as fiscal_year_num
                             FROM budget_expenses e
                             LEFT JOIN expense_categories c ON e.category_id = c.id
                             $where_exp)";
        }

        $transactions = [];
        $sum_income = 0;
        $sum_expense = 0;

        if (!empty($sql_parts)) {
            $sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY txn_date DESC, id DESC";
            $result = mysqli_query($conn, $sql);
            
            while($row = mysqli_fetch_assoc($result)){
                if($row['type'] == 'income') {
                    $sum_income += $row['amount'];
                } else {
                    $sum_expense += abs($row['amount']);
                }
                $transactions[] = $row;
            }
        }

        $filters = [
            'search' => $f_search, 'year' => $f_year, 'cat' => $f_cat, 
            'min' => $f_min == 0 ? '' : $f_min, 
            'max' => $f_max == 0 ? '' : $f_max,
            'type' => $f_type // ส่งค่า type กลับไป view
        ];
        
        require_once __DIR__ .'/../../views/profile/index.php'; 
    }
}
