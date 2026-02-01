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
    $search     = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $dept_id    = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
    $date_type  = isset($_GET['date_type']) ? $_GET['date_type'] : 'approved';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $min_amount = isset($_GET['min_amount']) ? floatval(str_replace(',', '', $_GET['min_amount'])) : 0;
    $max_amount = isset($_GET['max_amount']) ? floatval(str_replace(',', '', $_GET['max_amount'])) : 0;
    $year_filter = isset($_GET['year']) && $_GET['year'] != 0 ? intval($_GET['year']) : current_fiscal_year();

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
                    LEFT JOIN departments d ON p.department_id = d.id ";

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
        $where_sql .= " AND (p.first_name LIKE '%$search%' OR p.last_name LIKE '%$search%' OR a.remark LIKE '%$search%') ";
    }
    if ($year_filter > 0) {
        $where_sql .= " AND (YEAR(a.approved_date) + IF(MONTH(a.approved_date) >= 10, 1, 0) + 543) = $year_filter ";
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
                   p.prefix, p.first_name, p.last_name, 
                   a.amount AS amount,      
                   a.remark,                        
                   a.approved_date,                 
                   a.record_date,
                   COALESCE((SELECT SUM(amount_used) FROM budget_usage_logs WHERE approval_id = a.id), 0) as total_used
            " . $base_joins . $where_sql;

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

    die;

    try {
        // A. บันทึกข้อมูลงบประมาณ
        $sql_budget = "INSERT INTO budget_received 
                                (user_id, amount, approved_date, remark, fiscal_year) 
                                VALUES 
                                ('$user_id', '$amount', '$approved_date', '$remark', '$fiscal_year')
                                ";

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
        $target_name_phrase = "เพิ่มข้อมูลให้กับ $full_name \nรายการ: ";
        $total_msg = $target_name_phrase . $log_desc;
        // กลับไปหน้า Dashboard พร้อมสถานะสำเร็จ
        header("Location: index.php?page=dashboard&status=success&toastMsg=" . urlencode($total_msg));
        exit; // ต้องมี exit เพื่อหยุดการทำงานทันที

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกการบันทึกทั้งหมด (Rollback)
        mysqli_rollback($conn);
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
        die;
        // ใน Production อาจเปลี่ยน echo เป็นการบันทึก error log ลงไฟล์แทน
    }
}

function submitDeleteAprove($conn)
{

    // 1. รับค่า ID และแปลงเป็นตัวเลข
    $id = isset($_POST['delete_received_id']) ? intval($_POST['delete_received_id']) : 0;
    $name = isset($_POST['target_name']) ? intval($_POST['target_name']) : '';
    // ดึง ID คนทำรายการจาก Session
    $actor_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    // 2. ตรวจสอบว่า ID ถูกต้องหรือไม่
    if ($id > 0) {

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่าออกมาสร้าง Description ให้ Log
        // ---------------------------------------------------------
        // *ตรวจสอบชื่อตารางให้ตรงกับ DB จริงของคุณ (budget_received หรือ budget_years)*
        $sql_check = "SELECT * FROM budget_received WHERE id = $id";
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
            logActivity($conn, $actor_id, $id, 'delete_received', $log_desc, $id);

            // 4. Redirect กลับ
            $more_details = "ลบข้อมูลของ $name \n";
            $toastMsg = $more_details . 'รายละเอียด: ' . $log_desc;
            header("Location: index.php?page=dashboard&tab=received&status=success&toastMsg=" . urlencode($toastMsg));
            exit();
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
            exit();
        }
    } else {
        echo "Invalid ID.";
        exit();
    }
}
