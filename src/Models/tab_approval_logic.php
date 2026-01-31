<?php

function showAndSearchApprove($conn)
{
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
    if (is_numeric($min_amount) && !is_numeric($max_amount)) {
        $max_amount = $min_amount;
    } elseif (!is_numeric($min_amount) && is_numeric($max_amount)) {
        $min_amount = $max_amount;
    }

    // กำหนดค่า default กลับเป็น 0 หากไม่มีการกรอกเลยทั้งคู่ (สำหรับ Amount)
    $min_amount = is_numeric($min_amount) ? $min_amount : 0;
    $max_amount = is_numeric($max_amount) ? $max_amount : 0;
    // ---------------------------------------------------------
    // 2. สร้างรายการ "ปีงบประมาณ" (Dynamic Year List)
    // ---------------------------------------------------------
    // ดึงวันที่ต่ำสุดและสูงสุดจากระบบ
    $sql_years = "SELECT MIN(approved_date) as min_date, MAX(approved_date) as max_date FROM budget_received WHERE deleted_at IS NULL ";
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

                            FROM budget_received a
                            JOIN users u ON a.user_id = u.id 
                            JOIN user_profiles p ON u.id = p.user_id 
                            LEFT JOIN departments d ON p.department_id = d.id 
                            WHERE 1=1
                            ";

    // ---------------------------------------------------------
    // 4. ใส่ Logic Filter
    // ---------------------------------------------------------

    // filter deleted data
    $sql .= "AND a.deleted_at IS NULL AND p.deleted_at IS NULL";
    // filter data for admin
    $sql = applyPermissionFilter($sql);

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
        $row['thai_date'] = dateToThai($row['approved_date']);
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

    return $data;

}

function addReceiveBudget($conn){
   // 1. รับค่าจากฟอร์มและป้องกัน SQL Injection
                // สังเกต: รับค่า user_id ครั้งเดียวและใช้ตัวแปรชื่อ $user_id ตลอดการทำงาน
                $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
                $amount = floatval($_POST['amount']);
                $approved_date = mysqli_real_escape_string($conn, $_POST['approved_date']);
                $remark = mysqli_real_escape_string($conn, $_POST['remark']);
                $full_name = mysqli_real_escape_string($conn, $_POST['target_full_name']);

                // 2. คำนวณปีงบประมาณ (Fiscal Year)
                $timestamp = strtotime($approved_date);
                $year_th = date('Y', $timestamp) + 543;

                // 3. เริ่ม Transaction (เพื่อความปลอดภัยข้อมูล)
                mysqli_begin_transaction($conn);

                try {
                    // A. บันทึกข้อมูลงบประมาณ
                    $sql_budget = "INSERT INTO budget_received 
                                (user_id, approved_amount, approved_date, remark) 
                                VALUES 
                                ('$user_id', '$amount', '$approved_date', '$remark')
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
                    // ใน Production อาจเปลี่ยน echo เป็นการบันทึก error log ลงไฟล์แทน
                } 

}