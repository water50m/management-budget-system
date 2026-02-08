<?php

function showAndSearchReceived($conn)
{
    $data['title'] = "สรุปยอดงบประมาณที่อนุมัติ";
    $data['view_mode'] = 'admin_received_table';

    // ---------------------------------------------------------
    // 1. รับค่า Pagination (เพิ่มส่วนนี้)
    // ---------------------------------------------------------
    // เรียก Helper ที่เราทำไว้
    $pg = getPaginationParams(10); // ค่า Default 10 รายการต่อหน้า
    $limit  = $pg['limit'];
    $page   = $pg['page'];
    $offset = $pg['offset'];

    // ---------------------------------------------------------
    // 2. รับค่าจากตัวกรอง (Filter Inputs)
    // ---------------------------------------------------------
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
    $dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
    $allowed_date_types = ['approved', 'created'];
    $date_type = (isset($_GET['date_type']) && in_array($_GET['date_type'], $allowed_date_types))
        ? $_GET['date_type']
        : 'approved';
    $start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
    $end_date   = isset($_GET['end_date'])   ? mysqli_real_escape_string($conn, $_GET['end_date'])   : '';
    $min_amount = isset($_GET['min_amount']) ? floatval(str_replace(',', '', $_GET['min_amount'])) : 0;
    $max_amount = isset($_GET['max_amount']) ? floatval(str_replace(',', '', $_GET['max_amount'])) : 0;
    $year_filter = isset($_GET['year']) ? intval($_GET['year']) : current_fiscal_year();
    $select_id = isset($_GET['show_id']) ? intval($_GET['show_id']) : 0;
    // ... (Logic จับคู่ข้อมูล Date/Amount เหมือนเดิม) ...
    if ($start_date !== '' && $end_date === '') {
        $end_date = $start_date;
    } elseif ($start_date === '' && $end_date !== '') {
        $start_date = $end_date;
    }

    if (is_numeric($min_amount) && !is_numeric($max_amount)) {
        $max_amount = $min_amount;
    } elseif (!is_numeric($min_amount) && is_numeric($max_amount)) {
        $min_amount = $max_amount;
    }

    $min_amount = is_numeric($min_amount) ? $min_amount : 0;
    $max_amount = is_numeric($max_amount) ? $max_amount : 0;

    // ... (Logic สร้างรายการปีงบประมาณ เหมือนเดิม) ...
    $sql_years = "SELECT MIN(approved_date) as min_date, MAX(approved_date) as max_date FROM budget_received WHERE deleted_at IS NULL ";
    $res_years = mysqli_query($conn, $sql_years);
    $row_years = mysqli_fetch_assoc($res_years);

    // (ขออนุญาตละโค้ดส่วนสร้าง years_list ไว้ ให้ใช้ของเดิมได้เลยครับ มันยาว)
    // ... code สร้าง $years_list ...
    // สมมติว่ามี $years_list แล้ว
    $years_list = [];
    if ($row_years['min_date'] && $row_years['max_date']) {
        $calcFiscal = function ($date) {
            $time = strtotime($date);
            $y = date('Y', $time);
            $m = date('n', $time);
            return ($m >= 10) ? ($y + 1 + 543) : ($y + 543);
        };
        $min_fy = $calcFiscal($row_years['min_date']);
        $max_fy = $calcFiscal($row_years['max_date']);
        for ($y = $max_fy + 1; $y >= $min_fy - 1; $y--) {
            $years_list[] = $y;
        }
    } else {
        $cur_fy = (date('n') >= 10) ? (date('Y') + 1 + 543) : (date('Y') + 543);
        $years_list = [$cur_fy + 1, $cur_fy, $cur_fy - 1];
    }
    $data['years_list'] = $years_list;


    // ---------------------------------------------------------
    // 🟡 3. เตรียมเงื่อนไข WHERE และ JOIN (เพื่อใช้ซ้ำในการนับและดึงข้อมูล)
    // ---------------------------------------------------------

    // Base Table Joins (ใช้เหมือนกันทั้ง Count และ Select)
    $base_joins = " FROM budget_received a
        JOIN users u ON a.user_id = u.id 
        JOIN user_profiles p ON u.id = p.user_id 
        LEFT JOIN departments d ON p.department_id = d.id";

    // Base Condition
    $where_sql = " WHERE 1=1 AND a.deleted_at IS NULL AND p.deleted_at IS NULL ";

    // Permission Filter (ต้องประยุกต์ใช้นิดหน่อย)
    // ปกติ applyPermissionFilter จะคืนค่า SQL เต็มๆ หรือต่อท้าย
    // สมมติว่า applyPermissionFilter รับ string แล้ว return string ที่มี WHERE ต่อท้าย
    // เราจะใช้วิธีสร้าง SQL dummy ไปผ่าน function เพื่อเอาเงื่อนไขออกมา (หรือถ้า function return แค่ condition ก็ง่ายเลย)
    // **เพื่อให้ง่าย ผมจะสมมติว่าคุณเอา Logic ใน applyPermissionFilter มาแปะต่อตรงนี้ หรือใช้แบบเดิม**

    // *แก้ปัญหาเฉพาะหน้า:* ใช้วิธีสร้าง SQL เต็มแล้วค่อยแยกคงยาก 
    // แนะนำให้เอา Logic Permission มาใส่ตรงนี้ครับ (ตัวอย่าง):
    if ($_SESSION['role'] == 'admin') {
        // $where_sql .= " AND ... "; 
    }
    // หรือถ้าจะใช้ function เดิม ให้เอามาต่อท้ายทีหลังสุดตอนประกอบร่าง

    // --- Filter Logic ---
    if (!empty($search)) {
        // Escape ค่าพิเศษของ SQL (')

        // Escape ค่าพิเศษของ LIKE (% และ _) เพื่อไม่ให้ User พิมพ์ % แล้วดึงข้อมูลทั้งหมด
        $search_safe = addcslashes($search, "%_");

        $where_sql .= " AND (p.first_name LIKE '%$search_safe%' OR p.last_name LIKE '%$search_safe%' OR a.remark LIKE '%$search_safe%') ";
    }
    if ($year_filter > 0) {
        $where_sql .= " AND a.fiscal_year = $year_filter ";
    }
    if ($dept_id > 0) {
        $where_sql .= " AND d.id = $dept_id ";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $col_date = ($date_type == 'created') ? "DATE(a.record_date)" : "DATE(a.approved_date)";
        $where_sql .= " AND $col_date BETWEEN '$start_date' AND '$end_date' ";
    }
    if ($min_amount > 0) {
        $where_sql .= " AND a.amount >= $min_amount ";
    }
    if ($max_amount > 0) {
        $where_sql .= " AND a.amount <= $max_amount ";
    }
    if ($select_id > 0) {
        $where_sql .= " AND a.id = $select_id";
    }


    // ---------------------------------------------------------
    // 🟡 4. Query นับจำนวนทั้งหมด (Count Total)
    // ---------------------------------------------------------
    // เราใช้ $base_joins และ $where_sql ที่เตรียมไว้
    $count_sql = "SELECT COUNT(*) as total " . $base_joins . $where_sql;

    // (ถ้า function applyPermissionFilter จำเป็นต้องใช้ ให้เรียกตรงนี้ด้วยกับ count_sql)
    $count_sql = applyPermissionFilter($count_sql);



    $res_count = mysqli_query($conn, $count_sql);
    $total_rows = ($res_count) ? mysqli_fetch_assoc($res_count)['total'] : 0;

    // คำนวณจำนวนหน้า

    if ($limit > 0) {
        $total_pages = ceil($total_rows / $limit);
    } else {
        $total_pages = 1;
    }


    // ---------------------------------------------------------
    // 🟡 5. Query ดึงข้อมูลจริง (Main Select)
    // ---------------------------------------------------------
    $sql = "SELECT a.id, 
               d.thai_name AS department, 
               p.user_id,
               p.prefix, p.first_name, p.last_name, 
               a.amount AS amount,      
               a.remark,                        
               a.approved_date,                 
               a.record_date,
               
               -- 1. ยอดที่ใช้ไปทั้งหมด (ใช้ COALESCE เพื่อให้เป็น 0 ถ้าไม่มีการใช้)
               COALESCE((SELECT SUM(amount_used) 
                         FROM budget_usage_logs 
                         WHERE approval_id = a.id
                         AND deleted_at IS NULL), 0) as total_used,

               -- 2. ยอดคงเหลือ (received_left) ✅ เพิ่มส่วนนี้
               -- ถ้ายังไม่มีการใช้ (NULL) ค่าจะเป็น NULL
               -- ถ้าใช้แล้ว จะคำนวณยอดคงเหลือ (ห้ามติดลบ)
               GREATEST(
                   a.amount - (SELECT SUM(amount_used) 
                               FROM budget_usage_logs 
                               WHERE approval_id = a.id
                               AND deleted_at IS NULL), 
                   0
               ) as received_left " . $base_joins . $where_sql;

    // ใส่ Permission Filter ให้ Query หลัก
    $sql = applyPermissionFilter($sql);

    $sql .= " ORDER BY a.approved_date DESC";

    // ✅ ใส่ LIMIT / OFFSET (เฉพาะเมื่อ limit > 0)
    if ($limit > 0) {

        $sql .= " LIMIT $limit OFFSET $offset";
    }
    // ---------------------------------------------------------
    // 6. ประมวลผลและส่งค่า
    // ---------------------------------------------------------
    $data['received'] = [];
    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['thai_date'] = dateToThai($row['approved_date']);
            $data['received'][] = $row;
        }
    }

    // ส่งข้อมูล Pagination กลับไป
    $data['pagination'] = [
        'current_page' => $page,
        'total_pages'  => $total_pages,
        'total_rows'   => $total_rows,
        'limit'        => $limit
    ];

    $data['filters'] = [
        'search'     => $search,
        'dept_id'    => $dept_id,
        'date_type'  => $date_type,
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'min_amount' => $_GET['min_amount'] ?? '',
        'max_amount' => $_GET['max_amount'] ?? '',
        'year'       => $year_filter,
        'limit'      => $limit // ส่ง limit กลับไปโชว์ใน UI ด้วย
    ];

    return $data;
}

