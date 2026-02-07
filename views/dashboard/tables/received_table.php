<?php
$theme = 'emerald';
// 2. ตั้งค่า Theme สี (Default: Emerald สีเขียวเงินเข้า)
$bgLight = "bg-{$theme}-50";
$textDark = "text-{$theme}-700";
$borderBase = "border-{$theme}-200";
$focusRing = "focus-within:ring-{$theme}-500 focus-within:border-{$theme}-500";
$btnBg = "bg-{$theme}-600";
$btnHover = "hover:bg-{$theme}-700";
$textAmount = "text-{$theme}-600"; // สีตัวเลขเงิน
$border = " border border-{$theme}-200"
?>
<div id="table-received" class="bg-white rounded-xl shadow-lg overflow-hidden border <?php echo $borderBase; ?> flex flex-col min-h-0 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
        <table class="w-full text-sm text-left">
            <thead class="sticky top-0 z-10 <?php echo $bgLight; ?> <?php echo $textDark; ?> border-b <?php echo $borderBase; ?>">
                <tr>
                    <th class="px-6 py-4 font-bold text-center w-16">
                        <select name="limit"
                            hx-get="index.php?page=dashboard&tab=received"
                            hx-target="#tab-content"
                            hx-include="[name='search'], [name='dept_id'], [name='date_type'], [name='start_date'], [name='end_date'], [name='min_amount'], [name='max_amount'], [name='year']"
                            class="border-gray-300 rounded shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-1 cursor-pointer">
                            <?php
                            $limits = [10 => '10', 25 => '25', 50 => '50', 100 => '100', 1000000 => 'ทั้งหมด'];
                            foreach ($limits as $val => $text):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo ($pagination['limit'] == $val) ? 'selected' : ''; ?>>
                                    <?php echo $text; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </th>

                    <th class="px-6 py-4 font-bold whitespace-nowrap w-[12%]">วันที่อนุมัติ</th>
                    <th class="px-6 py-4 font-bold  w-[15%]">ผู้ขออนุมัติ</th>
                    <th class="px-6 py-4 font-bold">รายละเอียด</th>
                    <th class="px-6 py-4 font-bold w-[12%]">ยอดอนุมัติ (บาท)</th>
                    <th class="px-6 py-4 font-bold text-center w-[15%] min-w-[200px] ">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($received)): ?>
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400">ไม่พบรายการที่ค้นหา</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($received as $index => $row): ?>
                        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                            <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>

                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                <?php echo $row['thai_date']; ?>

                                <?php
                                // 1. แปลงวันที่จาก Database เป็น Timestamp
                                $record_time = strtotime($row['approved_date']);

                                // 2. หา Timestamp ของเวลา "2 ปีที่แล้ว" นับจากปัจจุบัน
                                $two_years_ago = strtotime('-2 years');

                                // 3. เปรียบเทียบ: ถ้าเวลาที่บันทึก น้อยกว่า (เก่ากว่า) 2 ปีที่แล้ว
                                if ($record_time < $two_years_ago) {
                                    // ปิด quote ของ class ก่อน แล้วค่อยเปิด title ใหม่
                                    echo '<div class="text-red-500 text-xs mt-1 font-semibold cursor-help" title="รายการนี้จะไม่ถูกนำไปคำนวนในยอดคงเหลือ">
                                        * รายการนี้มีอายุเกิน 2 ปี
                                    </div>';
                                } else if (!is_null($row['received_left']) && $row['received_left'] == 0) {
                                    // ปิด quote ของ class ก่อน แล้วค่อยเปิด title ใหม่
                                    echo '<div class="text-red-500 text-xs mt-1 font-semibold cursor-help" title="รายการนี้จะไม่ถูกนำไปคำนวนในยอดคงเหลือ">
                                        * รายการนี้ถูกตัดยอดไปใช้แล้วทั้งหมด
                                    </div>';
                                } else if (!is_null($row['received_left']) && $row['received_left'] > 0) {
                                    // ปิด quote ของ class ก่อน แล้วค่อยเปิด title ใหม่
                                    $formated_ = number_format($row['received_left'], 2);
                                    echo '<div class="text-red-500 text-xs mt-1 font-semibold cursor-help" title="รายการนี้จะถูกนำไปคำนวนในยอดคงเหลือเพียงบางส่วน (เหลือ ' . $formated_ . 'บาท)">
                                        * รายการนี้ถูกตัดยอดไปใช้แล้วบางส่วน
                                    </div>';
                                }
                                ?>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">
                                    <?php echo $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name']; ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo $row['department']; ?></div>
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                <?php echo $row['remark']; ?>
                            </td>

                            <td class="px-6 py-4 font-mono font-bold <?php echo $textAmount; ?> text-lg whitespace-nowrap">
                                + <?php echo number_format($row['amount'], 2); ?>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    <a hx-get="index.php?page=profile&id=<?php echo $row['user_id']; ?>"
                                        hx-target="#app-container"
                                        hx-swap="innerHTML"
                                        hx-push-url="true"
                                        class="cursor-pointer bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1 rounded hover:bg-blue-100 text-xs font-bold transition flex items-center gap-1">
                                        <i class="fas fa-user"></i> ดูโปรไฟล์
                                    </a>
                                    <?php $isUsed = (isset($row['total_used']) && $row['total_used'] > 0); ?>

                                    <button type="button"
                                        onclick="openEditBudgetReceivedModal(
                                                    '<?php echo $row['id']; ?>', 
                                                    '<?php echo $row['user_id']; ?>', 
                                                    '<?php echo $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name']; ?>',
                                                    '<?php echo $row['amount']; ?>', 
                                                    '<?php echo $row['approved_date']; ?>', 
                                                    '<?php echo addslashes($row['remark']); ?>',
                                                    '<?php echo $isUsed ?>'
                                                )"
                                        class="bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded hover:bg-orange-100 text-xs font-bold transition flex items-center gap-1">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>


                                    <?php if ($isUsed): ?>
                                        <button type="button" disabled
                                            onmouseenter="showGlobalAlert('⚠️ ไม่สามารถลบได้: งบประมาณบางส่วน หรือทั้งหมดของรายการนี้ถูกใช้ไปแล้ว')"
                                            onmouseleave="hideGlobalAlert()"
                                            class="text-gray-300 cursor-not-allowed p-2 rounded-full">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            onclick="openDeleteModal(
                                                        '<?php echo $row['id']; ?>', 
                                                        'delete_budget'
                                                    )"
                                            class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition"
                                            title="ลบรายการนี้">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $hx_selectors = "[name='search'], [name='dept_id'], [name='date_type'], [name='start_date'], [name='end_date'], [name='min_amount'], [name='max_amount'], [name='year']";

    include_once __DIR__ . '/../../../src/Helper/FE_function.php';
    if (function_exists('renderPaginationBar')) {
        renderPaginationBar(
            $pagination,       // ข้อมูล Pagination
            'received',         // ชื่อ Tab (tab=expense)
            $hx_selectors,     // ตัวกรองที่จะส่งไปด้วย (hx-include)
            $theme              // ธีมสี (purple)
        );
    }
    include_once __DIR__ . "/../../../includes/confirm_delete.php";
    ?>

</div>