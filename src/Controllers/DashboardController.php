<?php
// src/Controllers/DashboardController.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/userRoleManageFunction.php';
require_once __DIR__ . '/../../includes/expenseTableFunction.php';
require_once __DIR__ . '/../../includes/approveTableFunction.php';
require_once __DIR__ . '/../../includes/saveLogFunction.php';

class DashboardController {
    

    private function getRemainingBalance($conn, $user_id) {
        $today = date('Y-m-d');
        
        // 1. หา "เงินเข้า"
        $sql_income = "SELECT COALESCE(SUM(approved_amount), 0) as total_approved 
                    FROM budget_approvals 
                    WHERE user_id = $user_id 
                    AND approved_date >= DATE_SUB('$today', INTERVAL 2 YEAR)";
                    
        $res_in = mysqli_query($conn, $sql_income);
        $row_in = mysqli_fetch_assoc($res_in);
        $total_approved = floatval($row_in['total_approved']);

        // 2. หา "เงินออก"
        $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total_spent 
                        FROM budget_expenses 
                        WHERE user_id = $user_id";
                        
        $res_ex = mysqli_query($conn, $sql_expense);
        $row_ex = mysqli_fetch_assoc($res_ex);
        $total_spent = floatval($row_ex['total_spent']);


        return $total_approved - $total_spent;
    }

