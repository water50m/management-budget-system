<?php
include_once __DIR__ . '/../../../src/Helper/FE_function.php';

// เรียกใช้ฟังก์ชันแสดงตาราง received
renderReceivedTableComponent(
    $received ?? [],
    $filters ?? [],
    $departments_list ?? [],
    $years_list ?? [],
    'emerald',
    $pagination
);

function renderReceivedTableComponent($received, $filters, $departments, $years = [], $theme = 'emerald', $pagination = null)
{
    // 1. ตั้งค่า Default
    $received = $received ?? [];
    $filters = array_merge([
        'search' => '',
        'dept_id' => 0,
        'date_type' => 'approved',
        'start_date' => '',
        'end_date' => '',
        'min_amount' => '',
        'max_amount' => '',
        'year' =>  0
    ], $filters ?? []);
    // 2. ตั้งค่า Theme สี (Default: Emerald สีเขียวเงินเข้า)
    $bgLight = "bg-{$theme}-50";
    $textDark = "text-{$theme}-700";
    $borderBase = "border-{$theme}-200";
    $focusRing = "focus-within:ring-{$theme}-500 focus-within:border-{$theme}-500";
    $btnBg = "bg-{$theme}-600";
    $btnHover = "hover:bg-{$theme}-700";
    $textAmount = "text-{$theme}-600"; // สีตัวเลขเงิน
?>

    <div class="bg-white p-5 rounded-xl shadow-sm border <?php echo $borderBase; ?> mb-6">
        
        <form hx-get="index.php?page=dashboard&tab=received" 
              hx-target="#tab-content"
              hx-push-url="true"
              class="w-full">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                <div class="md:col-span-1 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <select name="year" onchange="this.form.requestSubmit()" class="w-full border-none text-xs text-gray-700 py-2 pl-2 cursor-pointer focus:ring-0">
                            <option value="0">ทุกปีงบฯ</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($filters['year'] == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-3 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">คำค้นหา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <div class="pl-2 pr-1 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                            class="w-full border-none text-xs text-gray-700 py-2 focus:ring-0"
                            placeholder="ชื่อผู้ขอ / รายละเอียด"
                            hx-trigger="keyup changed delay:500ms search"> </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <select name="dept_id" onchange="this.form.requestSubmit()" class="w-full border-none text-xs text-gray-700 py-2 pl-2 cursor-pointer focus:ring-0">
                            <option value="0">--ทุกภาควิชา--</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($filters['dept_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['thai_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-3 flex flex-col justify-end">
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="block text-xs font-bold text-gray-700">ช่วงวันที่</label>
                        <select name="date_type" onchange="this.form.requestSubmit()" class="appearance-none <?php echo $bgLight; ?> <?php echo $textDark; ?> text-[10px] font-bold rounded px-2 py-0.5 cursor-pointer focus:outline-none">
                            <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>วันที่อนุมัติ</option>
                            <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>>วันที่คีย์</option>
                        </select>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <input type="date" name="start_date" value="<?php echo $filters['start_date']; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0" onchange="this.form.requestSubmit()">
                        <div class="bg-gray-100 px-2 py-2 text-[10px] text-gray-500">ถึง</div>
                        <input type="date" name="end_date" value="<?php echo $filters['end_date']; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0" onchange="this.form.requestSubmit()">
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ยอดเงิน (บาท)</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 <?php echo $focusRing; ?>">
                         <input type="hidden" name="min_amount" id="min_amount_hidden" value="<?php echo $filters['min_amount']; ?>">
                         <input type="text" inputmode="decimal" placeholder="Min" value="<?php echo ($filters['min_amount'] !== '') ? number_format((float)$filters['min_amount']) : ''; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent" oninput="formatCurrency(this, 'min_amount_hidden')">
                         <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-gray-400">-</div>
                         <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max_amount']; ?>">
                         <input type="text" inputmode="decimal" placeholder="Max" value="<?php echo ($filters['max_amount'] !== '') ? number_format((float)$filters['max_amount']) : ''; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent" oninput="formatCurrency(this, 'max_amount_hidden')">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full <?php echo "$btnBg $btnHover"; ?> text-white py-2 rounded-md h-[38px] flex items-center justify-center shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    
                    <button type="button" 
                            hx-get="index.php?page=dashboard&tab=received" 
                            hx-target="#tab-content"
                            class="flex-none w-[38px] h-[38px] bg-white text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition flex items-center justify-center border border-gray-200" 
                            title="Reset">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

            </div>
            
            <?php if ($pagination): ?>
                <input type="hidden" name="limit" value="<?php echo $pagination['limit']; ?>">
            <?php endif; ?>

        </form>
    </div>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border <?php echo $borderBase; ?> flex flex-col min-h-0 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 z-10 <?php echo $bgLight; ?> <?php echo $textDark; ?> border-b <?php echo $borderBase; ?>">
    <tr> <th class="px-6 py-4 font-bold text-center w-16">
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
            <div class="text-[10px] font-normal mt-1 text-gray-500 whitespace-nowrap">
                รวม <b><?php echo number_format($pagination['total_rows']); ?></b>
            </div>
        </th>
        
        <th class="px-6 py-4 font-bold whitespace-nowrap">วันที่อนุมัติ</th>
        <th class="px-6 py-4 font-bold">ผู้ขออนุมัติ</th>
        <th class="px-6 py-4 font-bold">รายละเอียด</th>
        <th class="px-6 py-4 font-bold">ยอดอนุมัติ (บาท)</th>
        <th class="px-6 py-4 font-bold text-center w-20">จัดการ</th>
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
                                    <div class="text-[10px] text-gray-400">เวลา: <?php echo date('H:i', strtotime($row['record_date'])); ?> น.</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800">
                                        <?php echo $row['prefix'] . $row['first_name'] . ' ' . $row['last_name']; ?>
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
                                    <?php
                                    // เช็คว่ามีการใช้งานไปแล้วหรือยัง?
                                    $isUsed = (isset($row['total_used']) && $row['total_used'] > 0);
                                    ?>

                                    <?php if ($isUsed): ?>
                                        <button type="button" disabled
                                            onmouseenter="showGlobalAlert('⚠️ ไม่สามารถลบได้: งบประมาณบางส่วน หรือทั้งหมดถูกใช้ไปแล้ว')"
                                            onmouseleave="hideGlobalAlert()"
                                            class="text-gray-300 cursor-not-allowed p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"

                                            onclick="openDeleteModal(<?php echo $row['id']; ?>, 'delete_received_id')"
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition duration-150"
                                            title="ลบรายการนี้">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        if (function_exists('renderDeleteModal')) {
                            renderDeleteModal(
                                "index.php?page=dashboard",
                                "delete_budget",
                                "delete_received_id",
                                $row['id'],
                                $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name']
                            );
                        }
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $hx_selectors = "[name='search'], [name='dept_id'], [name='date_type'], [name='start_date'], [name='end_date'], [name='min_amount'], [name='max_amount'], [name='year']";


        if (function_exists('renderPaginationBar')) {
            renderPaginationBar(
                $pagination,       // ข้อมูล Pagination
                'received',         // ชื่อ Tab (tab=expense)
                $hx_selectors,     // ตัวกรองที่จะส่งไปด้วย (hx-include)
                $theme              // ธีมสี (purple)
            );
        }
        ?>
    </div>
<?php
}
?>