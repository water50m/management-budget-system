<?php
// src/Controllers/DashboardController.php
require_once __DIR__ . '/../../includes/db.php';

class DashboardController {
    
    // ฟังก์ชันสำหรับบันทึก Log การกระทำต่างๆ
    private function logActivity($conn, $actor_id, $target_id, $action, $desc) {
        $desc = mysqli_real_escape_string($conn, $desc);
        $sql = "INSERT INTO activity_logs (actor_id, target_id, action_type, description) 
                VALUES ($actor_id, $target_id, '$action', '$desc')";
        mysqli_query($conn, $sql);
    }

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

        // ========================================================
        // 🛑 DEBUG ZONE: เช็คค่าตรงนี้ (แก้ ID ตามคนที่เราอยากดู)
        // ========================================================
        // if ($user_id == 4) { // สมมติอยากดูของ User ID 1 (อ.ปิติ)
        //     echo "<div style='background: #fff; padding: 20px; border: 2px solid red; z-index: 9999; position: relative;'>";
        //     echo "<h3>🕵️ Debugging User ID: $user_id</h3>";
            
        //     echo "<strong>1. SQL Income:</strong> " . $sql_income . "<br>";
        //     echo "<strong>Total Approved (2 Years):</strong> <span style='color:green'>" . number_format($total_approved, 2) . "</span><br><br>";
            
        //     echo "<strong>2. SQL Expense:</strong> " . $sql_expense . "<br>";
        //     echo "<strong>Total Spent:</strong> <span style='color:red'>" . number_format($total_spent, 2) . "</span><br><br>";
            
        //     echo "<strong>3. Final Result:</strong> " . ($total_approved - $total_spent) . "<br>";
            
