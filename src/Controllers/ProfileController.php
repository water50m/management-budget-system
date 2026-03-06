<?php
class ProfileController
{
    // ถ้ามีเวลาควรแยกฟังชั่นนะ
    public function index()
    {
        global $conn;
        require_once __DIR__ . '/../../includes/userRoleManageFunction.php';
        include_once __DIR__ . "/../Helper/function.php";

        $user_id = isset($_GET['id']) ? intval($_GET['id']) : '';
        
        // 1. ดึงข้อมูลส่วนตัว (เหมือนเดิม)
        $sql_user = "SELECT u.*, p.*, d.thai_name AS department_name,d.id AS department_id, d.name AS department_eng,
                            b.*
                     FROM users u
                     LEFT JOIN user_profiles p ON u.upid = p.user_id
                     LEFT JOIN departments d ON p.department_id = d.id
                     LEFT JOIN v_user_budget_summary b ON p.user_id = b.user_id
                     WHERE u.upid = $user_id";
        $user_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_user));
        $query_result = mysqli_query($conn, $sql_user);

        // 1. เช็คก่อนว่า Query พังหรือไม่? (สำคัญมาก)
        if (!$query_result) {
            die("<h1>SQL Error!</h1><br>" . mysqli_error($conn)); 
        }

        $user_info = mysqli_fetch_assoc($query_result);

        // 2. ถ้า Query ผ่าน แต่หาข้อมูลไม่เจอ
        if (!$user_info) {
            // header("Location: index.php?page=dashboard"); // <--- ปิด Redirect ไว้ก่อน
            // exit;
            
            die("<h1>User Not Found</h1><br>ID ที่ค้นหา: " . $user_id);
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
        $f_prev_year = isset($_GET['prevYear']) && $_GET['prevYear'] != 0 ? intval($_GET['prevYear']) : 0;
        $f_total_balance_show = isset($_GET['total_balance'])  && $_GET['prevYear'] > 0 ? intval($conn, $_GET['total_balance']) : 0;
        $f_carried_over_remaining = isset($_GET['carried_over_remaining'])  && $_GET['carried_over_remaining'] != '' ? true : false;

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
            $f_search_safe = addcslashes($f_search, "%_");
            $where_inc .= " AND (br.remark LIKE '%$f_search_safe%') ";
            $where_exp .= " AND (e.description LIKE '%$f_search_safe%') ";
        }
        if ($f_year > 0) {
            // กรณีมีปีก่อนหน้า (ดึงข้อมูลช่วง 2 ปี: ปีปัจจุบัน + ปีก่อนหน้า)
            if (isset($f_prev_year) && $f_prev_year > 0) {
                $where_inc .= " AND br.fiscal_year IN ('$f_prev_year', '$f_year') ";
                $where_exp .= " AND e.fiscal_year IN ('$f_prev_year','$f_year') ";
            }
            // กรณีไม่มีปีก่อนหน้า (ดึงแค่ปีปัจจุบันปีเดียว)
            else {
                $where_inc .= " AND br.fiscal_year = '$f_year' ";
                $where_exp .= " AND e.fiscal_year = '$f_year' ";
            }
        }
        if ($f_cat > 0) {
            $where_inc .= " AND 1=0 ";
            $where_exp .= " AND e.category_id = '$f_cat' ";
        }
        if ($f_min !== '' && $f_min > 0) {
            $where_inc .= " AND br.amount >= '$f_min' ";
            $where_exp .= " AND e.amount >= '$f_min' ";
        }
        if ($f_max !== '' && $f_max > 0) {
            $where_inc .= " AND br.amount <= '$f_max' ";
            $where_exp .= " AND e.amount <= '$f_max' ";
        }
        if ($f_total_balance_show > 0) {
            $where_inc = "  WHERE br.user_id = $user_id AND deleted_at IS NULL AND br.fiscal_year IN ('$f_total_balance_show', '$f_total_balance_show' - 1) ";
            $where_exp = " WHERE e.user_id = $user_id AND e.deleted_at IS NULL AND e.fiscal_year = '$f_total_balance_show' ";
        }
        if ($f_carried_over_remaining) {
            $where_inc = " WHERE br.user_id = $user_id 
                            AND br.deleted_at IS NULL
                            AND br.approved_date < DATE(CONCAT(YEAR(CURDATE()) - (MONTH(CURDATE()) < 10), '-10-01'))
                            AND br.expire_date >= DATE(CONCAT(YEAR(CURDATE()) - (MONTH(CURDATE()) < 10), '-10-01'))
                            ORDER BY br.approved_date DESC";
            $where_exp = "WHERE 1=0";
        }


        // Combine Query based on Type
        $sql_parts = [];


        $carry_over_data = getBudgetCarryOverSummary($conn, $user_id, $current_fiscal_year);
        // ส่วนรายรับ (Income)
        if ($f_type == 'all' || $f_type == 'income') {
            $sql_parts[] = "(SELECT 
                                br.id, 
                                br.approved_date as txn_date, 
                                br.remark as description, 
                                br.amount as amount,
                                br.expire_date,
                                'income' as type, 
                                NULL as category_name, 
                                NULL as category_id,
                                NULL as receipt_image_path,
                                NULL as receipt_original_path,
                                 -- 2. คำนวณยอดที่ ใช้ไปแล้วในปีก่อน (Past Usage)
                                -- เพื่อเอามาโชว์ user ว่า อ๋อ หายไปเพราะปีที่แล้วใช้นะ
                                COALESCE((
                                    SELECT SUM(amount_used) 
                                    FROM budget_usage_logs 
                                    WHERE approval_id = br.id 
                                    AND deleted_at IS NULL
                                    AND created_at < DATE(CONCAT(YEAR(CURDATE()) - (MONTH(CURDATE()) < 10), '-10-01'))
                                ), 0) AS used_last_year,

                                -- 3. คำนวณ ยกยอดมาสุทธิ (Net Carried Over) ⭐ ตัวนี้แหละที่ User อยากรู้
                                -- สูตร: (เงินรับ - ใช้ไปปีก่อน) = เงินที่ข้ามเวลามาถึงปีนี้
                                GREATEST(
                                    br.amount - COALESCE((
                                        SELECT SUM(amount_used) 
                                        FROM budget_usage_logs 
                                        WHERE approval_id = br.id 
                                        AND deleted_at IS NULL
                                        AND created_at < DATE(CONCAT(YEAR(CURDATE()) - (MONTH(CURDATE()) < 10), '-10-01'))
                                    ), 0),
                                    0
                                ) AS net_carried_over,

                                -- 4. ยอดคงเหลือปัจจุบัน (Remaining)
                                -- อันนี้หักลบทุกอย่างแล้ว (ทั้งอดีตและปัจจุบัน)
                                GREATEST(
                                    br.amount - COALESCE(
                                        (SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = br.id AND deleted_at IS NULL),
                                        0  
                                    ),
                                    0
                                ) AS current_remaining,
                                br.fiscal_year as fiscal_year_num
                            FROM budget_received br 
                            $where_inc)";
        }

        // ส่วนรายจ่าย (Expense)
        if ($f_type == 'all' || $f_type == 'expense') {
            $sql_parts[] = "(SELECT 
                                e.id, e.approved_date as txn_date, e.description, e.amount as amount,
                                'expense' as type, c.name_th as category_name, c.id AS category_id,
                                NULL AS used_last_year, NULL AS net_carried_over, NULL AS expire_date,
                                fiscal_year as fiscal_year_num, NULL AS current_remaining, e.receipt_image_path, e.receipt_original_path
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
                $row['expire_date_th'] = dateToThai($row['expire_date']);
                $transactions[] = $row;
            }
        }

        $department_list = getAllDepartment($conn);
        $data['department_list'] =  $department_list;




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
            'carry_over_data' => $carry_over_data,
            'years_list'   => $years_list,
            'cats_list'    => $cats_list,
            'filters'      => $filters,      // ส่ง filters ไปด้วย
            'sum_income'   => $sum_income,
            'sum_expense'  => $sum_expense,
            'current_fiscal_year' => $current_fiscal_year,
            'department_list' => $department_list,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
            submitDeleteUser($conn);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_department') {
            $this->editDepartment($conn);
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

            // -----------------------------------------------------------
            // 🛑 เริ่มส่วน DEBUG (ลบออกเมื่อใช้จริง)
            // -----------------------------------------------------------


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
                $_SESSION['tragetfilters'] = $profile_id;
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

    private function editDepartment($conn)
    {

        // 1. รับค่าจาก Form (ที่ส่งมาแบบ POST)
        $id = $_POST['user_id'] ?? 0;               // ID ของ User ที่เรากำลังแก้ไข
        $new_dept_id = $_POST['new_department_id'] ?? 0; // ID ภาควิชาใหม่ที่เลือกมา
        $submt_page = $_POST['submit_page'] ?? "";
        $submt_tab = $_POST['submit_tab'] ?? "";
        // 2. ตรวจสอบข้อมูลเบื้องต้น
        if (empty($id) || empty($new_dept_id)) {
            header("Location: index.php?page=$submt_page&tab=$submt_tab&id=$id&status=error&msg=missing_data");
            exit;
        }

        // 3. เตรียมคำสั่ง SQL (Update)
        // หมายเหตุ: ตรง WHERE id = ? คือการอ้างอิง Primary Key ของตาราง user_profiles
        $sql = "UPDATE user_profiles SET department_id = ? WHERE user_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // 4. ผูกตัวแปร (Bind Params) -> "ii" หมายถึง Integer ทั้งคู่
            $stmt->bind_param("ii", $new_dept_id, $id);

            // 5. รันคำสั่ง (Execute)
            if ($stmt->execute()) {

                // (Optional) บันทึก Log การกระทำ ถ้ามีฟังก์ชัน logActivity
                if (function_exists('logActivity')) {
                    $actor_id = $_SESSION['user_id'] ?? 0;
                    logActivity($conn, $actor_id, $id, 'change_department', "เปลี่ยนภาควิชาเป็น ID: $new_dept_id", $id);
                }

                // ส่งกลับหน้าเดิมพร้อมแจ้งเตือนสำเร็จ
                header("Location: index.php?page=$submt_page&tab=$submt_tab&id=$id&status=success&msg=dept_updated");
            } else {
                // แจ้งเตือนถ้า Update ไม่สำเร็จ
                header("Location: index.php?page=$submt_page&tab=$submt_tab&id=$id&status=error&msg=update_failed");
            }

            $stmt->close();
        } else {
            // แจ้งเตือนถ้า SQL ผิดพลาด
            header("Location: index.php?page=$submt_page&id=$id&status=error&msg=sql_error");
        }

        exit;
    }
}


