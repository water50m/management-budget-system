<?php
// src/Controllers/DashboardController.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/saveLogFunction.php';

include_once __DIR__ . "/../Helper/function.php";

require_once __DIR__ . '/../Models/tab_received_logic.php';
require_once __DIR__ . '/../Models/tab_users_logic.php';
require_once __DIR__ . '/../Models/tab_logs_logic.php';
require_once __DIR__ . '/../Models/tab_expense_logic.php';
class DashboardController
{
    public function index()
    {
        global $conn;
        require_once __DIR__ . '/../../includes/userRoleManageFunction.php';
        // 1. ตรวจสอบสิทธิ์
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }
        
        $page = $_GET['page'] ?? 'dashboard';

        // ✅ แก้ไขเงื่อนไข: ต้องอยู่หน้า dashboard และไม่มีการส่งค่า tab มาเท่านั้น
        if ($page === 'dashboard' && (!isset($_GET['tab']) || empty($_GET['tab']))) {
            // สั่ง Redirect ไปที่ Tab แรกของ Dashboard
            header("Location: index.php?page=dashboard&tab=received"); 
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
            if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {
                addExpense($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'delete_expense') {
                submitDeleteExpense($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
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
                if ($tab == 'received') {

                    $data = array_merge($data, showAndSearchApprove($conn));
                } elseif ($tab == 'expense') {

                    $data = array_merge($data, showAndSearchExpense($conn));
                } elseif ($tab == 'users') {

                    $data = array_merge($data, showAndSearchUsers($conn));
                } elseif ($tab == 'logs' && $session_role == 'high-admin') {

                    $data = array_merge($data, showAndManageLogs($conn));
                } 
                
            
            }
        
        }
        // ==================================================================================
        // 🟢 ส่วนที่ 4: HTMX RESPONSE (ส่งเฉพาะไส้ใน)
        // ==================================================================================
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            // ปิดการแสดงผล Error ชั่วคราวเพื่อให้ HTML ไม่พัง (Optional)
            // error_reporting(0); 
            $hx_target = $_SERVER['HTTP_HX_TARGET'] ?? '';
            if ($hx_target == 'app-container') {
                // 🟢 กรณีที่ 2: กดจาก Navbar (เปลี่ยนหน้าใหญ่)
                // ส่งไปทั้งหน้า Dashboard (แต่ไม่เอา Header/Footer หลัก)
                header("HX-Push-Url: index.php?page=dashboard&tab=" . $tab);
                extract($data);
                require_once __DIR__ . '/../../views/dashboard/index.php';
                exit;
            } elseif ($hx_target == 'tab-content') {

                // 🟢 กรณีที่ 3: กด Tab ย่อย (เปลี่ยนแค่ไส้ใน)
                // (Logic เดิมของคุณ)
                extract($data);
                include __DIR__ . '/../../views/dashboard/tabs/' . $tab . '_view.php';
                exit;
            }
        }

        require_once __DIR__ . '/../../includes/header.php';
        extract($data);
        require_once __DIR__ . '/../../views/dashboard/index.php';
        require_once __DIR__ . '/../../includes/footer.php';
        // 🛑 สำคัญมาก! สั่งหยุดทันที เพื่อไม่ให้โหลด Header/Footer ซ้ำ
        exit();
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

function submitDeleteExpense($conn)
{
    // 1. รับค่า ID
    $expense_id = isset($_POST['delete_target_id']) ? intval($_POST['delete_target_id']) : 0;
    $name = isset($_POST['delete_approval_id']) ? intval($_POST['delete_approval_id']) : '';

    // ดึง User ID คนทำรายการ (Actor)
    $actor_id = $_SESSION['user_id']; 

    if ($expense_id > 0) {
               

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่ามาก่อน (เพื่อเอาไปเขียน Description ใน Log)
        // ---------------------------------------------------------
        $sql_check = "SELECT description, amount FROM budget_expenses WHERE id = $expense_id";
        $res_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($res_check);
        

        $log_desc = "ลบรายการรายจ่าย ID: $expense_id"; // default description
        if ($old_data) {
            // ถ้าเจอข้อมูล ให้ระบุรายละเอียดให้ชัดเจน
            $log_desc = "ลบรายการ: " . $old_data['description'] . " (จำนวน " . number_format($old_data['amount']) . " บาท)";
        }

        // ---------------------------------------------------------
        // ✅ Step 2: ทำการลบ (แนะนำเป็น Soft Delete)
        // ---------------------------------------------------------
        // เปลี่ยนจาก DELETE เป็น UPDATE deleted_at
        $sql = "UPDATE budget_expenses SET deleted_at = NOW() WHERE id = $expense_id";
        

        // *หมายเหตุ: ถ้าคุณยังอยากใช้ Hard Delete (ลบถาวร) ให้ใช้บรรทัดล่างนี้แทนครับ
        // $sql = "DELETE FROM budget_expenses WHERE id = $expense_id";

        if (mysqli_query($conn, $sql)) {
            

            // ---------------------------------------------------------
            // ✅ Step 3: บันทึก Log (เมื่อลบสำเร็จแล้ว)
            // ---------------------------------------------------------
            // เรียกใช้ฟังก์ชัน saveActivityLog (หรือชื่อที่คุณตั้งไว้)
            // saveActivityLog($conn, $actor_id, $action_type, $description, $target_id);
            
            logActivity($conn, $actor_id, $expense_id, 'delete_expense', $log_desc, $expense_id);

            // ---------------------------------------------------------
            // ✅ Step 4: Redirect กลับ
            // ---------------------------------------------------------
            $more_details = "ลบข้อมูลของ $name \n";
            $toastMsg = $more_details . 'รายละเอียด: ' . $log_desc;
            header("Location: index.php?page=dashboard&tab=expense&status=deleted&toastMsg=" . urlencode($toastMsg));
            exit();
            
        } else {
            echo "Error: " . mysqli_error($conn);
            exit();
        }
    }
}