function addReceiveBudget($conn)
{
    // 1. รับค่าจากฟอร์มและป้องกัน SQL Injection
    // สังเกต: รับค่า user_id ครั้งเดียวและใช้ตัวแปรชื่อ $user_id ตลอดการทำงาน
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $approved_date = mysqli_real_escape_string($conn, $_POST['approved_date']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $full_name = mysqli_real_escape_string($conn, $_POST['target_full_name']);
    $submit_page = $_POST['submit_page'];
    $submit_tab = $_POST['submit_tab'];
    $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;



    // 2. คำนวณปีงบประมาณ (Fiscal Year)
    // 1. แปลงวันที่เป็น Timestamp
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

    // 3. เริ่ม Transaction (เพื่อความปลอดภัยข้อมูล)
    mysqli_begin_transaction($conn);

    try {

        // A. บันทึกข้อมูลงบประมาณ
        $expire_date = date('Y-m-d', strtotime($approved_date . " +2 years"));

        // 2. เพิ่ม expire_date เข้าไปใน SQL
        $sql_budget = "INSERT INTO budget_received 
                    (user_id, amount, approved_date, expire_date, remark, fiscal_year) 
                    VALUES 
                    ('$user_id', '$amount', '$approved_date', '$expire_date', '$remark', '$fiscal_year')
                    ";

        if (!mysqli_query($conn, $sql_budget)) {
            throw new Exception("บันทึกงบไม่สำเร็จ: " . mysqli_error($conn));
        }
        $new_budget_id = mysqli_insert_id($conn);

        // B. บันทึก Log (เรียกใช้ฟังก์ชันเดิมของคุณ)
        $word_remark = $remark ? $remark : 'ไม่มี';
        $actor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $log_desc = "เพิ่มงบประมาณปี $year_th จำนวน " . number_format($amount, 2) . " บาท \n(หมายเหตุ: $word_remark )";

        // เรียกใช้ฟังก์ชัน logActivity ($user_id คือ target_id)
        logActivity($conn, $actor_id, $user_id, 'add_budget', $log_desc,);

        // ยืนยันข้อมูลทั้งหมด (Commit)
        mysqli_commit($conn);
        $target_name_phrase = "ให้กับ $full_name \n ";
        $total_msg = "เพิ่มงบประมาณปี $year_th จำนวน " . number_format($amount, 2) . " บาท  $target_name_phrase \n(หมายเหตุ: $word_remark )";

        // กลับไปหน้า Dashboard พร้อมสถานะสำเร็จ
        $_SESSION['tragettab'] = 'received';
        $_SESSION['tragetfilters'] = $new_budget_id;
        $_SESSION['show_btn'] = true;
        $_SESSION['fiscal_year'] = $fiscal_year;
        $page = $submit_tab;

        if ($profile_id > 0) {
            header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($total_msg));
        } else {
            header("Location: index.php?page=$submit_page&status=success&tab=" . $submit_tab . "&toastMsg=" . urlencode($total_msg));
        }
        exit; // ต้องมี exit เพื่อหยุดการทำงานทันที

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกการบันทึกทั้งหมด (Rollback)
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
        die;
        header("Location: index.php?page=$submit_page&status=success&tab=" . $page . "&toastMsg=เกิดข้อผิดพลาดในการทำรายการ");
    }
}

