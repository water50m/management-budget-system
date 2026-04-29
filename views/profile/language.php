<?php
// --- 🌐 Auto Language Detection Logic ---
// ตรวจสอบว่าในชื่อมีตัวอักษรไทยหรือไม่ (ถ้าไม่มีภาษาไทยเลย ให้ถือว่าเป็นต่างชาติ/English)
$is_thai = preg_match('/[ก-๙]/', $user_info['first_name']);

// ชุดคำแปล (Dictionary)
$t = [
    'home' => $is_thai ? 'กลับหน้าหลัก' : 'Dashboard',
    'logout' => $is_thai ? 'ออกจากระบบ' : 'Logout',
    'role_level' => $is_thai ? 'ระดับสิทธิ์' : 'Role Level',
    'net_balance' => $is_thai ? 'ยอดคงเหลือสุทธิ' : 'Net Balance',
    'currency' => $is_thai ? 'THB' : 'THB', // หรือจะแก้เป็น Baht ก็ได้
    'currency_unit' => $is_thai ? 'บาท' : 'Baht',
    
    // Stats
    'total_received' => $is_thai ? 'รับทั้งหมด' : 'Total Income',
    'used_this_year' => $is_thai ? 'ใช้ไปปีนี้' : 'Expense (YTD)',
    'carried_over' => $is_thai ? 'ยกยอดมา' : 'Carried Over',
    
    // Form Filters
    'search_label' => $is_thai ? 'ค้นหา / รายละเอียด' : 'Search / Details',
    'search_placeholder' => $is_thai ? 'รายละเอียด / รายการ' : 'Description / Item',
    'fiscal_year' => $is_thai ? 'ปี' : 'Fiscal Year',
    'all_years' => $is_thai ? 'ทุกปีงบฯ' : 'All Years',
    'year_prefix' => $is_thai ? 'งบปี' : 'FY',
    'category' => $is_thai ? 'หมวดหมู่' : 'Category',
    'all_categories' => $is_thai ? '--ทุกหมวดหมู่--' : '--All Categories--',
    'type' => $is_thai ? 'ประเภท' : 'Type',
    'all_types' => $is_thai ? '--ทุกประเภท--' : '--All Types--',
    'type_income' => $is_thai ? 'ยอดรับ (Income)' : 'Income',
    'type_expense' => $is_thai ? 'ยอดตัด (Expense)' : 'Expense',
    'range_label' => $is_thai ? 'ช่วงเงิน (Min - Max)' : 'Amount Range (Min - Max)',
    'btn_filter' => $is_thai ? 'กรองข้อมูล' : 'Filter',
    
    // Table Headers
    'th_seq' => $is_thai ? 'ลำดับ' : '#',
    'th_date' => $is_thai ? 'วันที่อนุมัติ' : 'Approve Date',
    'th_desc' => $is_thai ? 'รายละเอียด / รายการ' : 'Description / Item',
    'th_cat' => $is_thai ? 'หมวดหมู่' : 'Category',
    'th_amount' => $is_thai ? 'จำนวนเงิน(บาท)' : 'Amount(THB)',
    'th_type' => $is_thai ? 'ประเภท' : 'Type',
    
    // Table Content
    'no_data' => $is_thai ? 'ไม่พบรายการเคลื่อนไหว' : 'No transactions found.',
    'summary_label' => $is_thai ? 'สรุปยอดรวม (เฉพาะรายการที่แสดง):' : 'Summary (Visible items):',
    'label_income' => $is_thai ? 'ยอดรับ' : 'INCOME',
    'label_expense' => $is_thai ? 'ยอดตัด' : 'EXPENSE',
    'label_total' => $is_thai ? 'ยอดรวม' : 'TOTAL',
];