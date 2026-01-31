<?php

/**
 * Component สำหรับแสดงตาราง Expense พร้อมตัวกรอง (Filter)
 * * @param array $expenses ข้อมูลรายการที่ query มาได้
 * @param array $filters ค่าที่ user กรอกมาเพื่อคงสถานะ input (search, dates, etc.)
 * @param array $departments รายชื่อแผนกสำหรับ dropdown
 * @param array $categories รายชื่อหมวดหมู่สำหรับ dropdown
 */
function renderExpenseTableComponent($expenses, $filters, $departments, $categories, $years = [], $color = 'purple')
{
    // ป้องกัน Error กรณีตัวแปร array ว่าง
    $expenses = $expenses ?? [];
    $filters = $filters ?? [];
    $departments = $departments ?? [];
    $categories = $categories ?? [];


    // ตั้งค่า Default ให้ filters เพื่อป้องกัน Undefined Index Warning
    $defaultFilters = [
        'search' => '',
        'dept_id' => 0,
        'cat_id' => 0,
        'date_type' => 'approved',
        'start_date' => '',
        'end_date' => '',
        'min_amount' => '',
        'max_amount' => '',
        'year' =>  0
    ];
    $filters = array_merge($defaultFilters, $filters);
?>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-purple-100 mb-6 ">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="dashboard">
            <input type="hidden" name="tab" value="expense">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                <div class="md:col-span-1 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $color; ?>">
                        <select name="year" class="w-full border-none text-xs text-gray-700 py-2 pl-2 cursor-pointer focus:ring-0">
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                            class="w-full border-none text-xs text-gray-700 py-2 focus:ring-0 bg-transparent placeholder-gray-400 leading-tight"
                            placeholder="ชื่อ/รายละเอียด">
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <div class="pl-2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <select name="dept_id" class="w-full border-none text-xs text-gray-700 py-2 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
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
                            <select name="date_type" class="appearance-none bg-<?= $color ?>-50 hover:bg-<?= $color ?>-100 border border-<?= $color ?>-200 text-<?= $color ?>-700 text-[10px] font-bold rounded px-2 py-0.5 pr-5 cursor-pointer focus:outline-none transition">
                                <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>เอกสาร</option>
                                <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>>วันคีย์</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="date" name="start_date" value="<?php echo $filters['start_date']; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent">
                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-[10px] text-gray-500 font-medium">ถึง</div>
                        <input type="date" name="end_date" value="<?php echo $filters['end_date']; ?>" class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent">
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">หมวดหมู่</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <div class="pl-2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <select name="cat_id" class="w-full border-none text-xs text-gray-700 py-2 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
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
                        <input type="hidden" name="min_amount" id="min_amount_hidden"
                            value="<?php echo $filters['min_amount']; ?>">

                        <input type="text" inputmode="decimal" placeholder="Min"
                            value="<?php echo ($filters['min_amount'] !== '') ? number_format((float)$filters['min_amount']) : ''; ?>"
                            class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'min_amount_hidden')">

                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>

                        <input type="hidden" name="max_amount" id="max_amount_hidden"
                            value="<?php echo $filters['max_amount']; ?>">
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <a href="index.php?page=dashboard&tab=expense"
                            class="flex-none w-[38px] h-[38px] bg-white  text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition flex items-center justify-center "
                            title="ล้างค่าทั้งหมด">
                            <i class="fas fa-sync-alt text-xs"></i>
                        </a>
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
                        <th class="px-6 py-4 font-bold ">จำนวนเงิน (บาท)</th>
                        <th class="px-6 py-4 font-bold text-center w-20">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 ">
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="7" class="p-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
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
                                    <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-<?= $color ?>-100 text-<?= $color ?>-700">
                                        <?php echo $row['category_name'] ? $row['category_name'] : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php echo $row['description']; ?>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-red-600 text-lg whitespace-nowrap">
                                    <?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button"
                                        onclick="openDeleteModal(<?php echo $row['id']; ?>,'delete_target_id')"
                                        class="text-red-600 p-2 rounded-full "
                                        title="ลบรายการนี้">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>

                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        // ตรวจสอบว่า function renderDeleteModal มีอยู่จริงก่อนเรียกใช้
                        if (function_exists('renderDeleteModal')) {
                            renderDeleteModal(
                                "index.php?page=dashboard",  // action
                                "delete_expense",            // value (action name)
                                "delete_target_id",           // id ของ hidden input
                                $row['id'],
                                $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name']
                            );
                        }
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}


function submitDeleteExpense($conn)
{
    // 1. รับค่า ID
    $expense_id = isset($_POST['delete_target_id']) ? intval($_POST['delete_target_id']) : 0;
    $name = isset($_POST['delete_approval_id']) ? intval($_POST['delete_approval_id']) : '';

    // ดึง User ID คนทำรายการ (Actor)
    $actor_id = $_SESSION['user_id'];

    if ($expense_id > 0) {

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่ามาก่อน (เพื่อเอาไปเขียน Description ใน Log)
        // ---------------------------------------------------------
        $sql_check = "SELECT description, amount FROM budget_expenses WHERE id = $expense_id";
        $res_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($res_check);

        $log_desc = "ลบรายการรายจ่าย ID: $expense_id"; // default description
        if ($old_data) {
            // ถ้าเจอข้อมูล ให้ระบุรายละเอียดให้ชัดเจน
            $log_desc = "ลบรายการ: " . $old_data['description'] . " (จำนวน " . number_format($old_data['amount']) . " บาท)";
        }

        // ---------------------------------------------------------
        // ✅ Step 2: ทำการลบ (แนะนำเป็น Soft Delete)
        // ---------------------------------------------------------
        // เปลี่ยนจาก DELETE เป็น UPDATE deleted_at
        $sql = "UPDATE budget_expenses SET deleted_at = NOW() WHERE id = $expense_id";

        // *หมายเหตุ: ถ้าคุณยังอยากใช้ Hard Delete (ลบถาวร) ให้ใช้บรรทัดล่างนี้แทนครับ
        // $sql = "DELETE FROM budget_expenses WHERE id = $expense_id";

        if (mysqli_query($conn, $sql)) {

            // ---------------------------------------------------------
            // ✅ Step 3: บันทึก Log (เมื่อลบสำเร็จแล้ว)
            // ---------------------------------------------------------
            // เรียกใช้ฟังก์ชัน saveActivityLog (หรือชื่อที่คุณตั้งไว้)
            // saveActivityLog($conn, $actor_id, $action_type, $description, $target_id);

            logActivity($conn, $actor_id, $expense_id, 'delete_expense', $log_desc, $expense_id);

            // ---------------------------------------------------------
            // ✅ Step 4: Redirect กลับ
            // ---------------------------------------------------------
            $more_details = "ลบข้อมูลของ $name \n";
            $toastMsg = $more_details . 'รายละเอียด: ' . $log_desc;
            header("Location: index.php?page=dashboard&tab=expense&status=deleted&toastMsg=" . urlencode($toastMsg));
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
            exit();
        }
    }
}
// การนำไปใช้
// if (isset($_POST['action']) && $_POST['action'] == 'delete_expense'){
//     submitDeleteExpense($conn);
// }
