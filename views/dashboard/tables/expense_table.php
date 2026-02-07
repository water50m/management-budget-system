<?php $color = 'purple' ?>
<div id="table-expense" class="bg-white rounded-xl shadow-lg border border-<?= $color ?>-200 flex flex-col min-h-0 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
        <table class="w-full text-sm text-left">
            <thead class="sticky top-0 z-10 bg-<?= $color ?>-50 text-<?= $color ?>-900 border-b border-<?= $color ?>-100 shadow-sm">
                <tr>
                    <th class="px-6 py-4 font-bold text-center w-16">
                        <select name="limit"
                            hx-get="index.php?page=dashboard&tab=expense"
                            hx-target="#tab-content"
                            hx-include="[name='search_text'], [name='dept_user'], [name='role_user']"
                            class="border-gray-300 rounded shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-1  cursor-pointer">
                            <?php
                            $limits = [
                                10 => '10',
                                25 => '25',
                                50 => '50',
                                100 => '100',
                                1000000 => 'ทั้งหมด'
                            ];

                            foreach ($limits as $val => $text):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo ($pagination['limit'] == $val) ? 'selected' : ''; ?>>
                                    <?php echo $text; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </th>
                    <th class="px-6 py-2 font-bold whitespace-nowrap w-[12%]">วันที่อนุมัติ</th>

                    <th class="px-6 py-2 font-bold w-[15%]">ผู้เบิกจ่าย</th>

                    <th class="px-6 py-2 font-bold whitespace-nowrap text-center w-[12%]">หมวดหมู่</th>

                    <th class="px-6 py-2 font-bold">รายละเอียด</th>

                    <th class="px-6 py-2 font-bold text-right whitespace-nowrap w-[12%]">จำนวนเงิน (บาท)</th>

                    <th class="px-6 py-2 font-bold text-center w-[15%] min-w-[140px] whitespace-nowrap">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="p-12 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-file-invoice-dollar text-4xl mb-3 text-gray-300"></i>
                                ไม่พบรายการที่ค้นหา
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $index => $row): ?>
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-<?= $color ?>-100 transition group border-b border-gray-100">
                            <td class="px-6 py-4 text-center text-gray-400">
                                <?php
                                // คำนวณลำดับที่ (เพื่อให้เลขรันต่อกันเมื่อเปลี่ยนหน้า)
                                if ($pagination) {
                                    echo number_format(($pagination['current_page'] - 1) * $pagination['limit'] + ($index + 1));
                                } else {
                                    echo $index + 1;
                                }
                                ?>
                            </td>
                            <td class="px-6 py-2 text-gray-600 whitespace-nowrap">
                                <?php echo $row['thai_date']; ?>
                            </td>
                            <td class="px-6 py-2">
                                <div class="font-bold text-gray-800"><?php echo $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo $row['department']; ?></div>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-center">
                                <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-<?= $color ?>-100 text-<?= $color ?>-700 border border-<?= $color ?>-200">
                                    <?php echo $row['category_name'] ? $row['category_name'] : '-'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-2 text-gray-600">
                                <?php echo $row['description']; ?>
                            </td>
                            <td class="px-6 py-2 font-mono font-bold text-red-600 text-lg whitespace-nowrap text-right">
                                <?php echo number_format($row['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    <a hx-get="index.php?page=profile&id=<?php echo $row['user_id']; ?>"
                                        hx-target="#app-container"
                                        hx-swap="innerHTML"
                                        hx-push-url="true"
                                        class="cursor-pointer bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1 rounded hover:bg-blue-100 text-xs font-bold transition flex items-center gap-1">
                                        <i class="fas fa-user"></i> ดูโปรไฟล์
                                    </a>

                                    <?php
                                    // รวมชื่อ-นามสกุลเพื่อส่งไป Modal
                                    $fullName = $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];

                                    // เช็ค key ของวันที่ให้ชัวร์ (ปกติใน DB มักเป็น expense_date หรือ approved_date)
                                    // สมมติว่าใช้ expense_date ตามฟอร์มรายจ่าย
                                    $rawDate = isset($row['approved_date']) ? $row['approved_date'] : $row['created_at'];
                                    ?>
                                    <button type="button"
                                        onclick="openEditExpenseModal(
                                                                '<?= $row['id'] ?>', 
                                                                '<?= $row['user_id'] ?>', 
                                                                '<?= addslashes($fullName) ?>', 
                                                                '<?= $row['amount'] ?>', 
                                                                '<?= $rawDate ?>', 
                                                                '<?= isset($row['category_id']) ? $row['category_id'] : '' ?>', 
                                                                '<?= addslashes($row['description']) ?>'
                                                            )"
                                        class="bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded hover:bg-orange-100 text-xs font-bold transition flex items-center gap-1">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>

                                    <button type="button"
                                        onclick="openDeleteModal(
                                                        '<?php echo $row['id']; ?>', 
                                                        'delete_expense'
                                                    )"
                                        class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition"
                                        title="ลบรายการนี้">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    include_once __DIR__ . '/../../../src/Helper/FE_function.php';
    $hx_selectors = "[name='search'], [name='dept_id'], [name='cat_id'], [name='date_type'], [name='start_date'], [name='end_date'], [name='min_amount'], [name='max_amount'], [name='year']";

    if (function_exists('renderPaginationBar')) {
        renderPaginationBar(
            $pagination,       // ข้อมูล Pagination
            'expense',         // ชื่อ Tab (tab=expense)
            $hx_selectors,     // ตัวกรองที่จะส่งไปด้วย (hx-include)
            $color             // ธีมสี (purple)
        );
    }
    include_once __DIR__ . "/../../../includes/confirm_delete.php";
    include_once __DIR__ . '/../../../includes/modal_edit_expense.php';

    ?>
</div>