<?php
// src/Controllers/DashboardController.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/saveLogFunction.php';

include_once __DIR__ . "/../Helper/function.php";

require_once __DIR__ . '/../Models/dashboard/tab_received_logic.php';
require_once __DIR__ . '/../Models/dashboard/tab_users_logic.php';
require_once __DIR__ . '/../Models/dashboard/tab_logs_logic.php';
require_once __DIR__ . '/../Models/dashboard/tab_expense_logic.php';
require_once __DIR__ . '/../Models/dashboard/tab_summary_logic.php';

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

        if ($_SESSION['role'] == 'user') {
            $_id = $_SESSION['user_id'];

            header("Location: index.php?page=profile&id=$_id...");
            exit();
        }

        $page = $_GET['page'] ?? 'dashboard';

        // ✅ แก้ไขเงื่อนไข: ต้องอยู่หน้า dashboard และไม่มีการส่งค่า tab มาเท่านั้น
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $page === 'dashboard' && (!isset($_GET['tab']) || empty($_GET['tab']))) {
            // สั่ง Redirect ไปที่ Tab แรกของ Dashboard
            header("Location: index.php?page=dashboard&tab=users");
            exit();
        }

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
            if (isset($_POST['action']) && $_POST['action'] == 'edit_budget_received') {
                handleEditReceived($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'edit_budget_expense') {
                handleEditExpense($conn);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'update_role') {
                submitUpdateRole($conn);
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

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'received';
        $data['current_tab'] = $tab;
        $target_id = isset($_GET['id']) ? intval($_GET['id']) : null;

        // --- กรณี: ADMIN MODE (ดูภาพรวม) ---

        $role_list = getAllAdminRole($conn);
        if (in_array($session_role, $role_list)) {
            if (!$target_id) {
                if ($tab == 'received') {
                    $data = array_merge($data, showAndSearchReceived($conn));
                } elseif ($tab == 'expense') {
                    $data = array_merge($data, showAndSearchExpense($conn));
                } elseif ($tab == 'users') {
                    $data = array_merge($data, showAndSearchUsers($conn));
                } elseif ($tab == 'logs' && $session_role == 'high-admin') {
                    $data = array_merge($data, showAndManageLogs($conn));
                } elseif ($tab == 'summary' && !isset($_GET['query_over_all'])) {
                    $data = array_merge($data, showAndSearchOverview($conn));
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
                ($data);
                require_once __DIR__ . '/../../views/dashboard/index.php';
                exit;
            } else if ($hx_target == 'tab-content') {

                // 🟢 กรณีที่ 3: กด Tab ย่อย (เปลี่ยนแค่ไส้ใน)
                // (Logic เดิมของคุณ)
                extract($data);
                include_once __DIR__ . '/../../views/dashboard/tabs/' . $tab . '_view.php';
                exit;
            } else if ($hx_target == 'fpaTableBody') {
                require_once __DIR__ . '/../Helper/table_summary_FPA.php';
                exit;
            } else if ($hx_target == 'table-received') {
                extract($data);
                require_once __DIR__ . '/../../views/dashboard/tables/received_table.php';
                exit;
            } else if ($hx_target == 'table-expense') {
                extract($data);
                require_once __DIR__ . '/../../views/dashboard/tables/expense_table.php';
                exit;
            } else if ($page != 'dashboard') {
                require_once __DIR__ . '/../../views/profile/idex.php';
            }
        }

        require_once __DIR__ . '/../../includes/header.php';
        extract($data);
        require_once __DIR__ . '/../../views/dashboard/index.php';
        require_once __DIR__ . '/../../includes/footer.php';
        // 🛑 สำคัญมาก! สั่งหยุดทันที เพื่อไม่ให้โหลด Header/Footer ซ้ำ
        exit();
    }

    public function showPDF()
    {
        global $conn;
        require_once __DIR__ . '/../Helper/print_fpa_pdf.php';
    }
}



function submitDeleteExpense($conn)
{
    // 1. รับค่า ID
    $expense_id = isset($_POST['id_to_delete']) ? intval($_POST['id_to_delete']) : 0;
    $submit_tab = isset($_POST['submit_tab']) ? $_POST['submit_tab'] : '';
    $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;

    // ดึง User ID คนทำรายการ (Actor)
    $actor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if ($expense_id > 0) {

        // ดึงข้อมูลเก่า (เอาไว้ทำ Log)
        $sql_check = "SELECT b.description, b.amount, b.user_id,
                             up.prefix, up.first_name, up.last_name 
                      FROM budget_expenses b 
                      JOIN user_profiles up ON b.user_id = up.user_id 
                      WHERE b.id = $expense_id";
        $res_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($res_check);

        $log_desc = "ลบรายการรายจ่าย ID: $expense_id";
        if ($old_data) {
            $log_desc = "ลบรายการ: " . $old_data['description'] . " (จำนวน " . number_format($old_data['amount']) . " บาท)";
        }

        // =========================================================
        // 🔴 เริ่มต้น TRANSACTION (จุดสำคัญ)
        // =========================================================
        mysqli_begin_transaction($conn);

        try {
            // ---------------------------------------------------------
            // ✅ Step 1: ลบ (Soft Delete) ตารางแม่ (budget_expenses)
            // ---------------------------------------------------------
            $sql = "UPDATE budget_expenses SET deleted_at = NOW() WHERE id = $expense_id";
            $result1 = mysqli_query($conn, $sql);

            if (!$result1) {
                throw new Exception("Error Delete Expense: " . mysqli_error($conn));
            }

            // ---------------------------------------------------------
            // ✅ Step 2: ลบ (Soft Delete) ตารางลูก (budget_usage_logs)
            // ---------------------------------------------------------
            $sql_delete_expense_log = "UPDATE budget_usage_logs SET deleted_at = NOW() WHERE expense_id = $expense_id";
            $result2 = mysqli_query($conn, $sql_delete_expense_log);

            if (!$result2) {
                throw new Exception("Error Delete Logs: " . mysqli_error($conn));
            }

            // ---------------------------------------------------------
            // ✅ Step 3: ถ้ามาถึงตรงนี้แปลว่าสำเร็จทั้งคู่ -> ยืนยันข้อมูล (COMMIT)
            // ---------------------------------------------------------
            mysqli_commit($conn);

            // --- ส่วนบันทึก Log และ Redirect ทำหลังจาก Commit สำเร็จแล้ว ---

            // บันทึก Activity Log
            if (function_exists('logActivity')) {
                logActivity($conn, $actor_id, $old_data['user_id'] ?? 0, 'delete_expense', $log_desc, $expense_id);
            }

            // เตรียมข้อความแจ้งเตือน
            $name = ($old_data['prefix'] ?? '') . " " . ($old_data['first_name'] ?? '') . " " . ($old_data['last_name'] ?? '');
            $more_details = "ลบข้อมูลของ $name \n";
            $toastMsg = $more_details . 'รายละเอียด: ' . $log_desc;

            // Redirect
            if ($profile_id > 0) {
                header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($toastMsg));
            } else {
                header("Location: index.php?page=dashboard&status=success&tab=" . $submit_tab . "&toastMsg=" . urlencode($toastMsg));
            }
            exit();
        } catch (Exception $e) {
            // =========================================================
            // ⚫ เกิดข้อผิดพลาด -> ยกเลิกทั้งหมด (ROLLBACK)
            // =========================================================
            mysqli_rollback($conn);

            // แสดง Error หรือ Redirect ไปหน้า Error
            echo "Transaction Failed: " . $e->getMessage();
            exit();
        }
    }
}