function getBudgetCarryOverSummary($conn, $user_id, $fiscal_year)
{
    // 1. คำนวณช่วงวันที่ของปีงบประมาณปัจจุบัน
    // แปลงปี พ.ศ. เป็น ค.ศ. (เช่น 2569 -> 2026)
    $fy_ce = (int)$fiscal_year - 543;

    // วันเริ่มปีงบ (1 ต.ค. ปีก่อนหน้า)
    $start_fy_date = ($fy_ce - 1) . "-10-01";
    // วันสิ้นสุดปีงบ (30 ก.ย. ปีปัจจุบัน)
    $end_fy_date   = $fy_ce . "-09-30";
    // วันนี้ (เพื่อเช็คหมดอายุ)
    $today = date('Y-m-d');

    // 2. SQL Query
    $sql = "
        SELECT 
            -- 1. ยอดสุทธิที่ยกมา (Net Carried Over)
            SUM(
                GREATEST(
                    br.amount - COALESCE((
                        SELECT SUM(amount_used) 
                        FROM budget_usage_logs 
                        WHERE approval_id = br.id 
                        AND deleted_at IS NULL
                        AND created_at < ? -- (s) ตัดยอดก่อนเริ่มปีงบนี้
                    ), 0),
                    0
                )
            ) AS total_net_carried_over,

            -- 2. ยอดที่ใช้ไป 'ในปีนี้' (Used This Year)
            COALESCE((
                SELECT SUM(bul.amount_used)
                FROM budget_usage_logs bul
                JOIN budget_received br2 ON bul.approval_id = br2.id
                WHERE br2.user_id = ?   -- (i)
                AND br2.fiscal_year < ? -- (i) เฉพาะรายการปีก่อน
                AND bul.deleted_at IS NULL
                AND bul.created_at BETWEEN ? AND ? -- (s, s) ช่วงปีงบปัจจุบัน
            ), 0) AS total_used_this_year,

            -- 3. ยอดที่หมดอายุ/คืนคลัง (Lapsed)
            SUM(
                CASE 
                    WHEN br.expire_date < ? THEN -- (s) ถ้าหมดอายุแล้วเทียบกับวันนี้
                        GREATEST(
                            br.amount - COALESCE((
                                SELECT SUM(amount_used) 
                                FROM budget_usage_logs 
                                WHERE approval_id = br.id 
                                AND deleted_at IS NULL
                            ), 0),
                            0
                        )
                    ELSE 0 
                END
            ) AS total_lapsed

        FROM budget_received br
        WHERE br.user_id = ?    -- (i)
        AND br.fiscal_year < ?  -- (i) เฉพาะรายการจากปีก่อนๆ
    ";

    // 3. Prepare & Execute
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // กรณี SQL Error ให้ Return 0 หมดเพื่อกันเว็บพัง
        return [
            'carried_over_remaining' => 0,
            'carried_over_used' => 0,
            'carried_over_lapsed' => 0
        ];
    }


    $stmt->bind_param(
        "siisssii",
        $start_fy_date,
        $user_id,
        $fiscal_year,
        $start_fy_date,
        $end_fy_date,
        $today,
        $user_id,
        $fiscal_year
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // 4. Return ผลลัพธ์ (ใส่ 0 หากเป็น null)
    return [
        'carried_over_remaining' => $row['total_net_carried_over'] ?? 0,
        'carried_over_used'      => $row['total_used_this_year'] ?? 0,
        'carried_over_lapsed'    => $row['total_lapsed'] ?? 0
    ];
}
