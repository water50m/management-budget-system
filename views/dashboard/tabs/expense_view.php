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

// 🟢 1. Add $pagination = null to the last parameter
function renderExpenseTableComponent($expenses, $filters, $departments, $categories, $years = [], $color = 'purple', $pagination = null)
{
    // Prevent Errors
    $expenses = $expenses ?? [];
    $filters = $filters ?? [];
    $departments = $departments ?? [];
    $categories = $categories ?? [];

    // Set Default Filters
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
        'limit' => 10
    ];
    $filters = array_merge($defaultFilters, $filters);

    $year_list_ = getBudgetYears();
    $fiscal_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
?>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-<?= $color ?>-100 mb-6 animate-fade-in">
        <form hx-get="index.php?page=dashboard&tab=expense"
            hx-target="#table-expense"
            hx-push-url="true"
            hx-swap="innerHTML"
            class="w-full"
            id="form-expense">

            <?php
            // Check Admin Permissions
            $isHighAdmin = (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin');
            ?>

            <div class="flex flex-wrap md:flex-nowrap gap-3 items-end">

                <div class="w-full md:w-[10%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ปีงบประมาณ</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm <?php echo $color; ?>">
                        <select name="year"
                            hx-get="index.php?page=dashboard&tab=expense"
                            hx-trigger="change"
                            hx-target="#table-expense"
                            hx-include="#form-expense"
                            class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 cursor-pointer focus:ring-0">

                            <option value="0">ทุกปีงบฯ</option>

                            <?php foreach ($year_list_ as $y):
                                // เช็คเงื่อนไขเก็บไว้ในตัวแปร เพื่อความอ่านง่าย
                                $is_current = ($fiscal_year == $y);
                            ?>
                                <option value="<?php echo $y; ?>"
                                    class="<?php echo $is_current ? "bg-{$color}-50 text-{$color}-800 font-bold cursor-help" : ""; ?>"
                                    title="<?php echo $is_current ? 'ปีงบประมาณปีปัจจุบัน' : ''; ?>"
                                    <?php echo $is_current ? 'selected' : ''; ?>>
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
                            hx-target="#table-expense"
                            hx-include="#form-expense"
                            hx-trigger="keyup changed delay:500ms, search">
                    </div>
                </div>

                <?php if ($isHighAdmin): ?>
                    <div class="w-full md:w-[15%] flex-shrink-0 flex flex-col justify-end">
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">ภาควิชา / สำนักงาน</label>
                        <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                            <select name="dept_id"
                                hx-get="index.php?page=dashboard&tab=expense"
                                hx-trigger="change"
                                hx-target="#table-expense"
                                hx-include="#form-expense"
                                class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
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
                            <select name="date_type"
                                hx-get="index.php?page=dashboard&tab=expense"
                                hx-trigger="change"
                                hx-target="#table-expense"
                                hx-include="#form-expense"
                                class="appearance-none bg-<?= $color ?>-50 hover:bg-<?= $color ?>-100 border border-<?= $color ?>-200 text-<?= $color ?>-700 text-[10px] font-bold rounded px-2 py-0.5 pr-2 cursor-pointer focus:outline-none transition">
                                <option value="approved" <?php echo ($filters['date_type'] == 'approved') ? 'selected' : ''; ?>>วันที่อนุมัติ</option>
                                <option value="created" <?php echo ($filters['date_type'] == 'created') ? 'selected' : ''; ?>>วันที่สร้าง</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm h-[38px] focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <input type="text" name="start_date"
                            value="<?php echo $filters['start_date']; ?>"
                            hx-get="index.php?page=dashboard&tab=expense" hx-trigger="change"
                            hx-target="#table-expense" hx-include="#form-expense"
                            class="flatpickr-thai w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            placeholder="วันเริ่มต้น">

                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 h-full flex items-center text-[10px] text-gray-500 font-medium">ถึง</div>

                        <input type="text" name="end_date"
                            value="<?php echo $filters['end_date']; ?>"
                            hx-get="index.php?page=dashboard&tab=expense" hx-trigger="change"
                            hx-target="#table-expense" hx-include="#form-expense"
                            class="flatpickr-thai w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            placeholder="วันสิ้นสุด">
                    </div>
                </div>

                <div class="w-full md:w-[12%] flex-shrink-0 flex flex-col justify-end">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">หมวดหมู่การใช้เงิน</label>
                    <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-<?= $color ?>-500">
                        <select name="cat_id"
                            hx-get="index.php?page=dashboard&tab=expense"
                            hx-trigger="change"
                            hx-target="#table-expense"
                            hx-include="#form-expense"
                            class="w-full h-[38px] border-none text-xs text-gray-700 pl-2 pr-4 focus:ring-0 bg-transparent cursor-pointer">
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
                            oninput="formatCurrency(this, 'min_amount_hidden')"
                            hx-get="index.php?page=dashboard&tab=expense" hx-trigger="change"
                            hx-target="#table-expense" hx-include="#form-expense">

                        <div class="bg-gray-100 border-l border-r border-gray-200 px-2 h-full flex items-center text-gray-400 text-xs">-</div>

                        <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max_amount']; ?>">

                        <input type="text" inputmode="decimal" placeholder="Max"
                            value="<?php echo ($filters['max_amount'] !== '') ? number_format((float)$filters['max_amount']) : ''; ?>"
                            class="w-1/2 h-full border-none text-xs text-gray-600 px-1 text-center focus:ring-0 bg-transparent"
                            oninput="formatCurrency(this, 'max_amount_hidden')"
                            hx-get="index.php?page=dashboard&tab=expense" hx-trigger="change"
                            hx-target="#table-expense" hx-include="#form-expense">
                    </div>
                </div>

                <div class="w-full md:w-auto flex-shrink-0 flex items-center gap-2">
                    <button type="submit"
                        class="w-full md:w-[40px] bg-<?= $color ?>-600 hover:bg-<?= $color ?>-700 text-white rounded-lg text-sm font-medium transition shadow-sm flex justify-center items-center h-[38px]">
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
            </div>

            <?php if ($pagination): ?>
                <input type="hidden" name="limit" value="<?php echo $pagination['limit']; ?>">
            <?php endif; ?>
        </form>
    </div>

<?php
    include_once __DIR__ . "/../tables/expense_table.php";
}
?>