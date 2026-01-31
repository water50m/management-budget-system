<?php
// src/Controllers/DashboardController.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/saveLogFunction.php';
require_once __DIR__ . '/../../views//dashboard/approveTableFunction.php';
require_once __DIR__ . '/../../views//dashboard/expenseTableFunction.php';
require_once __DIR__ . '/../../views//dashboard/userTableFunction.php';

include_once __DIR__ . "/../Helper/function.php";
require_once __DIR__ . '/../Models/tab_approval_logic.php';
require_once __DIR__ . '/../Models/tab_users_logic.php';
require_once __DIR__ . '/../Models/tab_logs_logic.php';
require_once __DIR__ . '/../Models/tab_expense_logic.php';
class DashboardController
{
    public function index()
    {
        global $conn;

        // 1. ตรวจสอบสิทธิ์
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $session_role = $_SESSION['role'];
        $data = [];

        // ==================================================================================
        // 🟢 ส่วนที่ 1: จัดการ POST REQUEST (บันทึกข้อมูล) ** ทำก่อนแสดงผลเสมอ **
        // ==================================================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {


            if (isset($_POST['action']) && $_POST['action'] == 'add_budget') {
                addReceiveBudget($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'delete_budget') {
                submitDeleteAprove($conn);
            }
            // 1.2 Action: เพิ่มรายการใช้จ่าย (Add Expense)
            if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {
                addExpense($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'delete_expense') {
                submitDeleteExpense($conn);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
                submitDeleteUser($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'restore_data') {
                restoreData($conn);
            }
        }

        // ==================================================================================
        // 🟢 ส่วนที่ 2: เตรียมข้อมูลสำหรับ VIEW (GET REQUEST)
        // ==================================================================================

        // 2.1 ดึงหมวดหมู่รายจ่าย (Categories) ส่งไปทำ Dropdown ใน Modal
        $data['categories_list'] = [];
        $res_cat = mysqli_query($conn, "SELECT * FROM expense_categories");
        if ($res_cat) {
            while ($c = mysqli_fetch_assoc($res_cat)) $data['categories_list'][] = $c;
        }

        // 2.2 ตั้งค่าตัวแปร Search & Filter พื้นฐาน
        $data['search_keyword'] = '';
        $data['search_dept'] = 0;
        $data['search_year'] = 0;



        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $dept_filter = isset($_GET['dept']) ? intval($_GET['dept']) : 0;

        $current_fiscal_year = (date('n') >= 10) ? date('Y') + 544 : date('Y') + 543;
        $year_filter = isset($_GET['year']) && $_GET['year'] != 0 ? intval($_GET['year']) : $current_fiscal_year;

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
                          FROM budget_received
                          WHERE deleted_at IS NULL 
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
        if ($session_role == 'admin' || $session_role == 'high-admin') { // รองรับ high-admin ด้วย

            if (!$target_id) { // ถ้าไม่ได้ระบุ ID (ดูตารางรวม)

                if ($tab == 'approval') {
   
                    $data = array_merge($data, showAndSearchApprove($conn));
                } elseif ($tab == 'expense') {

                    $data = array_merge($data, showAndSearchExpense($conn));
                } elseif ($tab == 'users') {

                    $data = array_merge($data, showAndSearchUsers($conn));
                } elseif ($tab == 'logs' && $session_role == 'high-admin') {
  
                    $data = array_merge($data, showAndManageLogs($conn));
                } else {
                    // ... (Logic เดิม: Request Table) ...
                    $data['title'] = "ภาพรวมคำของบประมาณ (Request)";
                    $data['view_mode'] = 'admin_request_table';

                    $sql = "SELECT u.id, p.prefix, p.first_name, p.last_name, 
                                   d.thai_name AS department
                            FROM users u 
                            JOIN user_profiles p ON u.id = p.user_id 
                            LEFT JOIN departments d ON p.department_id = d.id
                            WHERE p.deleted_at IS NULL
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
    private function loadUserDetail($conn, $view_id, &$data, $is_admin_viewing)
    {
        $data['view_mode'] = 'user_detail';
        $data['is_admin_viewing'] = $is_admin_viewing;

        $sql_name = "SELECT p.prefix, p.first_name, p.last_name, d.thai_name AS department FROM user_profiles p LEFT JOIN departments d ON p.department_id = d.id WHERE p.user_id = $view_id AND p.deleted_at IS NULL";
        $res_name = mysqli_query($conn, $sql_name);
        $data['profile'] = mysqli_num_rows($res_name) > 0 ? mysqli_fetch_assoc($res_name) : ['prefix' => '', 'first_name' => 'Unknown', 'department' => '-'];
        $data['budget'] = $this->calculateBudget($conn, $view_id);
        $data['title'] = $is_admin_viewing ? "รายละเอียด: " . $data['profile']['first_name'] : "Dashboard ของคุณ";
    }

    // ฟังก์ชันคำนวณงบ (ใช้ตารางใหม่ budget_expenses ที่มี source_type แล้ว)
    private function calculateBudget($conn, $uid)
    {
        $budget = ['travel' => 0, 'book' => 0, 'computer' => 0, 'medical' => 0, 'total_expense' => 0];

        // 2. รายจ่าย (Expenses) - ปรับให้รองรับ category เป็นภาษาอังกฤษ
        $res_ex = mysqli_query($conn, "SELECT * FROM budget_expenses WHERE user_id = $uid AND deleted_at IS NULL");
        while ($r = mysqli_fetch_assoc($res_ex)) {
            if (isset($budget[$r['category']])) {
                $budget[$r['category']] += $r['amount'];
            }
        }
        $budget['total_expense'] = $budget['travel'] + $budget['book'] + $budget['computer'] + $budget['medical'];
        return $budget;
    }
}
