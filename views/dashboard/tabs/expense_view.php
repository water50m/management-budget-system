<?php
include_once __DIR__ . '/../../../src/Helper/FE_function.php';

renderExpenseTableComponent(
    $expenses ?? [],
    $filters ?? [],
    $departments_list ?? [],
    $categories_list ?? [],
    $years_list ?? [],
    'purple',
    $pagination
);


// 🟢 1. เพิ่ม $pagination = null ใน parameter ตัวสุดท้าย
function renderExpenseTableComponent($expenses, $filters, $departments, $categories, $years = [], $color = 'purple', $pagination = null)
{
    // ป้องกัน Error
    $expenses = $expenses ?? [];
    $filters = $filters ?? [];
    $departments = $departments ?? [];
    $categories = $categories ?? [];

    // ตั้งค่า Default Filters
    $defaultFilters = [
        'search' => '',
        'dept_id' => 0,
        'cat_id' => 0,
        'date_type' => 'approved',
        'start_date' => '',
        'end_date' => '',
        'min_amount' => '',
        'max_amount' => '',
        'year' => 0,
        'limit' => 10 // เพิ่ม limit เข้าไปใน filter ด้วย
    ];
    $filters = array_merge($defaultFilters, $filters);

    // 🟢 2. สร้าง String สำหรับ hx-include (รวมทุกตัวกรองเพื่อให้ส่งค่าไปด้วยตอนเปลี่ยนหน้า)
    // จำเป็นมากสำหรับหน้านี้เพราะตัวกรองเยอะ
    $hx_selectors = "[name='search'], [name='dept_id'], [name='cat_id'], [name='date_type'], [name='start_date'], [name='end_date'], [name='min_amount'], [name='max_amount'], [name='year']";
?>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-<?= $color ?>-100 mb-6 animate-fade-in">
        <form hx-get="index.php?page=dashboard&tab=expense"
            hx-target="#tab-content"
            hx-push-url="true"
            class="w-full">

            <?php
            // เช็คสิทธิ์ Admin
            $isHighAdmin = (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin');
            ?>

            <div class="flex flex-wrap md:flex-nowrap gap-3 items-end">

                <div class="w-full md:w-[10%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $color; ?>">
                        <select name="year" onchange="this.form.requestSubmit()" class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 cursor-pointer focus:ring-0">
                            <option value="0">ทุกปีงบฯ</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($filters['year'] == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="w-full md:flex-1 min-w-[200px] flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">คำค้นหา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <div class="pl-2 pr-1 text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </div>
                        <input type="text" name="search"
                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                            class="w-full h-[38px] border-none text-xs text-gray-700 focus:ring-0 bg-transparent placeholder-gray-400 leading-tight"
                            placeholder="ชื่อ/รายละเอียด"
                            hx-get="index.php?page=dashboard&tab=expense"
                            hx-target="#tab-content"
                            hx-trigger="keyup changed delay:500ms search">
                    </div>
                </div>

                <?php if ($isHighAdmin): ?>
                    <div class="w-full md:w-[15%] flex-shrink-0 flex flex-col justify-end">
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา</label>
                        <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                            <select name="dept_id" onchange="this.form.requestSubmit()" class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
                                <option value="0">--ทุกภาควิชา--</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($filters['dept_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['thai_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="w-full md:w-[22%] flex-shrink-0 flex flex-col justify-end">
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="block text-xs font-bold text-gray-700">ช่วงวันที่</label>
                        <div class="relative">
                            <select name="date_type" onchange="this.form.requestSubmit()" class="appearance-none bg-<?= $color ?>-50 hover:bg-<?= $color ?>-100 border border-<?= $color ?>-200 text-<?= $color ?>-700 text-[10px] font-bold rounded px-2 py-0.5 pr-2 cursor-pointer focus:outline-none transition">
                                <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>วันที่อนุมัติ</option>
                                <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>></option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm h-[38px] focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="date" name="start_date" value="<?php echo $filters['start_date']; ?>" class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent" onchange="this.form.requestSubmit()">
                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 h-full flex items-center text-[10px] text-gray-500 font-medium">ถึง</div>
                        <input type="date" name="end_date" value="<?php echo $filters['end_date']; ?>" class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent" onchange="this.form.requestSubmit()">
                    </div>
                </div>

                <div class="w-full md:w-[12%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">หมวดหมู่</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <select name="cat_id" onchange="this.form.requestSubmit()" class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
                            <option value="0">--ทุกหมวด--</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($filters['cat_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['name_th']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="w-full md:w-[18%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">เงิน (บาท)</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm h-[38px] focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="hidden" name="min_amount" id="min_amount_hidden" value="<?php echo $filters['min_amount']; ?>">

                        <input type="text" inputmode="decimal" placeholder="Min"
                            value="<?php echo ($filters['min_amount'] !== '') ? number_format((float)$filters['min_amount']) : ''; ?>"
                            class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'min_amount_hidden')">

                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 h-full flex items-center text-gray-400 text-xs">-</div>

                        <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max_amount']; ?>">

                        <input type="text" inputmode="decimal" placeholder="Max"
                            value="<?php echo ($filters['max_amount'] !== '') ? number_format((float)$filters['max_amount']) : ''; ?>"
                            class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'max_amount_hidden')">
                    </div>
                </div>

                <div class="w-full md:w-auto flex-shrink-0 flex items-center gap-2">
                    <button type="submit" class="w-full md:w-[40px] bg-<?= $color ?>-600 hover:bg-<?= $color ?>-700 text-white rounded-lg text-sm font-medium transition shadow-sm flex justify-center items-center h-[38px]">
                        <i class="fas fa-search"></i>
                    </button>

                    <button hx-get="index.php?page=dashboard&tab=expense"
                        hx-target="#tab-content"
                        type="button"
                        class="flex-none w-full md:w-[38px] h-[38px] bg-white text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition flex items-center justify-center border border-gray-200"
                        title="ล้างค่าทั้งหมด">
                        <i class="fas fa-sync-alt text-xs"></i>
                    </button>
                </div>

                <?php if ($pagination): ?>
                    <input type="hidden" name="limit" value="<?php echo $pagination['limit']; ?>">
                <?php endif; ?>

            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-<?= $color ?>-200 flex flex-col min-h-0 overflow-hidden">
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
                                    <div class="text-[10px] text-gray-400">เวลา: <?php echo date('H:i', strtotime($row['created_at'])); ?> น.</div>
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

                                        <button type="button"
                                            onclick="openDeleteModal(<?php echo $row['id']; ?>, 'delete_target_id')"
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
        if (function_exists('renderPaginationBar')) {
            renderPaginationBar(
                $pagination,       // ข้อมูล Pagination
                'expense',         // ชื่อ Tab (tab=expense)
                $hx_selectors,     // ตัวกรองที่จะส่งไปด้วย (hx-include)
                $color             // ธีมสี (purple)
            );
        }
        ?>
    </div>
    <?php
    if (function_exists('renderDeleteModal')) {
        renderDeleteModal(
            "index.php?page=dashboard",
            "delete_expense",
            "delete_target_id",
            0,
            ""
        );
    }
    ?>
<?php
}