    public function index() {
        global $conn;

        // 1. ตรวจสอบสิทธิ์
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $data = [];

        // ==================================================================================
        // 🟢 ส่วนที่ 1: จัดการ POST REQUEST (บันทึกข้อมูล) ** ทำก่อนแสดงผลเสมอ **
        // ==================================================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $page = '';
            // 1.1 Action: แก้ไข Role (เฉพาะ High-Admin)
            if (isset($_POST['action']) && $_POST['action'] == 'update_role' && $role == 'high-admin') {
                submitUpdateRole($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'add_budget') {
                $page = 'users';
                // 1. รับค่าจากฟอร์มและป้องกัน SQL Injection
                // สังเกต: รับค่า user_id ครั้งเดียวและใช้ตัวแปรชื่อ $user_id ตลอดการทำงาน
                $user_id = mysqli_real_escape_string($conn, $_POST['user_id']); 
                $amount = floatval($_POST['amount']);
                $approved_date = mysqli_real_escape_string($conn, $_POST['approved_date']);
                $remark = mysqli_real_escape_string($conn, $_POST['remark']);

                // 2. คำนวณปีงบประมาณ (Fiscal Year)
                $timestamp = strtotime($approved_date);
                $month = date('n', $timestamp); 
                $year_th = date('Y', $timestamp) + 543;

                // 3. เริ่ม Transaction (เพื่อความปลอดภัยข้อมูล)
                mysqli_begin_transaction($conn);

                try {
                    // A. บันทึกข้อมูลงบประมาณ
                    $sql_budget = "INSERT INTO budget_approvals 
                                (user_id, approved_amount, approved_date, remark) 
                                VALUES 
                                ('$user_id', '$amount', '$approved_date', '$remark')";
                    
                    if (!mysqli_query($conn, $sql_budget)) {
                        throw new Exception("บันทึกงบไม่สำเร็จ: " . mysqli_error($conn));
                    }
                    
                    // B. บันทึก Log (เรียกใช้ฟังก์ชันเดิมของคุณ)
                    $actor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; 
                    $log_desc = "เพิ่มงบประมาณปี .$year_th. จำนวน " . number_format($amount, 2) . " บาท (หมายเหตุ: $remark)";
                    
                    // เรียกใช้ฟังก์ชัน logActivity ($user_id คือ target_id)
                    logActivity($conn, $actor_id, $user_id, 'add_budget', $log_desc);

                    // ยืนยันข้อมูลทั้งหมด (Commit)
                    mysqli_commit($conn);
                    
                    // กลับไปหน้า Dashboard พร้อมสถานะสำเร็จ
                    header("Location: index.php?page=dashboard&status=success");
                    exit; // ต้องมี exit เพื่อหยุดการทำงานทันที

                } catch (Exception $e) {
                    // หากเกิดข้อผิดพลาด ให้ยกเลิกการบันทึกทั้งหมด (Rollback)
                    mysqli_rollback($conn);
                    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
                    // ใน Production อาจเปลี่ยน echo เป็นการบันทึก error log ลงไฟล์แทน
                }
            }

            if (isset($_POST['action']) && $_POST['action'] == 'delete_budget'){
                submitDeleteAprove($conn);
            }

            // 1.2 Action: เพิ่มรายการใช้จ่าย (Add Expense)
            if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {
                $page = 'users';
                $user_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);
                $amount_needed = floatval($_POST['amount']); 
                $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
                $category_id = intval($_POST['category_id']); 
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                                
                mysqli_begin_transaction($conn);

                try {
                    // ---------------------------------------------------------
                    // A. บันทึกรายจ่ายลงตารางหลัก (budget_expenses)
                    // ---------------------------------------------------------
                    $approved_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
                    
                    // กำหนด Type เป็น 'FIFO' หรือ 'System' เพื่อให้รู้ว่าระบบตัดเอง
                    // (ถ้า Database คุณบังคับ ENUM 'current_year','carry_over' อาจต้องไปแก้ DB หรือใส่ค่าใดค่าหนึ่งไปก่อน)
                    $budget_source = 'FIFO'; 

                    $sql_ins = "INSERT INTO budget_expenses 
                                (user_id, category_id, description, amount, approved_date, budget_source_type) 
                                VALUES 
                                ('$user_id', '$category_id', '$description', '$amount_needed', '$approved_date', '$budget_source')";
                    
                    if (!mysqli_query($conn, $sql_ins)) {
                        throw new Exception("Error Inserting Expense: " . mysqli_error($conn));
                    }
                    
                    $new_expense_id = mysqli_insert_id($conn); 

                    // ---------------------------------------------------------
                    // B. ค้นหาใบอนุมัติ (FIFO Logic แบบรวมถุง)
                    // ---------------------------------------------------------
                    
                    // ✅ Query เดียว ดึงหมดทุกใบที่มีเงินเหลือ เรียงตามวันที่อนุมัติ (เก่าสุดขึ้นก่อน)
                    // ตัดเงื่อนไข Fiscal Year ออก เพื่อให้มันมองเห็นงบทุกก้อน
                    $sql_app = "SELECT a.id, a.approved_amount, a.approved_date, 
                                COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id), 0) as used_so_far
                                FROM budget_approvals a
                                WHERE a.user_id = '$user_id'
                                AND a.approved_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) -- (Optional) กรองใบที่เก่าเกิน 2 ปีทิ้ง ถ้าไม่ใช้ก็ลบบรรทัดนี้ได้
                                HAVING (a.approved_amount - used_so_far) > 0
                                ORDER BY a.approved_date ASC"; // หัวใจสำคัญของ FIFO คือตรงนี้ (เก่าไปใหม่)

                    $res_app = mysqli_query($conn, $sql_app);
                    $money_to_cut = $amount_needed;

                    // ---------------------------------------------------------
                    // C. วนลูปตัดเงินทีละใบ
                    // ---------------------------------------------------------
                    while ($row = mysqli_fetch_assoc($res_app)) {
                        if ($money_to_cut <= 0) break;

                        $available_on_this_slip = $row['approved_amount'] - $row['used_so_far'];
                        $cut_amount = 0;

                        if ($money_to_cut >= $available_on_this_slip) {
                            $cut_amount = $available_on_this_slip; // ตัดหมดใบนี้
                        } else {
                            $cut_amount = $money_to_cut; // ตัดบางส่วน
                        }

                        // บันทึก Log การใช้เงิน
                        $sql_log = "INSERT INTO budget_usage_logs (expense_id, approval_id, amount_used)
                                    VALUES ('$new_expense_id', '{$row['id']}', '$cut_amount')";
                        
                        if (!mysqli_query($conn, $sql_log)) {
                            throw new Exception("Error Logging Usage: " . mysqli_error($conn));
                        }

                        $money_to_cut -= $cut_amount;
                    }

