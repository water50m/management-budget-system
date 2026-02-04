<?php

// --- ส่วน Logic (ที่แก้ให้ถูกต้องแล้ว) ---
$year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : (date('Y') + 543);
$raw_dept = $_GET['department_id'] ?? '';
$dept_id = ($raw_dept !== '') ? (int)$raw_dept : ''; // แก้ Logic ให้ถูกต้องตามที่คุยกัน

// ดึงข้อมูล
$summary_data = getFpaSummary($conn, $year, $dept_id);

$i = 1;
$grand_total = 0;
$sum_travel = 0;
$sum_book = 0;
$sum_comp = 0;
$sum_sci = 0;

// --- ส่วนแสดงผล HTML ---
if (!empty($summary_data)) {
    foreach ($summary_data as $row) {
        $row_total = $row['travel'] + $row['book'] + $row['comp'] + $row['sci'];
        
        if ($row_total <= 0) continue;
        $grand_total += $row_total;

        $sum_travel += $row['travel'];
        $sum_book   += $row['book'];
        $sum_comp   += $row['comp'];
        $sum_sci    += $row['sci'];
?>
        <tr class="hover:bg-blue-50/30 border-b border-gray-200 transition-colors">
            <td class="px-4 py-3 text-center border-r border-gray-200 font-medium text-gray-500"><?php echo $i++; ?></td>
            <td class="px-4 py-3 font-semibold text-gray-800 border-r border-gray-200"><?php echo $row['name']; ?></td>
            <td class="px-2 py-3 text-right text-red-600 font-medium border-r border-red-100 bg-red-50/10"><?php echo $row['travel'] > 0 ? number_format($row['travel'], 2) : '-'; ?></td>
            <td class="px-2 py-3 text-right text-red-600 font-medium border-r border-red-100 bg-red-50/10"><?php echo $row['book'] > 0 ? number_format($row['book'], 2) : '-'; ?></td>
            <td class="px-2 py-3 text-right text-red-600 font-medium border-r border-red-100 bg-red-50/10"><?php echo $row['comp'] > 0 ? number_format($row['comp'], 2) : '-'; ?></td>
            <td class="px-2 py-3 text-right text-red-600 font-medium border-r border-red-100 bg-red-50/10"><?php echo $row['sci'] > 0 ? number_format($row['sci'], 2) : '-'; ?></td>
            <td class="px-2 py-3 text-right font-bold text-gray-900 bg-gray-50"><?php echo number_format($row_total, 2); ?></td>
        </tr>
<?php
    }
} else {
    echo '<tr><td colspan="10" class="text-center py-8 text-gray-400">ไม่พบข้อมูล</td></tr>';
}
?>

<tr style="display:none">
    <td>
        <span id="grandTotalCell" hx-swap-oob="true"><?php echo number_format($grand_total, 2); ?></span>
        <span id="headerYearText" hx-swap-oob="true"><?php echo $year; ?></span>
        <span id="headerYearTextLastTwo" hx-swap-oob="true"><?php echo substr($year, -2); ?></span>
        <span id="totalTravel" hx-swap-oob="true"><?php echo number_format($sum_travel, 2); ?></span>
        <span id="totalBook" hx-swap-oob="true"><?php echo number_format($sum_book, 2); ?></span>
        <span id="totalComp" hx-swap-oob="true"><?php echo number_format($sum_comp, 2); ?></span>
        <span id="totalSci" hx-swap-oob="true"><?php echo number_format($sum_sci, 2); ?></span>
    </td>
</tr>