        //     echo "</div>";
        //     // exit(); // ถ้าอยากให้หยุดทำงานเลยให้เอา comment ออก
        // }
        // ========================================================

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
                $target_uid = intval($_POST['target_user_id']);
                $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);
                
                // อัปเดต Role ลง DB
                $sql_update = "UPDATE users SET role = '$new_role' WHERE id = $target_uid";
                if (mysqli_query($conn, $sql_update)) {
                    // บันทึก Log
                    $this->logActivity($conn, $user_id, $target_uid, 'update_role', "เปลี่ยนสิทธิ์เป็น $new_role");
                    
                    // Redirect กลับมาหน้าเดิม (tab users) เพื่อไม่ให้ Form ค้าง
                    header("Location: index.php?page=dashboard&tab=users&success=role_updated");
                    exit();
                }
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
                    $log_desc = "เพิ่มงบประมาณปี $fiscal_year จำนวน " . number_format($amount, 2) . " บาท (หมายเหตุ: $remark)";
                    
                    // เรียกใช้ฟังก์ชัน logActivity ($user_id คือ target_id)
                    $this->logActivity($conn, $actor_id, $user_id, 'add_budget', $log_desc);

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

            // 1.2 Action: เพิ่มรายการใช้จ่าย (Add Expense)
            if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {
                $page = 'users';
                $user_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);
                $amount_needed = floatval($_POST['amount']); // ยอดที่ต้องการจ่าย
                $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
                $category_id = intval($_POST['category_id']); // ใช้ ID ตามที่เราแก้แล้ว
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $use_prev_budget = isset($_POST['use_prev_budget']) ? 1 : 0; // 1=ใช้งบปีก่อน, 0=งบปีนี้

                // เริ่ม Transaction (สำคัญมาก! เพื่อความปลอดภัยของข้อมูล)
                mysqli_begin_transaction($conn);

                try {
                    // ---------------------------------------------------------
                    // A. บันทึกรายจ่ายลงตารางหลักก่อน (budget_expenses)
                    // ---------------------------------------------------------
                    $approved_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
                    $budget_source = $use_prev_budget ? 'carry_over' : 'current_year';
                    
                    $sql_ins = "INSERT INTO budget_expenses 
                                (user_id, category_id, description, amount, approved_date, budget_source_type) 
                                VALUES 
                                ('$user_id', '$category_id', '$description', '$amount_needed', '$approved_date', '$budget_source')";
                    
                    if (!mysqli_query($conn, $sql_ins)) {
                        throw new Exception("Error Inserting Expense: " . mysqli_error($conn));
                    }
                    
                    $new_expense_id = mysqli_insert_id($conn); // ได้ ID ของบิลรายจ่ายมาแล้ว

                    // ---------------------------------------------------------
                    // B. ค้นหาใบอนุมัติที่มีเงินเหลือ (FIFO Logic)
                    // ---------------------------------------------------------
                    
                    // กำหนดเงื่อนไขปีงบประมาณ (แยกกระเป๋าตาม Checkbox)
                    $fiscal_condition = "";
                    $current_year = (date('m') >= 10) ? date('Y') + 1 : date('Y'); // ปีงบปัจจุบัน
                    
                    if ($use_prev_budget) {
                        // ถ้าติ๊กใช้งบเก่า: หาใบที่อนุมัติก่อนปีงบปัจจุบัน
                        $fiscal_condition = "AND (YEAR(approved_date) + (IF(MONTH(approved_date)>=10,1,0))) < $current_year";
                    } else {
                        // ถ้าไม่ติ๊ก (ใช้งบปีนี้): หาใบที่เป็นปีงบปัจจุบัน
                        $fiscal_condition = "AND (YEAR(approved_date) + (IF(MONTH(approved_date)>=10,1,0))) = $current_year";
                    }

                    // Query ดึงใบอนุมัติ + คำนวณยอดที่ใช้ไปแล้ว (Used)
                    // เรียงตาม approved_date ASC (เก่าสุดขึ้นก่อน -> FIFO)
                    $sql_app = "SELECT a.id, a.approved_amount, a.approved_date,
                                COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id), 0) as used_so_far
                                FROM budget_approvals a
                                WHERE a.user_id = '$user_id'
                                AND a.approved_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) -- ต้องยังไม่หมดอายุ
                                $fiscal_condition
                                HAVING (a.approved_amount - used_so_far) > 0
                                ORDER BY a.approved_date ASC";

                    $res_app = mysqli_query($conn, $sql_app);
                    $money_to_cut = $amount_needed; // ตัวแปรช่วยนับยอดคงเหลือที่ต้องตัด

                    // ---------------------------------------------------------
                    // C. วนลูปตัดเงินทีละใบ
                    // ---------------------------------------------------------
                    while ($row = mysqli_fetch_assoc($res_app)) {
                        if ($money_to_cut <= 0) break; // ถ้าตัดครบแล้ว หยุดลูปทันที

                        $available_on_this_slip = $row['approved_amount'] - $row['used_so_far'];
                        $cut_amount = 0;

                        if ($money_to_cut >= $available_on_this_slip) {
                            // กรณี 1: เงินใบนี้ "ไม่พอ" หรือ "พอดี" -> ตัดเกลี้ยงใบ
                            $cut_amount = $available_on_this_slip;
                        } else {
                            // กรณี 2: เงินใบนี้ "เหลือเยอะกว่า" -> ตัดเท่าที่ต้องใช้
                            $cut_amount = $money_to_cut;
                        }

                        // บันทึกลงตาราง Log (หัวใจสำคัญ!)
                        $sql_log = "INSERT INTO budget_usage_logs (expense_id, approval_id, amount_used)
                                    VALUES ('$new_expense_id', '{$row['id']}', '$cut_amount')";
                        
                        if (!mysqli_query($conn, $sql_log)) {
                            throw new Exception("Error Logging Usage: " . mysqli_error($conn));
                        }

                        $money_to_cut -= $cut_amount; // ลดยอดที่ต้องการจ่ายลง
                    }

                    // ---------------------------------------------------------
                    // D. เช็คความถูกต้องสุดท้าย
                    // ---------------------------------------------------------
                    if ($money_to_cut > 0) {
                        // ถ้าวนลูปจนหมดทุกใบแล้ว เงินยังไม่พอจ่าย (แสดงว่ายอดเงินคงเหลือหน้าเว็บอาจไม่อัปเดต)
                        // คุณเลือกได้ว่าจะ Rollback (ห้ามบันทึก) หรือจะยอมให้บันทึกแบบติดลบ
                        // ในที่นี้ผมแนะนำให้ยอมบันทึกไปก่อน (แต่มันจะไม่มี Log จับคู่ในส่วนที่เกิน) 
                        // หรือจะ throw Exception เพื่อห้ามบันทึกก็ได้ครับ
                    }

                    $actor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; 
                    $budget_text = $use_prev_budget ? "งบปีก่อนหน้า" : "งบปีปัจจุบัน";
                    $log_desc = "บันทึกรายจ่าย ($budget_text): $description จำนวน " . number_format($amount_needed, 2) . " บาท";
                    
                    // เรียกใช้ฟังก์ชันที่มีอยู่แล้วใน Class
                    $this->logActivity($conn, $actor_id, $user_id, 'add_expense', $log_desc);

                    // ยืนยันข้อมูลทั้งหมด (Save)
                    mysqli_commit($conn);
                    
                    // Redirect กลับไป
                    if ($page == '') {
                        // ถ้าไม่มีการระบุหน้า ให้กลับไปที่ Dashboard ปกติ
                        header("Location: index.php?page=dashboard&status=success");
                    } else {
                        // ถ้ามีระบุหน้า (เช่น กลับไปหน้า profile) ให้ส่งค่า tab หรือ page กลับไปด้วย
                        // แก้ไข: ใช้ . เชื่อมสตริง และแก้ succes เป็น success
                        header("Location: index.php?page=dashboard&status=success&tab=" . $page);
                    }
                    exit;
                    

                } catch (Exception $e) {
                    // ถ้ามี Error แม้แต่นิดเดียว -> ยกเลิกทั้งหมด (ข้อมูลไม่พัง)
                    mysqli_rollback($conn);
                    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
                    exit;
                }
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
                    
                    // ✅ แก้ไข SQL ตรงนี้ครับ
                    $sql = "SELECT a.id, -- 1. เพิ่ม ID เพื่อใช้ในการลบ
                                   d.thai_name AS department, p.prefix, p.first_name, p.last_name, 
                                   a.approved_amount, a.remark, a.approved_date,
                                   -- 2. เพิ่มการเช็คยอดที่ใช้ไปแล้ว (เพื่อทำปุ่มจาง)
                                   COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id), 0) as total_used
                            FROM budget_approvals a
                            JOIN users u ON a.user_id = u.id 
                            JOIN user_profiles p ON u.id = p.user_id 
                            LEFT JOIN departments d ON p.department_id = d.id 
                            WHERE 1=1 "; 

                    if (!empty($search)) {
                        $sql .= " AND (p.first_name LIKE '%$search%' OR p.last_name LIKE '%$search%') ";
                    }
                    if ($dept_filter > 0) {
                        $sql .= " AND d.id = $dept_filter ";
                    }
                    if ($year_filter > 0) {
                        $sql .= " AND (YEAR(a.approved_date) + (IF(MONTH(a.approved_date)>=10,1,0))) = $year_filter ";
                    }

                    $sql .= " ORDER BY a.approved_date DESC";

                    $data['approvals'] = [];
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['thai_date'] = $this->dateToThai($row['approved_date']);
                        $data['approvals'][] = $row;
                    }

                } elseif ($tab == 'users') { 
                    $data['title'] = "รายชื่อผู้ใช้งานทั้งหมด";
                    $data['view_mode'] = 'admin_user_list';

                    $search_user = isset($_GET['search_user']) ? mysqli_real_escape_string($conn, $_GET['search_user']) : '';
                    $dept_user   = isset($_GET['dept_user']) ? intval($_GET['dept_user']) : 0;
                    
                    $data['filter_user_name'] = $search_user;
                    $data['filter_user_dept'] = $dept_user;

                    // ปรับ SQL ให้แสดงทุกคน (รวม Admin) เพื่อให้ High-Admin เห็นและแก้ได้
                    $sql = "SELECT u.*, p.*, d.thai_name AS department, b.remaining_balance 
                            FROM users u
                            LEFT JOIN user_profiles p ON u.id = p.user_id
                            LEFT JOIN departments d ON p.department_id = d.id
                            LEFT JOIN v_user_budget_summary b ON u.id = b.user_id 
                            WHERE 1=1 ";

                    if (!empty($search_user)) {
                        $sql .= " AND (p.first_name LIKE '%$search_user%' OR p.last_name LIKE '%$search_user%') ";
                    }
                    if ($dept_user > 0) {
                        $sql .= " AND d.id = $dept_user ";
                    }

                    $sql .= " ORDER BY d.id, p.first_name ASC";
                    
                    $data['user_list'] = [];
                    $result = mysqli_query($conn, $sql);

                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['remaining_balance'] = $this->getRemainingBalance($conn, $row['id']);  
                        $data['user_list'][] = $row;
                    }
                    

                

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
?>