                    // ---------------------------------------------------------
                    // D. เช็คความถูกต้องสุดท้าย
                    // ---------------------------------------------------------
                    $actor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; 
                    
                    // เปลี่ยนคำอธิบาย Log นิดหน่อยให้เข้าใจง่าย
                    $log_desc = "บันทึกรายจ่าย (FIFO): $description จำนวน " . number_format($amount_needed, 2) . " บาท";
                    
                    logActivity($conn, $actor_id, $user_id, 'add_expense', $log_desc);

                    mysqli_commit($conn);
                    
                    // Redirect
                    if ($page == '') {
                        header("Location: index.php?page=dashboard&status=success");
                    } else {
                        header("Location: index.php?page=dashboard&status=success&tab=" . $page);
                    }
                    exit;

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
                    exit;
                }
            }
            if (isset($_POST['action']) && $_POST['action'] == 'delete_expense'){
                submitDeleteExpense($conn);
            }
        }

        // ==================================================================================
        // 🟢 ส่วนที่ 2: เตรียมข้อมูลสำหรับ VIEW (GET REQUEST)
        // ==================================================================================

        // 2.1 ดึงหมวดหมู่รายจ่าย (Categories) ส่งไปทำ Dropdown ใน Modal
        $data['categories_list'] = [];
        $res_cat = mysqli_query($conn, "SELECT * FROM expense_categories");
        if ($res_cat) {
            while($c = mysqli_fetch_assoc($res_cat)) $data['categories_list'][] = $c;
        }

        // 2.2 ตั้งค่าตัวแปร Search & Filter พื้นฐาน
        $data['search_keyword'] = '';
        $data['search_dept'] = 0;
        $data['search_year'] = 0;

        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $dept_filter = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
        $year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;

        $data['search_keyword'] = $search;
        $data['search_dept'] = $dept_filter;
        $data['search_year'] = $year_filter;

        // 2.3 ดึงรายชื่อภาควิชา (Dropdown Filter)
        $data['departments_list'] = [];
        $res_dept = mysqli_query($conn, "SELECT * FROM departments ORDER BY id");
        while ($d = mysqli_fetch_assoc($res_dept)) {
            $data['departments_list'][] = $d;
        }

        // 2.4 ดึงปีที่มีข้อมูลจริง (Year Dropdown)
        $data['year_list'] = [];
        // ใช้ Logic ดึงปีงบประมาณจากวันที่ (Fiscal Year Logic)
        $sql_year_list = "SELECT DISTINCT (YEAR(approved_date) + IF(MONTH(approved_date) >= 10, 1, 0)) + 543 as fiscal_year_th
                          FROM budget_approvals 
                          ORDER BY fiscal_year_th DESC";
        $res_year = mysqli_query($conn, $sql_year_list);
        while ($row = mysqli_fetch_assoc($res_year)) {
            $data['year_list'][] = $row['fiscal_year_th'];
        }
        if (empty($data['year_list'])) $data['year_list'][] = date('Y') + 543;


        // ==================================================================================
        // 🟢 ส่วนที่ 3: แยก LOGIC ตาม TABS
        // ==================================================================================
        
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'approval';
        $data['current_tab'] = $tab;
        $target_id = isset($_GET['id']) ? intval($_GET['id']) : null;

        // --- กรณี: ADMIN MODE (ดูภาพรวม) ---
        if ($role == 'admin' || $role == 'high-admin') { // รองรับ high-admin ด้วย
            
            if (!$target_id) { // ถ้าไม่ได้ระบุ ID (ดูตารางรวม)

                if ($tab == 'approval') {
                    $data['title'] = "สรุปยอดงบประมาณที่อนุมัติ";
                    $data['view_mode'] = 'admin_approval_table'; 

                    // ---------------------------------------------------------
                    // 1. รับค่าจากตัวกรอง (Filter Inputs)
                    // ---------------------------------------------------------
                    $search     = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    $dept_id    = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
                    $date_type  = isset($_GET['date_type']) ? $_GET['date_type'] : 'approved'; 
                    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
                    $min_amount = isset($_GET['min_amount']) ? floatval(str_replace(',', '', $_GET['min_amount'])) : 0;
                    $max_amount = isset($_GET['max_amount']) ? floatval(str_replace(',', '', $_GET['max_amount'])) : 0;
                    $year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;

                    // ---------------------------------------------------------
                    // 2. สร้างรายการ "ปีงบประมาณ" (Dynamic Year List)
                    // ---------------------------------------------------------
                    // ดึงวันที่ต่ำสุดและสูงสุดจากระบบ
                    $sql_years = "SELECT MIN(approved_date) as min_date, MAX(approved_date) as max_date FROM budget_approvals";
                    $res_years = mysqli_query($conn, $sql_years);
                    $row_years = mysqli_fetch_assoc($res_years);

                    $years_list = [];

                    if ($row_years['min_date'] && $row_years['max_date']) {
                        // ฟังก์ชันคำนวณปีงบประมาณไทย (เดือน >= 10 คือปีหน้า, +543 เป็น พ.ศ.)
                        $calcFiscal = function($date) {
                            $time = strtotime($date);
                            $y = date('Y', $time);
                            $m = date('n', $time);
                            return ($m >= 10) ? ($y + 1 + 543) : ($y + 543);
                        };

                        $min_fy = $calcFiscal($row_years['min_date']); // ปีงบที่มีในระบบ (น้อยสุด)
                        $max_fy = $calcFiscal($row_years['max_date']); // ปีงบที่มีในระบบ (มากสุด)

                        // สร้าง Loop ตั้งแต่ (Min - 1) ถึง (Max + 1)
                        for ($y = $max_fy + 1; $y >= $min_fy - 1; $y--) {
                            $years_list[] = $y;
                        }
                    } else {
                        // ถ้าไม่มีข้อมูลเลย ให้ใช้ปีปัจจุบัน +1/-1
                        $cur_fy = (date('n') >= 10) ? (date('Y') + 1 + 543) : (date('Y') + 543);
                        $years_list = [$cur_fy + 1, $cur_fy, $cur_fy - 1];
                    }

                    $data['years_list'] = $years_list;

                    // ---------------------------------------------------------
                    // 3. สร้าง SQL (ปรับ Alias ให้ตรงกับ Component)
                    // ---------------------------------------------------------
                    $sql = "SELECT a.id, 
                                d.thai_name AS department, 
                                p.prefix, p.first_name, p.last_name, 
                                a.approved_amount AS amount,      
                                a.remark,                        
                                a.approved_date,                 
                                a.record_date,

                                -- ✅ เพิ่มบรรทัดนี้กลับเข้ามาครับ เพื่อเช็คยอดใช้
                                COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id), 0) as total_used

                            FROM budget_approvals a
                            JOIN users u ON a.user_id = u.id 
                            JOIN user_profiles p ON u.id = p.user_id 
                            LEFT JOIN departments d ON p.department_id = d.id 
                            WHERE 1=1 "; 

                    // ---------------------------------------------------------
                    // 4. ใส่ Logic Filter
                    // ---------------------------------------------------------
                    
                    if (!empty($search)) {
                        $sql .= " AND (p.first_name LIKE '%$search%' OR p.last_name LIKE '%$search%' OR a.remark LIKE '%$search%') ";
                    }

                    if ($year_filter > 0) {
                        // สูตรคำนวณ: ปี ค.ศ. + (ถ้าเดือน>=10 ให้บวก 1) + 543 = ปีงบไทย
                        $sql .= " AND (YEAR(a.approved_date) + IF(MONTH(a.approved_date) >= 10, 1, 0) + 543) = $year_filter ";
                    }

                    if ($dept_id > 0) {
                        $sql .= " AND d.id = $dept_id ";
                    }

                    if (!empty($start_date) && !empty($end_date)) {
                        if ($date_type == 'created') {
                            $sql .= " AND DATE(a.record_date) BETWEEN '$start_date' AND '$end_date' "; 
                        } else {
                            $sql .= " AND DATE(a.approved_date) BETWEEN '$start_date' AND '$end_date' "; 
                        }
                    }

                    if ($min_amount > 0) {
                        $sql .= " AND a.approved_amount >= $min_amount ";
                    }
                    if ($max_amount > 0) {
                        $sql .= " AND a.approved_amount <= $max_amount ";
                    }

                    // ---------------------------------------------------------
                    // 5. ประมวลผลและส่งค่า
                    // ---------------------------------------------------------
                    $sql .= " ORDER BY a.approved_date DESC";

                    $data['approvals'] = [];
                    $result = mysqli_query($conn, $sql);
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['thai_date'] = $this->dateToThai($row['approved_date']);
                        $data['approvals'][] = $row;
                    }
                     // ส่งรายการปีที่สร้าง

                    $data['filters'] = [
                        'search'     => $search,
                        'dept_id'    => $dept_id,
                        'date_type'  => $date_type,
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                        'min_amount' => $_GET['min_amount'] ?? '', 
                        'max_amount' => $_GET['max_amount'] ?? '',
                        'year' => $year_filter
                    ];

                } elseif ($tab == 'expense') {
                    $data['title'] = "ประวัติการเบิกจ่ายงบประมาณ";
                    $data['view_mode'] = 'admin_expense_table';

                    // 1.1 ดึงข้อมูลหมวดหมู่มาทำ Dropdown ตัวกรอง
                    $cat_sql = "SELECT * FROM expense_categories ORDER BY name_th ASC";
                    $cat_res = mysqli_query($conn, $cat_sql);
                    $data['categories_list'] = [];
                    while ($c = mysqli_fetch_assoc($cat_res)) {
                        $data['categories_list'][] = $c;
                    }
                    
                    // 1.2 ดึงข้อมูลภาควิชา (เพิ่มส่วนนี้)
                    $dept_sql = "SELECT * FROM departments ORDER BY thai_name ASC";
                    $dept_res = mysqli_query($conn, $dept_sql);
                    $data['departments_list'] = [];
                    while ($d = mysqli_fetch_assoc($dept_res)) {
                        $data['departments_list'][] = $d;
                    }

                    // 2. รับค่าจากตัวกรอง (Filter Inputs)
                    $search_text = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
                    $cat_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0; //catagory
                    $min_amt    = isset($_GET['min_amount']) && $_GET['min_amount'] != '' ? floatval($_GET['min_amount']) : '';
                    $max_amt    = isset($_GET['max_amount']) && $_GET['max_amount'] != '' ? floatval($_GET['max_amount']) : '';
                    $search_text = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    $dept_filter = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0; //department
                    $date_type  = isset($_GET['date_type']) ? $_GET['date_type'] : 'approved';  
                    $year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;


                    // ---------------------------------------------------------
                    // สร้างรายการ "ปีงบประมาณ" (Dynamic Year List)
                    // ---------------------------------------------------------
                    // ดึงวันที่ต่ำสุดและสูงสุดจากระบบ
                    $sql_years = "SELECT MIN(approved_date) as min_date, MAX(approved_date) as max_date FROM budget_expenses";
                    $res_years = mysqli_query($conn, $sql_years);
                    $row_years = mysqli_fetch_assoc($res_years);

                    $years_list = [];

                    if ($row_years['min_date'] && $row_years['max_date']) {
                        // ฟังก์ชันคำนวณปีงบประมาณไทย (เดือน >= 10 คือปีหน้า, +543 เป็น พ.ศ.)
                        $calcFiscal = function($date) {
                            $time = strtotime($date);
                            $y = date('Y', $time);
                            $m = date('n', $time);
                            return ($m >= 10) ? ($y + 1 + 543) : ($y + 543);
                        };

                        $min_fy = $calcFiscal($row_years['min_date']); // ปีงบที่มีในระบบ (น้อยสุด)
                        $max_fy = $calcFiscal($row_years['max_date']); // ปีงบที่มีในระบบ (มากสุด)

                        // สร้าง Loop ตั้งแต่ (Min - 1) ถึง (Max + 1)
                        for ($y = $max_fy + 1; $y >= $min_fy - 1; $y--) {
                            $years_list[] = $y;
                        }
                    } else {
                        // ถ้าไม่มีข้อมูลเลย ให้ใช้ปีปัจจุบัน +1/-1
                        $cur_fy = (date('n') >= 10) ? (date('Y') + 1 + 543) : (date('Y') + 543);
                        $years_list = [$cur_fy + 1, $cur_fy, $cur_fy - 1];
                    }

                    $data['years_list'] = $years_list;


                    // เก็บค่าไว้แสดงกลับใน Form (Sticky Form)
                    $data['filters'] = [
                        'search' => $search_text,
                        'date_type' => $date_type, // ✅ ส่งกลับไป
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'cat_id' => $cat_filter,
                        'min_amount' => $min_amt,
                        'max_amount' => $max_amt,
                        'dept_id' => $dept_filter,
                        'year' => $year_filter
                    ];

                    // 3. เริ่มเขียน Query หลัก
                    $sql = "SELECT e.*, 
                                p.prefix, p.first_name, p.last_name, 
                                c.name_th as category_name,
                                d.thai_name as department
                            FROM budget_expenses e
                            JOIN users u ON e.user_id = u.id
                            JOIN user_profiles p ON u.id = p.user_id
                            LEFT JOIN expense_categories c ON e.category_id = c.id
                            LEFT JOIN departments d ON p.department_id = d.id
                            WHERE 1=1 ";

                    // --- ใส่เงื่อนไขการกรอง ---
                    if ($year_filter > 0) {
                        // สูตรคำนวณ: ปี ค.ศ. + (ถ้าเดือน>=10 ให้บวก 1) + 543 = ปีงบไทย
                        $sql .= " AND (YEAR(a.approved_date) + IF(MONTH(a.approved_date) >= 10, 1, 0) + 543) = $year_filter ";
                    }
                    
                    // กรองชื่อ / นามสกุล / รายละเอียด
                    if (!empty($search_text)) {
                        $sql .= " AND (p.first_name LIKE '%$search_text%' OR p.last_name LIKE '%$search_text%' OR e.description LIKE '%$search_text%') ";
                    }

                    // กรองช่วงวันที่ (Start - End)
                    if (!empty($start_date) && !empty($end_date)) {
                        if ($date_type == 'created') {
                            // ถ้าเลือก "วันที่คีย์ข้อมูล" ให้เทียบกับ created_at (เอาเฉพาะวันที่ ไม่เอาเวลา)
                            $sql .= " AND DATE(e.created_at) BETWEEN '$start_date' AND '$end_date' ";
                        } else {
                            // ค่าปกติ: เทียบกับ approved_date (วันที่เอกสาร)
                            $sql .= " AND e.approved_date BETWEEN '$start_date' AND '$end_date' ";
                        }
                    } 
                    // (เพิ่ม Logic แบบเดียวกันสำหรับกรณีมีแค่ Start หรือ End อย่างเดียวได้ตามต้องการ)
                    elseif (!empty($start_date)) {
                        $col = ($date_type == 'created') ? "DATE(e.created_at)" : "e.approved_date";
                        $sql .= " AND $col >= '$start_date' ";
                    }
                    elseif (!empty($end_date)) {
                        $col = ($date_type == 'created') ? "DATE(e.created_at)" : "e.approved_date";
                        $sql .= " AND $col <= '$end_date' ";
                    }

                    // กรองหมวดหมู่
                    if ($cat_filter > 0) {
                        $sql .= " AND e.category_id = $cat_filter ";
                    }

                    // กรองภาควิชา
                    if ($dept_filter > 0) {
                        $sql .= " AND d.id = $dept_filter ";
                    }

                    // กรองช่วงจำนวนเงิน (Min - Max)
                    if ($min_amt !== '') {
                        $sql .= " AND e.amount >= $min_amt ";
                    }
                    if ($max_amt !== '') {
                        $sql .= " AND e.amount <= $max_amt ";
                    }

                    $sql .= " ORDER BY e.approved_date DESC, e.created_at DESC";

                    // 4. รัน Query และเก็บผลลัพธ์
                    $data['expenses'] = [];
                    $result = mysqli_query($conn, $sql);
                    
                    if (!$result) {
                        die("SQL Error: " . mysqli_error($conn));
                    }

                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['thai_date'] = $this->dateToThai($row['approved_date']);
                        $data['expenses'][] = $row;
                    }

                } elseif ($tab == 'users') { 
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
                    $sql = "SELECT u.*, p.*, d.thai_name AS department 
                            FROM users u
                            LEFT JOIN user_profiles p ON u.id = p.user_id
                            LEFT JOIN departments d ON p.department_id = d.id
                            WHERE 1=1 ";

                    // ---------------------------------------------------------
                    // 4. ใส่ Logic Filter
                    // ---------------------------------------------------------
                    
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
                        $sql .= " AND u.role = '$role_user' ";
                    }

                    // ---------------------------------------------------------
                    // 5. ประมวลผลข้อมูล
                    // ---------------------------------------------------------
                    $sql .= " ORDER BY d.id ASC, p.first_name ASC"; // เรียงตามภาควิชา -> ชื่อ
                    
                    $data['user_list'] = [];
                    $result = mysqli_query($conn, $sql);

                    while ($row = mysqli_fetch_assoc($result)) {
                        // ดึงยอดเงินคงเหลือ (ใช้ Function เดิมของคุณ)
                        $row['remaining_balance'] = $this->getRemainingBalance($conn, $row['id']);  
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

                } elseif ($tab == 'logs' && $role == 'high-admin') {
                    // === [ใหม่] แท็บที่ 4: ประวัติการใช้งาน (System Logs) ===
                    $data['title'] = "ประวัติการทำงานของระบบ (Activity Logs)";
                    $data['view_mode'] = 'admin_activity_logs';

                    // SQL: ดึงข้อมูล Log + ชื่อคนทำ (Actor) + ชื่อคนโดน (Target)
                    $sql = "SELECT 
                                l.id, l.action_type, l.description, l.created_at,
                                
                                -- ข้อมูลคนทำ (Actor)
                                u_actor.username AS actor_username,
                                u_actor.role AS actor_role,
                                CONCAT(pa.prefix, pa.first_name, ' ', pa.last_name) AS actor_name,
                                
                                -- ข้อมูลคนโดน (Target)
                                u_target.username AS target_username,
                                CONCAT(pt.prefix, pt.first_name, ' ', pt.last_name) AS target_name

                            FROM activity_logs l
                            -- JOIN ครั้งที่ 1: หาคนทำ (Actor)
                            LEFT JOIN users u_actor ON l.actor_id = u_actor.id
                            LEFT JOIN user_profiles pa ON l.actor_id = pa.user_id
                            
                            -- JOIN ครั้งที่ 2: หาคนโดน (Target)
                            LEFT JOIN users u_target ON l.target_id = u_target.id
                            LEFT JOIN user_profiles pt ON l.target_id = pt.user_id
                            
                            ORDER BY l.created_at DESC
                            LIMIT 100"; // ดึงล่าสุด 100 รายการ

                    $data['logs'] = [];
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        // แปลงวันที่ให้สวยงาม
                        $row['thai_datetime'] = date('d/m/Y H:i', strtotime($row['created_at']));
                        $data['logs'][] = $row;
                    }
                } else {
                    // ... (Logic เดิม: Request Table) ...
                    $data['title'] = "ภาพรวมคำของบประมาณ (Request)";
                    $data['view_mode'] = 'admin_request_table';
                    
                    $sql = "SELECT u.id, p.prefix, p.first_name, p.last_name, 
                                   d.thai_name AS department
                            FROM users u 
                            JOIN user_profiles p ON u.id = p.user_id 
                            LEFT JOIN departments d ON p.department_id = d.id
                            WHERE u.role = 'user' 
                            ORDER BY d.id, p.first_name";
                    
                    $result = mysqli_query($conn, $sql);
                    $users_list = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['budget'] = $this->calculateBudget($conn, $row['id']);
                        $users_list[] = $row;
                    }
                    $data['users'] = $users_list;
                }

            } else {
                // --- กรณี: Admin ดู Detail ของคนอื่น (มี target_id) ---
                $this->loadUserDetail($conn, $target_id, $data, true);
            }

        } else {
            // --- กรณี: USER ธรรมดา (ดูของตัวเอง) ---
            $this->loadUserDetail($conn, $user_id, $data, false);
        }

        require_once __DIR__ . '/../../views/dashboard/index.php';
    }

    // ฟังก์ชันย่อยสำหรับโหลดข้อมูล Detail (เพื่อลด code ซ้ำซ้อน)
    private function loadUserDetail($conn, $view_id, &$data, $is_admin_viewing) {
        $data['view_mode'] = 'user_detail';
        $data['is_admin_viewing'] = $is_admin_viewing;
        
        $sql_name = "SELECT p.prefix, p.first_name, p.last_name, d.thai_name AS department FROM user_profiles p LEFT JOIN departments d ON p.department_id = d.id WHERE p.user_id = $view_id";
        $res_name = mysqli_query($conn, $sql_name);
        $data['profile'] = mysqli_num_rows($res_name) > 0 ? mysqli_fetch_assoc($res_name) : ['prefix'=>'','first_name'=>'Unknown','department'=>'-'];
        $data['budget'] = $this->calculateBudget($conn, $view_id);
        $data['title'] = $is_admin_viewing ? "รายละเอียด: ".$data['profile']['first_name'] : "Dashboard ของคุณ";
    }

    // ฟังก์ชันแปลงวันที่
    private function dateToThai($date) {
        if (!$date) return '-';
        $timestamp = strtotime($date);
        $y = date('Y', $timestamp) + 543;
        return date('d/m/', $timestamp) . $y;
    }

    // ฟังก์ชันคำนวณงบ (ใช้ตารางใหม่ budget_expenses ที่มี source_type แล้ว)
    private function calculateBudget($conn, $uid) {
        $budget = ['income_prev'=>0, 'income_next'=>0, 'travel'=>0, 'book'=>0, 'computer'=>0, 'medical'=>0, 'total_expense'=>0];

        // 1. รายรับ (Incomes)
        $res_in = mysqli_query($conn, "SELECT * FROM budget_incomes WHERE user_id = $uid");
        while ($r = mysqli_fetch_assoc($res_in)) {
            if ($r['source_name'] == 'งบ_68_ใช้_69') $budget['income_prev'] += $r['amount'];
            if ($r['source_name'] == 'งบ_69_ใช้_70') $budget['income_next'] += $r['amount'];
        }

        // 2. รายจ่าย (Expenses) - ปรับให้รองรับ category เป็นภาษาอังกฤษ
        $res_ex = mysqli_query($conn, "SELECT * FROM budget_expenses WHERE user_id = $uid");
        while ($r = mysqli_fetch_assoc($res_ex)) {
            if (isset($budget[$r['category']])) {
                $budget[$r['category']] += $r['amount'];
            }
        }
        $budget['total_expense'] = $budget['travel'] + $budget['book'] + $budget['computer'] + $budget['medical'];
        return $budget;
    }
}
