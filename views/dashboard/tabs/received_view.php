<?php

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
    $border = " border border-{$theme}-200"
?>

    <div class="bg-white p-5 rounded-xl shadow-sm border <?php echo $borderBase; ?> mb-6">

        <form hx-get="index.php?page=dashboard&tab=received"
            hx-target="#table-received"
            hx-push-url="true"
            hx-indicator="#loading-indicator"
            class="w-full"
            id="form-received">
            <div class="flex flex-wrap md:flex-nowrap gap-3 items-end">

                <div class="w-full md:w-[10%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <select name="year"
                            hx-get="index.php?page=dashboard&tab=received"
                            hx-trigger="change"
                            hx-target="#table-received"
                            hx-include="#form-received"
                            class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 cursor-pointer focus:ring-0">
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
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                        <div class="pl-2 pr-1 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                            class="w-full h-[38px] border-none text-xs text-gray-700 focus:ring-0 placeholder-gray-400"
                            placeholder="ชื่อผู้ขอ / รายละเอียด"

                            hx-get="index.php?page=dashboard&tab=received"
                            hx-trigger="keyup changed delay:500ms, search"
                            hx-target="#table-received"
                            hx-include="#form-received">
                    </div>
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>
                    <div class="w-full md:w-[15%] flex-shrink-0 flex flex-col justify-end">
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา / สำนักงาน</label>
                        <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $focusRing; ?>">
                            <select name="dept_id"
                                hx-get="index.php?page=dashboard&tab=received"
                                hx-trigger="change"
                                hx-target="#table-received"
                                hx-include="#form-received"
                                class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 cursor-pointer focus:ring-0">
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
                        <select name="date_type"
                            hx-get="index.php?page=dashboard&tab=received"
                            hx-trigger="change"
                            hx-target="#table-received"
                            hx-include="#form-received"
                            class="appearance-none <?php echo $bgLight; ?> <?php echo $textDark; ?> <?= $border ?> text-[10px] font-bold rounded px-2 py-0.5 cursor-pointer focus:outline-none">
                            <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>วันที่อนุมัติ</option>
                            <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>>วันที่สร้าง</option>
                        </select>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm h-[38px] <?php echo $focusRing; ?>">
                        <input type="text" name="start_date" value="<?php echo $filters['start_date']; ?>"
                            hx-get="index.php?page=dashboard&tab=received" hx-trigger="change"
                            hx-target="#table-received" hx-include="#form-received"
                            class="flatpickr-thai w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0" placeholder="วันที่เริ่มต้น">

                        <div class="bg-gray-100 px-2 h-full flex items-center text-[10px] text-gray-500 border-l border-r border-gray-200">ถึง</div>

                        <input type="text" name="end_date" value="<?php echo $filters['end_date']; ?>"
                            hx-get="index.php?page=dashboard&tab=received" hx-trigger="change"
                            hx-target="#table-received" hx-include="#form-received"
                            class="flatpickr-thai w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0" placeholder="วันที่สิ้นสุด">
                    </div>
                </div>

                <div class="w-full md:w-[18%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ยอดเงิน (บาท)</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm h-[38px] focus-within:ring-1 <?php echo $focusRing; ?>">
                        <input type="hidden" name="min_amount" id="min_amount_hidden" value="<?php echo $filters['min_amount']; ?>">

                        <input type="text" inputmode="decimal" placeholder="Min"
                            value="<?php echo ($filters['min_amount'] !== '') ? number_format((float)$filters['min_amount']) : ''; ?>"
                            class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'min_amount_hidden')"
                            hx-get="index.php?page=dashboard&tab=received" hx-trigger="change"
                            hx-target="#table-received" hx-include="#form-received">

                        <div class="bg-gray-100 px-2 h-full flex items-center text-gray-400 border-l border-r border-gray-200">-</div>

                        <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max_amount']; ?>">
                        <input type="text" inputmode="decimal" placeholder="Max"
                            value="<?php echo ($filters['max_amount'] !== '') ? number_format((float)$filters['max_amount']) : ''; ?>"
                            class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'max_amount_hidden')"
                            hx-get="index.php?page=dashboard&tab=received" hx-trigger="change"
                            hx-target="#table-received" hx-include="#form-received">
                    </div>
                </div>

                <div class="w-full md:w-auto flex-shrink-0 flex items-center gap-2">
                    <button type="submit" class="w-full md:w-[40px] <?php echo "$btnBg $btnHover"; ?> text-white rounded-md h-[38px] flex items-center justify-center shadow-sm transition"
                        hx-get="index.php?page=dashboard&tab=received"
                        hx-target="#table-received"
                        hx-include="#form-received">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <button type="button"
                        hx-get="index.php?page=dashboard&tab=received"
                        hx-target="#tab-content"
                        class="flex-none w-full md:w-[38px] h-[38px] bg-white text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition flex items-center justify-center border border-gray-200"
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
    <?php include_once __DIR__ . "/../tables/received_table.php"; ?>

<?php
}
?>