function submitDeleteAprove($conn)
{

    // 1. รับค่า ID และแปลงเป็นตัวเลข
    $id = isset($_POST['id_to_delete']) ? intval($_POST['id_to_delete']) : 0;
    // ดึง ID คนทำรายการจาก Session
    $actor_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $submit_page = $_POST['submit_page'];
    $submit_tab = isset($_POST['submit_tab']) ? $_POST['submit_tab'] : '';
    $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;
    // 2. ตรวจสอบว่า ID ถูกต้องหรือไม่
    if ($id > 0) {

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่าออกมาสร้าง Description ให้ Log
        // ---------------------------------------------------------
        // *ตรวจสอบชื่อตารางให้ตรงกับ DB จริงของคุณ (budget_received หรือ budget_years)*
        $sql_check = "SELECT b.remark, b.amount, b.user_id,
                    up.prefix, up.first_name, up.last_name 
                FROM budget_received b 
                JOIN user_profiles up 
                WHERE b.id = $id";
        $res_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($res_check);

        // สร้างข้อความ Log
        $log_desc = "ลบการอนุมัติงบ ID: $id"; // ค่า Default
        if ($old_data) {
            // ตัวอย่าง: "ลบการอนุมัติงบ 50,000 บาท ของโครงการ ABC"
            // ปรับชื่อ field ตามตารางจริง (เช่น amount, remark, description)
            $amount_show = isset($old_data['amount']) ? number_format($old_data['amount']) : '-';
            $log_desc = "ลบการอนุมัติงบจำนวน $amount_show บาท ";
        }

        // ---------------------------------------------------------
        // ✅ Step 2: สั่งลบแบบ Soft Delete (ใช้ deleted_at)
        // ---------------------------------------------------------
        // แนะนำให้ใช้ deleted_at = NOW() เพื่อให้ตรงกับ View ที่เราเขียนไปก่อนหน้านี้
        $sql = "UPDATE budget_received SET deleted_at = NOW() WHERE id = $id";

        // 3. สั่งรันคำสั่ง SQL
        if (mysqli_query($conn, $sql)) {

            // ---------------------------------------------------------
            // ✅ Step 3: บันทึก Log เมื่อลบสำเร็จ
            // ---------------------------------------------------------
            // logActivity($conn, $actor_id, $target_id, $action, $desc)
            logActivity($conn, $actor_id, $old_data['user_id'], 'delete_received', $log_desc, $id);

            // 4. Redirect กลับ
            $name = $old_data['prefix'] . " " . $old_data['first_name'] . " " . $old_data['last_name'];

            $more_details = "ลบข้อมูลของ $name \n";
            $toastMsg = $more_details . 'รายละเอียด: ' . $log_desc;
            if ($profile_id > 0) {
                header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($toastMsg));
            } else {
                header("Location: index.php?page=dashboard&status=success&tab=" . $submit_tab . "&toastMsg=" . urlencode($toastMsg));
            }

            exit();
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
            // echo "เกิดข้อผิดพลาด: " . $e->getMessage();
            if ($profile_id > 0) {
                header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=เกิดปัญหากับการทำรายการ");
            } else {
                header("Location: index.php?page=dashboard&status=success&tab=" . $submit_tab . "&toastMsg=เกิดปัญหากับการทำรายการ");
            }
            exit();
        }
    } else {
        echo "Invalid ID.";
        exit();
    }
}

