<?php
function showAndSearchExpense($conn)
{
    $data['title'] = "ประวัติการเบิกจ่ายงบประมาณ";
    $data['view_mode'] = 'admin_expense_table';

    // ---------------------------------------------------------
    // 1. รับค่า Pagination (Helper)
    // ---------------------------------------------------------
    $pg = getPaginationParams(10);
    $limit  = $pg['limit'];
    $page   = $pg['page'];
    $offset = $pg['offset'];

    // ... (ส่วนดึง Categories & Departments เหมือนเดิม) ...
    // 1.1 ดึงข้อมูลหมวดหมู่
    $cat_sql = "SELECT * FROM expense_categories ORDER BY name_th ASC";
    $cat_res = mysqli_query($conn, $cat_sql);
    $data['categories_list'] = [];
    while ($c = mysqli_fetch_assoc($cat_res)) {
        $data['categories_list'][] = $c;
    }

    // 1.2 ดึงข้อมูลภาควิชา
    $dept_sql = "SELECT * FROM departments ORDER BY thai_name ASC";
    $dept_res = mysqli_query($conn, $dept_sql);
    $data['departments_list'] = [];
    while ($d = mysqli_fetch_assoc($dept_res)) {
        $data['departments_list'][] = $d;
    }

    // ... (ส่วนรับค่า Filter Inputs เหมือนเดิม) ...
    $search_text = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
    $start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
    $end_date   = isset($_GET['end_date'])   ? mysqli_real_escape_string($conn, $_GET['end_date'])   : '';
    $cat_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
    $min_amt = (isset($_GET['min_amount']) && $_GET['min_amount'] !== '')
        ? floatval(str_replace(',', '', $_GET['min_amount']))
        : '';
    $max_amt = (isset($_GET['max_amount']) && $_GET['max_amount'] !== '')
        ? floatval(str_replace(',', '', $_GET['max_amount']))
        : '';
    $dept_filter = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
    $allowed_date_types = ['approved', 'created', 'updated']; // รายชื่อฟิลด์ที่อนุญาต
    $date_type = (isset($_GET['date_type']) && in_array($_GET['date_type'], $allowed_date_types))
        ? $_GET['date_type']
        : 'approved'; // ถ้าส่งค่ามั่วมา ให้ดีดกลับเป็น approved
    $year_filter = (isset($_GET['year']) && intval($_GET['year']) != 0)
        ? intval($_GET['year'])
        : current_fiscal_year();
    $select_id = isset($_GET['show_id']) ? intval($_GET['show_id']) : 0;
    // ... (Logic จับคู่ข้อมูล Date/Amount เหมือนเดิม) ...
    if ($start_date !== '' && $end_date === '') {
        $end_date = $start_date;
    } elseif ($start_date === '' && $end_date !== '') {
        $start_date = $end_date;
    }

    if (is_numeric($min_amt) && !is_numeric($max_amt)) {
        $max_amt = $min_amt;
    } elseif (!is_numeric($min_amt) && is_numeric($max_amt)) {
        $min_amt = $max_amt;
    }

    // ... (Logic สร้าง Year List เหมือนเดิม) ...
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
    // ---------------------------------------------------------
    // 🟡 สร้าง WHERE Clause (ใช้ร่วมกันทั้ง Count และ Main Query)
    // ---------------------------------------------------------
    $where_sql = " WHERE 1=1 AND e.deleted_at IS NULL AND p.deleted_at IS NULL ";

    // Permission Filter
    // (ต้องระวัง! ฟังก์ชัน applyPermissionFilter ปกติมันเติม WHERE หรือ AND? 
    // สมมติว่ามันเติมเงื่อนไขต่อท้าย SQL ให้ ถ้ามันเริ่มด้วย WHERE ต้องแก้ให้สอดคล้อง)
    // วิธีที่ปลอดภัยคือ เรียกใช้ function แล้วเอามาต่อท้าย
    $temp_sql = "SELECT * FROM budget_expenses e JOIN users u ON e.user_id = u.upid JOIN user_profiles p ON u.upid = p.user_id WHERE 1=1 ";
    $filtered_sql = applyPermissionFilter($temp_sql);
    // ดึงเฉพาะส่วนที่เติมเพิ่มมา (อันนี้อาจจะยากถ้า function มัน return sql เต็ม)
    // ถ้า applyPermissionFilter คืนค่าเป็น SQL เต็มๆ ให้ใช้วิธีเดิมของคุณคือเอามาต่อท้าย Query หลัก

    // ปีงบประมาณ
    if ($year_filter > 0) {
        $where_sql .= " AND (YEAR(e.approved_date) + IF(MONTH(e.approved_date) >= 10, 1, 0) + 543) = $year_filter ";
    }
    // Search Text
    if (!empty($search_text)) {
        $search_safe = addcslashes($search_text, "%_");

        // 3. นำไปใช้ใน Query
        $where_sql .= " AND (p.first_name LIKE '%$search_safe%' OR p.last_name LIKE '%$search_safe%' OR e.description LIKE '%$search_safe%') ";
    }
    // Date Range
    if (!empty($start_date) && !empty($end_date)) {
        $col = ($date_type == 'created') ? "DATE(e.created_at)" : "e.approved_date";
        $where_sql .= " AND $col BETWEEN '$start_date' AND '$end_date' ";
    } elseif (!empty($start_date)) {
        $col = ($date_type == 'created') ? "DATE(e.created_at)" : "e.approved_date";
        $where_sql .= " AND $col >= '$start_date' ";
    } elseif (!empty($end_date)) {
        $col = ($date_type == 'created') ? "DATE(e.created_at)" : "e.approved_date";
        $where_sql .= " AND $col <= '$end_date' ";
    }
    // Category
    if ($cat_filter > 0) {
        $where_sql .= " AND e.category_id = $cat_filter ";
    }
    // Department
    if ($dept_filter > 0) {
        $where_sql .= " AND d.id = $dept_filter ";
    }
    // Amount
    if ($min_amt !== '') {
        $where_sql .= " AND e.amount >= $min_amt ";
    }
    if ($max_amt !== '') {
        $where_sql .= " AND e.amount <= $max_amt ";
    }
    if ($select_id > 0) {
        $where_sql .= " AND e.id = $select_id";
    }


    // ---------------------------------------------------------
    // 🟡 2. Query นับจำนวนทั้งหมด (Count Total)
    // ---------------------------------------------------------
    // ต้อง JOIN เหมือน Query หลัก เพื่อให้เงื่อนไข WHERE ทำงานได้ถูกต้อง
    $count_sql = "SELECT COUNT(*) as total 
                  FROM budget_expenses e
                  JOIN users u ON e.user_id = u.upid
                  JOIN user_profiles p ON u.upid = p.user_id
                  LEFT JOIN expense_categories c ON e.category_id = c.id
                  LEFT JOIN departments d ON p.department_id = d.id
                  $where_sql";

    // ใส่ Permission Filter (แบบ Hack: เอา SQL ไปผ่าน function แล้วดึงค่า total ออกมา ถ้าทำได้)
    // หรือถ้า applyPermissionFilter แค่เติม string ก็เอามาต่อท้าย
    $count_sql = applyPermissionFilter($count_sql);
    $res_count = mysqli_query($conn, $count_sql);
    $total_rows = ($res_count) ? mysqli_fetch_assoc($res_count)['total'] : 0;

    if ($limit > 0) {
        $total_pages = ceil($total_rows / $limit);
    } else {
        $total_pages = 1;
    }

    // ---------------------------------------------------------
    // 🟡 3. Query หลัก (Main Query)
    // ---------------------------------------------------------
    $sql = "SELECT e.*, p.user_id,
                   p.prefix, p.first_name, p.last_name, 
                   c.name_th as category_name,
                   d.thai_name as department
            FROM budget_expenses e
            JOIN users u ON e.user_id = u.upid
            JOIN user_profiles p ON u.upid = p.user_id
            LEFT JOIN expense_categories c ON e.category_id = c.id
            LEFT JOIN departments d ON p.department_id = d.id
            $where_sql ";

    $sql = applyPermissionFilter($sql); // ใส่ Filter สิทธิ์

    $sql .= " ORDER BY e.approved_date DESC, e.created_at DESC";
    // ✅ ใส่ Limit / Offset
    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }

    // 4. รัน Query
    $data['expenses'] = [];
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("SQL Error: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['thai_date'] = dateToThai($row['approved_date']);
        $data['expenses'][] = $row;
    }

    // ---------------------------------------------------------
    // 🟡 5. ส่งค่ากลับไป View
    // ---------------------------------------------------------
    $data['pagination'] = [
        'current_page' => $page,
        'total_pages'  => $total_pages,
        'total_rows'   => $total_rows,
        'limit'        => $limit
    ];

    $data['filters'] = [
        'search' => $search_text,
        'date_type' => $date_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'cat_id' => $cat_filter,
        'min_amount' => $min_amt,
        'max_amount' => $max_amt,
        'dept_id' => $dept_filter,
        'year' => $year_filter,
        'limit' => $limit // ส่ง limit กลับไปด้วย
    ];

    return $data;
}


