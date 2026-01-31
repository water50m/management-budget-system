<?php


renderExpenseTableComponent(
    $expenses ?? [],
    $filters ?? [],
    $departments_list ?? [],
    $categories_list ?? [],
    $years_list ?? [],
    'purple'
);

function renderExpenseTableComponent($expenses, $filters, $departments, $categories, $years = [], $color = 'purple')
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
        'year' => 0
    ];
    $filters = array_merge($defaultFilters, $filters);
?>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-<?= $color ?>-100 mb-6 animate-fade-in">
        <form hx-get="index.php?page=dashboard&tab=expense" 
              hx-target="#tab-content" 
              hx-push-url="true"
              class="w-full">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                <div class="md:col-span-1 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $color; ?>">
                        <select name="year" 
                                onchange="this.form.requestSubmit()"
                                class="w-full border-none text-xs text-gray-700 py-2 pl-2 cursor-pointer focus:ring-0">
                            <option value="0">ทุกปีงบฯ</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($filters['year'] == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">คำค้นหา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <div class="pl-2 pr-1 text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </div>
                        <input type="text" name="search" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>"
                               class="w-full border-none text-xs text-gray-700 py-2 focus:ring-0 bg-transparent placeholder-gray-400 leading-tight"
                               placeholder="ชื่อ/รายละเอียด"
                               hx-get="index.php?page=dashboard&tab=expense"
                               hx-target="#tab-content"
                               hx-trigger="keyup changed delay:500ms search">
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <select name="dept_id" 
                                onchange="this.form.requestSubmit()"
                                class="w-full border-none text-xs text-gray-700 py-2 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
                            <option value="0">--ทุกภาควิชา--</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($filters['dept_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['thai_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="block text-xs font-bold text-gray-700">ช่วงวันที่</label>
                        <div class="relative">
                            <select name="date_type" 
                                    onchange="this.form.requestSubmit()"
                                    class="appearance-none bg-<?= $color ?>-50 hover:bg-<?= $color ?>-100 border border-<?= $color ?>-200 text-<?= $color ?>-700 text-[10px] font-bold rounded px-2 py-0.5 pr-5 cursor-pointer focus:outline-none transition">
                                <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>เอกสาร</option>
                                <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>>วันคีย์</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="date" name="start_date" 
                               value="<?php echo $filters['start_date']; ?>" 
                               class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                               onchange="this.form.requestSubmit()"> <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-[10px] text-gray-500 font-medium">ถึง</div>
                        <input type="date" name="end_date" 
                               value="<?php echo $filters['end_date']; ?>" 
                               class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                               onchange="this.form.requestSubmit()">
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">หมวดหมู่</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <select name="cat_id" 
                                onchange="this.form.requestSubmit()"
                                class="w-full border-none text-xs text-gray-700 py-2 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
                            <option value="0">--ทุกหมวด--</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($filters['cat_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['name_th']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">เงิน (บาท)</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="hidden" name="min_amount" id="min_amount_hidden" value="<?php echo $filters['min_amount']; ?>">
                        <input type="text" inputmode="decimal" placeholder="Min"
                            value="<?php echo ($filters['min_amount'] !== '') ? number_format((float)$filters['min_amount']) : ''; ?>"
                            class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'min_amount_hidden')"> <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-gray-400 text-xs">-</div>

                        <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max_amount']; ?>">
                        <input type="text" inputmode="decimal" placeholder="Max"
                            value="<?php echo ($filters['max_amount'] !== '') ? number_format((float)$filters['max_amount']) : ''; ?>"
                            class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'max_amount_hidden')">
                    </div>
                </div>

                <div class="md:col-span-1 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-transparent mb-1 select-none">Action</label>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="flex-1 bg-<?= $color ?>-600 hover:bg-<?= $color ?>-700 text-white rounded-lg text-sm font-medium transition shadow-sm flex justify-center items-center h-[38px]">
                            <i class="fas fa-search"></i>
                        </button>

                        <button hx-get="index.php?page=dashboard&tab=expense"
                            hx-target="#tab-content"
                            type="button"
                            class="flex-none w-[38px] h-[38px] bg-white text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition flex items-center justify-center border border-gray-200"
                            title="ล้างค่าทั้งหมด">
                            <i class="fas fa-sync-alt text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-<?= $color ?>-200 flex flex-col min-h-0 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 z-10 bg-<?= $color ?>-50 text-<?= $color ?>-900 border-b border-<?= $color ?>-100 shadow-sm">
                    <tr>
                        <th class="px-6 py-4 font-bold text-center w-16">#</th>
                        <th class="px-6 py-4 font-bold whitespace-nowrap">วันที่รายการ</th>
                        <th class="px-6 py-4 font-bold">ผู้เบิกจ่าย</th>
                        <th class="px-6 py-4 font-bold whitespace-nowrap text-center">หมวดหมู่</th>
                        <th class="px-6 py-4 font-bold">รายละเอียด</th>
                        <th class="px-6 py-4 font-bold text-right">จำนวนเงิน (บาท)</th>
                        <th class="px-6 py-4 font-bold text-center w-20">จัดการ</th>
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
                                <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>
                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                    <?php echo $row['thai_date']; ?>
                                    <div class="text-[10px] text-gray-400">เวลา: <?php echo date('H:i', strtotime($row['created_at'])); ?> น.</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800"><?php echo $row['prefix'] . $row['first_name'] . ' ' . $row['last_name']; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $row['department']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-<?= $color ?>-100 text-<?= $color ?>-700 border border-<?= $color ?>-200">
                                        <?php echo $row['category_name'] ? $row['category_name'] : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php echo $row['description']; ?>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-red-600 text-lg whitespace-nowrap text-right">
                                    <?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button"
                                            onclick="openDeleteModal(<?php echo $row['id']; ?>, 'delete_target_id')"
                                            class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-full transition"
                                            title="ลบรายการนี้">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php
    // ✅ ย้าย renderDeleteModal ออกมานอก Loop เพื่อไม่ให้ Render ซ้ำๆ
    // สมมติว่า function นี้ render "โครง" ของ Modal เปล่าๆ รอให้ JS มาเติม ID ทีหลัง
    if (function_exists('renderDeleteModal')) {
        renderDeleteModal(
            "index.php?page=dashboard",  // action
            "delete_expense",            // value (action name)
            "delete_target_id",          // id ของ hidden input
            0,                           // default id (JS จะมาแก้ตรงนี้)
            ""                           // default text (JS จะมาแก้ตรงนี้)
        );
    }
    ?>
<?php
}
?>