function handleEditReceived($conn)
{
    // 1. รับค่าจาก Form
    $page = $_POST['submit_page'] ?? 'dashboard';
    $tab = $_POST['submit_tab'] ?? 'received';
    $profile_id = $_POST['profile_id'] ?? 0;

    // ข้อมูลสำหรับ Update
    $id = $_POST['received_id'];
    $amount = $_POST['amount_real'];
    $remark = $_POST['remark'];

    // ข้อมูลสำหรับ Log
    $target_user_id = $_POST['user_id'] ?? 0;
    $actor_id = $_SESSION['user_id'] ?? 0;

    // ---------------------------------------------------------
    // 🔍 STEP 1: ดึงข้อมูลเก่าออกมาดูก่อน (เพื่อเทียบ Change Log)
    // ---------------------------------------------------------
    $sql_old = "SELECT amount, approved_date, remark FROM budget_received WHERE id = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();
    $old_data = $res_old->fetch_assoc();
    $stmt_old->close();

    $old_amount = $old_data['amount'] ?? 0;
    $old_date = $old_data['approved_date'] ?? '';
    $old_remark = $old_data['remark'] ?? '';

    // ---------------------------------------------------------
    // 📅 STEP 2: จัดการวันที่ และ คำนวณปีงบประมาณ (Fiscal Year)
    // ---------------------------------------------------------
    $raw_date = $_POST['approved_date'] ?? date('Y-m-d');

    if (empty($raw_date)) {
        $approved_date = date('Y-m-d');
        $timestamp = time();
    } else {
        $timestamp = strtotime($raw_date);
        if ($timestamp === false) $timestamp = time();
        $approved_date = date('Y-m-d', $timestamp);
    }

    // ✅ คำนวณ Fiscal Year: ถ้าเดือน >= 10 (ตุลาคม) ให้นับเป็นปีหน้า + 543
    $month = (int)date('n', $timestamp);
    $year_ad = (int)date('Y', $timestamp);
    $fiscal_year_ad = ($month >= 10) ? $year_ad + 1 : $year_ad;
    $fiscal_year_thai = $fiscal_year_ad + 543;

    // ---------------------------------------------------------
    // 📝 STEP 3: สร้างข้อความเปรียบเทียบ (Toast Msg)
    // ---------------------------------------------------------
    $change_details = [];

    // เทียบยอดเงิน (เปลี่ยนจาก " -> " เป็น " เป็น ")
    if (floatval($old_amount) != floatval($amount)) {
        $change_details[] = "ยอดเงิน(บาท): " . number_format($old_amount, 2) . " ➝ " . number_format($amount, 2);
    }

    // เทียบวันที่ (เปลี่ยนจาก " -> " เป็น " เป็น ")
    if ($old_date != $approved_date) {
        $old_date_th = date('d/m/', strtotime($old_date)) . (date('Y', strtotime($old_date)) + 543);
        $new_date_th = date('d/m/', strtotime($approved_date)) . (date('Y', strtotime($approved_date)) + 543);
        $change_details[] = "วันที่: " . $old_date_th . " ➝ " . $new_date_th;
    }

    // เทียบหมายเหตุ
    if (trim($old_remark) != trim($remark)) {
        $change_details[] = "มีการแก้ไขหมายเหตุ";
    }

    if (empty($change_details)) {
        $msg_text = "แก้ไขงบประมาณ (ID: $id)";
    } else {
        $msg_text = "แก้ไข (ID: $id): " . implode(", ", $change_details);
    }
    // ---------------------------------------------------------
    // 💾 STEP 4: อัปเดตข้อมูลลง Database (รวม fiscal_year)
    // ---------------------------------------------------------

    // 1. ✅ คำนวณวันหมดอายุใหม่ (Approved Date + 2 ปี)
    if (empty($approved_date)) {
        $approved_date = $old_date;
    }

    // 2. คำนวณวันหมดอายุ (+2 ปี) จากวันที่ที่ถูกต้องแล้ว
    $expire_date = date('Y-m-d', strtotime($approved_date . " +2 years"));

    // 2. ✅ เพิ่ม expire_date = ? ใน SQL
    $sql = "UPDATE budget_received 
            SET amount = ?, approved_date = ?, expire_date = ?, remark = ?, fiscal_year = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $redirect_url = "index.php?page=$page" . ($tab ? "&tab=$tab" : "") . ($profile_id ? "&id=$profile_id" : "");
        header("Location: $redirect_url&status=error&msg=prepare_fail");
        exit;
    }

    // 3. ✅ Bind Params: 
    // เพิ่ม 's' สำหรับ expire_date เข้าไป (เป็นตัวที่ 3)
    // เรียงลำดับ: amount(s), approved_date(s), expire_date(s), remark(s), fiscal_year(i), id(i)
    // กลายเป็น "ssssii"
    $stmt->bind_param("ssssii", $amount, $approved_date, $expire_date, $remark, $fiscal_year_thai, $id);

    // 3. Execute
    if ($stmt->execute()) {

        // ✅ บันทึก Log Activity (ใช้ข้อความ plain text)
        if (function_exists('logActivity')) {
            logActivity($conn, $actor_id, $target_user_id, 'edit_budget_received', $msg_text, $id);
        }

        // ✅ ตั้งค่า Session สำหรับ UX
        $_SESSION['tragettab'] = 'received';
        $_SESSION['tragetfilters'] = $id;
        $_SESSION['show_btn'] = true;

        // ✅ อัปเดตปีงบประมาณใน Session (เพื่อให้ Filter หน้าเว็บไม่งงเวลาวันที่เปลี่ยนปีงบ)
        $_SESSION['fiscal_year'] = $fiscal_year_thai;

        // ✅ Redirect พร้อม Toast Message (Plain Text)
        if ($profile_id > 0) {
            header("Location: index.php?page=profile&status=success&id=" . $profile_id . "&toastMsg=" . urlencode($msg_text));
        } else {
            header("Location: index.php?page=$page&status=success&tab=" . $tab . "&toastMsg=" . urlencode($msg_text));
        }
    } else {
        // ❌ Error
        $redirect_url = "index.php?page=$page" . ($tab ? "&tab=$tab" : "") . ($profile_id ? "&id=$profile_id" : "");
        header("Location: $redirect_url&status=error");
    }

    $stmt->close();
    exit;
}