function addExpense($conn)
{
    $user_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);
    $amount_needed = floatval($_POST['amount']);
    $category_id = intval($_POST['category_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $full_name = mysqli_real_escape_string($conn, $_POST['target_name']);
    $submit_page = $_POST['submit_page'];
    $submit_tab = isset($_POST['submit_tab']) ? $_POST['sbmit_tab'] : '';
    $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;
    mysqli_begin_transaction($conn);


    try {
        // ---------------------------------------------------------
        // A. บันทึกรายจ่ายลงตารางหลัก (budget_expenses)
        // ---------------------------------------------------------
        $approved_date = mysqli_real_escape_string($conn, $_POST['approved_date']);
        $timestamp = strtotime($approved_date);

        // 2. หามร พ.ศ. ปกติก่อน (User เดิม)
        $year_th = date('Y', $timestamp) + 543;

        // 3. หาเดือน (1-12)
        $month = date('n', $timestamp);

        // 4. คำนวณปีงบประมาณ
        if ($month >= 10) {
            // ถ้าเป็นเดือน 10, 11, 12 ให้ถือเป็นปีงบประมาณหน้า
            $fiscal_year = $year_th + 1;
        } else {
            // ถ้าเป็นเดือน 1-9 ให้ใช้ปีปัจจุบัน
            $fiscal_year = $year_th;
        }

        // กำหนด Type เป็น 'FIFO' หรือ 'System' เพื่อให้รู้ว่าระบบตัดเอง
        // (ถ้า Database คุณบังคับ ENUM 'current_year','carry_over' อาจต้องไปแก้ DB หรือใส่ค่าใดค่าหนึ่งไปก่อน)
        $budget_source = 'FIFO';

        $sql_ins = "INSERT INTO budget_expenses 
                                (user_id, category_id, description, amount, approved_date, budget_source_type, fiscal_year) 
                                VALUES 
                                ('$user_id', '$category_id', '$description', '$amount_needed', '$approved_date', '$budget_source', '$fiscal_year')";

        if (!mysqli_query($conn, $sql_ins)) {
            throw new Exception("Error Inserting Expense: " . mysqli_error($conn));
        }

        $new_expense_id = mysqli_insert_id($conn);

        // ---------------------------------------------------------
        // B. ค้นหาใบอนุมัติ (FIFO Logic แบบรวมถุง)
        // ---------------------------------------------------------

        // ✅ Query เดียว ดึงหมดทุกใบที่มีเงินเหลือ เรียงตามวันที่อนุมัติ (เก่าสุดขึ้นก่อน)
        // ตัดเงื่อนไข Fiscal Year ออก เพื่อให้มันมองเห็นงบทุกก้อน
        $sql_app = "SELECT a.id, a.amount, a.approved_date, 
                                COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id AND deleted_at IS NULL), 0) as used_so_far
                                FROM budget_received a
                                WHERE a.user_id = '$user_id'
                                AND a.approved_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) -- (Optional) กรองใบที่เก่าเกิน 2 ปีทิ้ง ถ้าไม่ใช้ก็ลบบรรทัดนี้ได้
                                AND deleted_at IS NULL
                                HAVING (a.amount - used_so_far) > 0
                                ORDER BY a.approved_date ASC"; // หัวใจสำคัญของ FIFO คือตรงนี้ (เก่าไปใหม่)

        $res_app = mysqli_query($conn, $sql_app);
        $money_to_cut = $amount_needed;

        // ---------------------------------------------------------
        // C. วนลูปตัดเงินทีละใบ
        // ---------------------------------------------------------
        while ($row = mysqli_fetch_assoc($res_app)) {
            if ($money_to_cut <= 0) break;

            $available_on_this_slip = $row['amount'] - $row['used_so_far'];
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

        $_SESSION['tragettab'] = 'expense';
        $_SESSION['tragetfilters'] = $new_expense_id;
        $_SESSION['show_btn'] = true;
        $_SESSION['fiscal_year'] = $fiscal_year;
        // Redirect
        if ($profile_id > 0) {
            header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($total_msg));
        } else {
            header("Location: index.php?page=$submit_page&status=success&tab=" . $submit_tab . "&toastMsg=" . urlencode($total_msg));
        }
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        // echo "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=$submit_page&tab=$submit_tab&status=error&toastMsg=ไม่สามารถทำรายการได้ กรุณาลองใหม่อีกครั้ง");
        exit;
    }
}

