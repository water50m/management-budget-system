<?php
class ProfileController
{
// ถ้ามีเวลาควรแยกฟังชั่นนะ
    public function index()
    {
        global $conn;
        require_once __DIR__ . '/../../includes/userRoleManageFunction.php';
        include_once __DIR__ . "/../Helper/function.php";

        $user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

        // 1. ดึงข้อมูลส่วนตัว (เหมือนเดิม)
        $sql_user = "SELECT u.*, p.*, d.thai_name AS department_name,d.id AS department_id, d.name AS department_eng,
                            b.remaining_balance, b.previous_year_budget, b.current_year_budget
                     FROM users u
                     LEFT JOIN user_profiles p ON u.id = p.user_id
                     LEFT JOIN departments d ON p.department_id = d.id
                     LEFT JOIN v_user_budget_summary b ON p.user_id = b.user_id
                     WHERE u.id = $user_id";
        $user_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_user));
        if (!$user_info) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        // 2. คำนวณยอดรวมต่างๆ (เหมือนเดิม)
        $sql_total_rec = "SELECT SUM(amount) as total FROM budget_received WHERE user_id = $user_id AND deleted_at IS NULL";
        $user_info['total_received_all'] = mysqli_fetch_assoc(mysqli_query($conn, $sql_total_rec))['total'] ?? 0;

        $cur_month = date('n');
        $cur_year_ad = date('Y');
        if ($cur_month >= 10) {
            $start_fiscal = $cur_year_ad . '-10-01';
            $end_fiscal = ($cur_year_ad + 1) . '-09-30';
            $current_fiscal_year = $cur_year_ad + 1 + 543;
        } else {
            $start_fiscal = ($cur_year_ad - 1) . '-10-01';
            $end_fiscal = $cur_year_ad . '-09-30';
            $current_fiscal_year = $cur_year_ad + 543;
        }
        $sql_spent_year = "SELECT SUM(amount) as total FROM budget_expenses WHERE user_id = $user_id AND approved_date BETWEEN '$start_fiscal' AND '$end_fiscal' AND deleted_at IS NULL";
        $user_info['total_spent_this_year'] = mysqli_fetch_assoc(mysqli_query($conn, $sql_spent_year))['total'] ?? 0;

        // 3. เตรียมตัวแปร Filter
        $years_list = [];
        $res_y = mysqli_query($conn, "SELECT DISTINCT IF(MONTH(approved_date)>=10, YEAR(approved_date)+1, YEAR(approved_date))+543 as fy FROM budget_received WHERE user_id = $user_id AND deleted_at IS NULL ORDER BY fy DESC");
        while ($y = mysqli_fetch_assoc($res_y)) {
            $years_list[] = $y['fy'];
        }

        $cats_list = [];
        $res_c = mysqli_query($conn, "SELECT * FROM expense_categories");
        while ($c = mysqli_fetch_assoc($res_c)) {
            $cats_list[] = $c;
        }

        // รับค่า Filter
        $f_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $f_year   = isset($_GET['year']) ? intval($_GET['year']) : ($years_list[0] ?? $current_fiscal_year);
        $f_cat    = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
        $f_min    = isset($_GET['min_amount']) && $_GET['min_amount'] != '' ? floatval($_GET['min_amount']) : '';
        $f_max    = isset($_GET['max_amount']) && $_GET['max_amount'] != '' ? floatval($_GET['max_amount']) : '';
        // ---------------------------------------------------------
        // 🔄 Logic จับคู่ข้อมูล (ถ้ามาแค่อย่างเดียว ให้เป็นค่าเดียวกัน)
        // ---------------------------------------------------------

        // ใช้ is_numeric เพราะค่าอาจจะเป็น 0 ได้
        if (is_numeric($f_min) && !is_numeric($f_max)) {
            $f_max = $f_min;
        } elseif (!is_numeric($f_min) && is_numeric($f_max)) {
            $f_min = $f_max;
        }

        // ✅ เพิ่ม Filter Type
        $f_type   = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, income, expense

        // 4. สร้าง SQL
        $where_inc = " WHERE user_id = $user_id AND deleted_at IS NULL";
        $where_exp = " WHERE e.user_id = $user_id AND e.deleted_at IS NULL";

        // Apply Filters
        if (!empty($f_search)) {
            $where_inc .= " AND (remark LIKE '%$f_search%') ";
            $where_exp .= " AND (e.description LIKE '%$f_search%') ";
        }
        if ($f_year > 0) {
            $fy_logic = "(IF(MONTH(approved_date)>=10, YEAR(approved_date)+1, YEAR(approved_date))+543)";
            $where_inc .= " AND $fy_logic = $f_year ";
            $where_exp .= " AND $fy_logic = $f_year ";
        }
        if ($f_cat > 0) {
            $where_inc .= " AND 1=0 ";
            $where_exp .= " AND e.category_id = $f_cat ";
        }
        if ($f_min !== '' && $f_min > 0) {
            $where_inc .= " AND amount >= $f_min ";
            $where_exp .= " AND e.amount >= $f_min ";
        }
        if ($f_max !== '' && $f_max > 0) {
            $where_inc .= " AND amount <= $f_max ";
            $where_exp .= " AND e.amount <= $f_max ";
        }
        

        // Combine Query based on Type
        $sql_parts = [];

        // ส่วนรายรับ (Income)
        if ($f_type == 'all' || $f_type == 'income') {
            $sql_parts[] = "(SELECT 
                                id, approved_date as txn_date, remark as description, amount as amount,
                                'income' as type, NULL as category_name,
                                IF(MONTH(approved_date) >= 10, YEAR(approved_date) + 1, YEAR(approved_date)) + 543 as fiscal_year_num
                             FROM budget_received $where_inc)";
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

            while ($row = mysqli_fetch_assoc($result)) {
                if ($row['type'] == 'income') {
                    $sum_income += $row['amount'];
                } else {
                    $sum_expense += abs($row['amount']);
                }
                $row['thai_date'] = dateToThai($row['txn_date']);
                $transactions[] = $row;
            }
        }


        $filters = [
            'search' => $f_search,
            'year'   => $f_year,
            'cat'    => $f_cat,
            'min'    => $f_min == 0 ? '' : $f_min,
            'max'    => $f_max == 0 ? '' : $f_max,
            'type'   => $f_type
        ];

        // 2. 🟢 มัดรวมตัวแปรทั้งหมดลงใน $data (จุดที่หายไป)
        $data = [
            'user_info'    => $user_info,
            'transactions' => $transactions,
            'years_list'   => $years_list,
            'cats_list'    => $cats_list,
            'filters'      => $filters,      // ส่ง filters ไปด้วย
            'sum_income'   => $sum_income,
            'sum_expense'  => $sum_expense,
            'current_fiscal_year' => $current_fiscal_year
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
            submitDeleteUser($conn);
        }

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            $hx_target = $_SERVER['HTTP_HX_TARGET'] ?? '';

            if ($hx_target == 'app-container') {
                // 🔵 กรณีที่ 2: กดจาก Navbar มาหน้า Profile
                header("HX-Push-Url: index.php?page=profile&id=$user_id...");
                require __DIR__ . '/../../views/profile/language.php';
                extract($data);
                // ส่งไปทั้งหน้า Profile (แต่ไม่เอา Header/Footer หลัก)
                require_once __DIR__ . '/../../views/profile/index.php';
                exit;
            } elseif ($hx_target == 'txn-table-container') {
                // 🔵 กรณีที่ 3: กด Filter ในหน้า Profile
                // (Logic เดิม)
                require __DIR__ . '/../../views/profile/language.php';
                extract($data);
                include __DIR__ . '/../../views/profile/transactions_table.php';
                exit;
            }
        }

        // 🔵 กรณีที่ 1: Full Page Load
        require_once __DIR__ . '/../../includes/header.php'; // Header เปิด #app-container
        extract($data);
        require_once __DIR__ . '/../../views/profile/index.php';
        require_once __DIR__ . '/../../includes/footer.php';
    }

    public function addProfile($conn)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {

            $return_page = isset($_POST['current_page']) ? $_POST['current_page'] : 'dashboard';
            $return_tab  = isset($_POST['current_tab']) ? $_POST['current_tab'] : 'dashboard';
            // 1. รับค่าจากฟอร์ม
            $prefix = mysqli_real_escape_string($conn, $_POST['prefix']);
            $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
            $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
            $department_id = intval($_POST['department_id']);
            $username = mysqli_real_escape_string($conn, $_POST['username']);

            // กำหนดค่า Role คงที่ = 7
            $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 7;
            $actor_id = $_SESSION['user_id']; // คนทำรายการ

            // 2. ตรวจสอบ Username ซ้ำ
            $check_sql = "SELECT id FROM users WHERE username = '$username'";
            if (mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
                $error_msg = "Username '$username' มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น";
                header("Location: index.php?page=$return_page&tab=$return_tab&status=error&toastMsg=" . urlencode($error_msg));
                exit();
            }

            // เริ่ม Transaction (เพราะต้องบันทึก 2 ตาราง)
            mysqli_begin_transaction($conn);

            try {
                // ---------------------------------------------------------
                // Step 1: Insert ลงตาราง user_profiles ก่อน
                // ---------------------------------------------------------
                $sql_profile = "INSERT INTO user_profiles (prefix, first_name, last_name, department_id) 
                                        VALUES ('$prefix', '$first_name', '$last_name', '$department_id')";

                if (!mysqli_query($conn, $sql_profile)) {
                    throw new Exception("บันทึก Profile ไม่สำเร็จ: " . mysqli_error($conn));
                }

                // ดึง ID ล่าสุดที่เพิ่ง Insert (p.id)
                $profile_id = mysqli_insert_id($conn);

                // ---------------------------------------------------------
                // Step 2: Insert ลงตาราง users (ผูก u.upid = p.id)
                // ---------------------------------------------------------
                // หมายเหตุ: ไม่มีการเก็บ Password ตามโจทย์
                $sql_user = "INSERT INTO users (username, role_id, upid, created_at) 
                     VALUES ('$username', $role_id, $profile_id, NOW())";

                if (!mysqli_query($conn, $sql_user)) {
                    throw new Exception("บันทึก User ไม่สำเร็จ: " . mysqli_error($conn));
                }


                // ✅ Commit ข้อมูลเมื่อผ่านทั้งคู่
                mysqli_commit($conn);

                // ---------------------------------------------------------
                // Step 3: บันทึก Log
                // ---------------------------------------------------------
                $fullname = "$prefix$first_name $last_name";
                logActivity($conn, $actor_id, $profile_id, 'add_user', "เพิ่มผู้ใช้งานใหม่: $fullname (User: $username)");

                $_SESSION['tragettab'] = 'users';
                $_SESSION['tragetfilters'] = 'id=' . $profile_id;
                $_SESSION['show_btn'] = true;

                // Redirect Success
                header("Location: index.php?page=$return_page&tab=$return_tab&status=add&toastMsg=" . urlencode("เพิ่มข้อมูล $fullname เรียบร้อยแล้ว"));
                exit();
            } catch (Exception $e) {
                // ❌ Rollback หากเกิดข้อผิดพลาด
                mysqli_rollback($conn);
                // echo "เกิดข้อผิดพลาด: " . $e->getMessage();
                header("Location: index.php?page=$return_page&tab=$return_tab&status=error&toastMsg=เกิดปัญหากับการทำรายการ");
                exit();
            }
        }
    }
}
