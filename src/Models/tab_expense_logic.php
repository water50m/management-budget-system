<?php

function showAndSearchExpense($conn)
{
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
    $year_filter = isset($_GET['year']) && $_GET['year'] != 0 ? intval($_GET['year']) : current_fiscal_year();
    // ---------------------------------------------------------
    // 🔄 Logic จับคู่ข้อมูล (ถ้ามาแค่อย่างเดียว ให้เป็นค่าเดียวกัน)
    // ---------------------------------------------------------

    // คู่ที่ 1: วันที่ (Date Range)
    if ($start_date !== '' && $end_date === '') {
        $end_date = $start_date; // มีแต่เริ่ม -> ให้สิ้นสุดเท่ากับเริ่ม
    } elseif ($start_date === '' && $end_date !== '') {
        $start_date = $end_date; // มีแต่สิ้นสุด -> ให้เริ่มเท่ากับสิ้นสุด
    }

    // คู่ที่ 2: จำนวนเงิน (Amount Range)
    // ใช้ is_numeric เพราะค่าอาจจะเป็น 0 ได้
    if (is_numeric($min_amt) && !is_numeric($max_amt)) {
        $max_amt = $min_amt;
    } elseif (!is_numeric($min_amt) && is_numeric($max_amt)) {
        $min_amt = $max_amt;
    }

    // ---------------------------------------------------------
    // สร้างรายการ "ปีงบประมาณ" (Dynamic Year List)
    // ---------------------------------------------------------
    // ดึงวันที่ต่ำสุดและสูงสุดจากระบบ
    $sql_years = "SELECT MIN(approved_date) as min_date, MAX(approved_date) as max_date FROM budget_expenses WHERE deleted_at IS NULL";
    $res_years = mysqli_query($conn, $sql_years);
    $row_years = mysqli_fetch_assoc($res_years);

    $years_list = [];

    if ($row_years['min_date'] && $row_years['max_date']) {
        // ฟังก์ชันคำนวณปีงบประมาณไทย (เดือน >= 10 คือปีหน้า, +543 เป็น พ.ศ.)
        $calcFiscal = function ($date) {
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
                            WHERE 1=1
                            ";

    // --- ใส่เงื่อนไขการกรอง ---
    $sql .= "AND e.deleted_at IS NULL AND p.deleted_at IS NULL";
    //filter for some admin
    $sql = applyPermissionFilter($sql);

    if ($year_filter > 0) {
        // สูตรคำนวณ: ปี ค.ศ. + (ถ้าเดือน>=10 ให้บวก 1) + 543 = ปีงบไทย
        $sql .= " AND (YEAR(e.approved_date) + IF(MONTH(e.approved_date) >= 10, 1, 0) + 543) = $year_filter ";
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
    } elseif (!empty($end_date)) {
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
        die("SQL Error:-- " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['thai_date'] = dateToThai($row['approved_date']);
        $data['expenses'][] = $row;
    }
    return $data;
    
}

function addExpense($conn)
{
    $page = 'users';
    $user_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);
    $amount_needed = floatval($_POST['amount']);
    $category_id = intval($_POST['category_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $full_name = mysqli_real_escape_string($conn, $_POST['target_name']);
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
                                FROM budget_received a
                                WHERE a.user_id = '$user_id'
                                AND a.approved_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) -- (Optional) กรองใบที่เก่าเกิน 2 ปีทิ้ง ถ้าไม่ใช้ก็ลบบรรทัดนี้ได้
                                AND deleted_at IS NULL
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
        $log_desc = "รายการ: $description จำนวน " . number_format($amount_needed, 2) . " บาท";

        logActivity($conn, $actor_id, $user_id, 'add_expense', $log_desc);

        $total_msg = "เพิ่มรายการตัดยอดของ $full_name \n" . $log_desc;
        mysqli_commit($conn);

        // Redirect
        if ($page == '') {
            header("Location: index.php?page=dashboard&status=success&toastMsg=" . urlencode($total_msg));
        } else {
            header("Location: index.php?page=dashboard&status=success&tab=" . $page . "&toastMsg=" . urlencode($total_msg));
        }
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
        exit;
    }
}