function handleEditExpense($conn)
{
    // 1. รับค่าจาก Form
    $page = $_POST['submit_page'] ?? 'dashboard';
    $tab = $_POST['submit_tab'] ?? 'expense';
    $profile_id = $_POST['profile_id'] ?? 0;

    // ข้อมูลสำหรับ Update
    $id = $_POST['expense_id'];
    $amount = $_POST['amount']; // ค่าใหม่
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    // ข้อมูลสำหรับ Log
    $target_user_id = $_POST['target_user_id'] ?? 0;
    $actor_id = $_SESSION['user_id'] ?? 0;

    // ---------------------------------------------------------
    // 🔍 STEP 1: ดึงข้อมูลเก่าออกมาดูก่อน (เพื่อเทียบว่าอะไรเปลี่ยน)
    // ---------------------------------------------------------
    $sql_old = "SELECT amount, approved_date FROM budget_expenses WHERE id = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();
    $old_data = $res_old->fetch_assoc();
    $stmt_old->close();

    // ถ้าไม่เจอข้อมูล (กรณีผิดพลาด) ให้กำหนดค่าเริ่มต้นเป็นว่าง
    $old_amount = $old_data['amount'] ?? 0;
    $old_date = $old_data['approved_date'] ?? '';

    // ---------------------------------------------------------
    // 📅 STEP 2: จัดการวันที่และปีงบประมาณ (ค่าใหม่)
    // ---------------------------------------------------------
    $raw_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : (isset($_POST['approved_date']) ? $_POST['approved_date'] : '');

    if (empty($raw_date)) {
        $approved_date = date('Y-m-d');
        $timestamp = time();
    } else {
        $timestamp = strtotime($raw_date);
        if ($timestamp === false) $timestamp = time();
        $approved_date = date('Y-m-d', $timestamp);
    }

    // คำนวณ Fiscal Year
    $month = (int)date('n', $timestamp);
    $year_ad = (int)date('Y', $timestamp);
    $fiscal_year_ad = ($month >= 10) ? $year_ad + 1 : $year_ad;
    $fiscal_year_thai = $fiscal_year_ad + 543;

    // ---------------------------------------------------------
    // 📝 STEP 3: สร้างข้อความเปรียบเทียบ (Change Log)
    // ---------------------------------------------------------
    $change_details = [];
    $msg_text = "แก้ไขรายละเอียดรายการ (ID: $id)"; // Default message

    // เทียบยอดเงิน (ถ้ายอดไม่เท่าเดิม)
    if (floatval($old_amount) != floatval($amount)) {
        $change_details[] = "ยอดเงิน(บาท): " . number_format($old_amount, 2) . " ➝ " . number_format($amount, 2);
    }

    // เทียบวันที่
    if ($old_date != $approved_date) {
        $old_date_th = date('d/m/', strtotime($old_date)) . (date('Y', strtotime($old_date)) + 543);
        $new_date_th = date('d/m/', strtotime($approved_date)) . (date('Y', strtotime($approved_date)) + 543);
        $change_details[] = "วันที่: " . $old_date_th . " ➝ " . $new_date_th;
    }

    if (!empty($change_details)) {
        $msg_text = "แก้ไข (ID: $id): " . implode(", ", $change_details);
    }

    // =========================================================
    // 🔴 เริ่มต้น TRANSACTION
    // =========================================================
    mysqli_begin_transaction($conn);

    try {
        // ---------------------------------------------------------
        // 💾 STEP 4.1: อัปเดตข้อมูลตารางแม่ (budget_expenses)
        // ---------------------------------------------------------
        $sql = "UPDATE budget_expenses 
                SET amount = ?, category_id = ?, approved_date = ?, description = ?, fiscal_year = ? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare Statement Failed (Expenses): " . $conn->error);

        // Bind parameters: d=double(amount), i=int(cat_id), s=string(date), s=string(desc), i=int(year), i=int(id)
        // หมายเหตุ: amount ควรเป็น d (double) หรือ s (string) ก็ได้ แต่ถ้าเป็นทศนิยมแนะนำ d
        $stmt->bind_param("sissii", $amount, $category_id, $approved_date, $description, $fiscal_year_thai, $id);

        if (!$stmt->execute()) {
            throw new Exception("Execute Failed (Expenses): " . $stmt->error);
        }
        $stmt->close();

        // ---------------------------------------------------------
        // 💾 STEP 4.2: อัปเดตตารางลูก (budget_usage_logs) ถ้ามีการเปลี่ยนยอดเงิน
        // ---------------------------------------------------------
        if (floatval($old_amount) != floatval($amount)) {
            // **จุดที่แก้:** SQL เดิมของคุณเขียนผิดชื่อตาราง (UPDATE budget_expenses SET ... WHERE expense_id)
            // ต้องเป็น budget_usage_logs ครับ
            $sql_update_log = "UPDATE budget_usage_logs 
                               SET amount_used = ?
                               WHERE expense_id = ?";

            $stmt_uel = $conn->prepare($sql_update_log);
            if (!$stmt_uel) throw new Exception("Prepare Statement Failed (Usage Logs): " . $conn->error);

            $stmt_uel->bind_param("di", $amount, $id); // d = double/decimal

            if (!$stmt_uel->execute()) {
                throw new Exception("Execute Failed (Usage Logs): " . $stmt_uel->error);
            }
            $stmt_uel->close();
        }

        // ---------------------------------------------------------
        // ✅ STEP 5: ยืนยันข้อมูล (COMMIT)
        // ---------------------------------------------------------
        mysqli_commit($conn);


        // --- ทำงานส่วนที่ไม่เกี่ยวกับ Database Transaction (Log & Redirect) ---

        // บันทึก Log
        if (function_exists('logActivity')) {
            logActivity($conn, $actor_id, $target_user_id, 'edit_expense', $msg_text, $id);
        }

        // ตั้งค่า Session
        $_SESSION['tragettab'] = 'expense';
        $_SESSION['tragetfilters'] = $id;
        $_SESSION['show_btn'] = true;
        $_SESSION['fiscal_year'] = $fiscal_year_thai;

        // Redirect Success
        if ($profile_id > 0) {
            header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($msg_text));
        } else {
            header("Location: index.php?page=$page&status=success&tab=" . $tab . "&toastMsg=" . urlencode($msg_text));
        }
        exit();
    } catch (Exception $e) {
        // =========================================================
        // ⚫ เกิดข้อผิดพลาด -> ยกเลิกทั้งหมด (ROLLBACK)
        // =========================================================
        mysqli_rollback($conn);

        // Redirect Error
        $redirect_url = "index.php?page=$page" . ($tab ? "&tab=$tab" : "") . ($profile_id ? "&id=$profile_id" : "");
        // ส่ง msg error ไปด้วยเพื่อ debug (ใน production อาจจะซ่อน message)
        header("Location: $redirect_url&status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}
