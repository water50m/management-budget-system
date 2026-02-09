<?php


include_once __DIR__ . '/language.php';
include_once __DIR__ . "/../../includes/userRoleManageFunction.php";
include_once __DIR__ . "/../../includes/saveLogFunction.php";

include_once __DIR__ . '/../../includes/text_box_alert.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../includes/add_new_profile.php';
include_once __DIR__ . '/../../includes/delete_user_modal.php';
include_once __DIR__ . '/../../includes/add_expense_modal.php';
include_once __DIR__ . "/../dashboard/modal_add_budget.php";



$role = $_SESSION['role'];
$title = $user_info['prefix'] . ' ' . $user_info['first_name'];

// ตั้ง Timezone ให้ชัวร์ก่อน
date_default_timezone_set('Asia/Bangkok');

$current_month = date('n'); // เดือนปัจจุบัน (1-12)
$current_year = date('Y');  // ปี ค.ศ. ปัจจุบัน

// Logic: ถ้าเดือน >= 10 (ต.ค., พ.ย., ธ.ค.) ให้บวกปี ค.ศ. เพิ่ม 1
if ($current_month >= 10) {
    $fiscal_year_ad = $current_year + 1;
} else {
    $fiscal_year_ad = $current_year;
}

// แปลงเป็น พ.ศ. (+543)
$fiscal_year_th = $fiscal_year_ad + 543;
$uid = $user_info['id'];


?>
<div class="container mx-auto px-4 py-6 max-w-[1800px] ">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        <div class="lg:col-span-1 flex flex-col gap-4 min-h-[256px]">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center flex flex-col h-full relative">

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>
                    <button type="button"
                        class="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition-colors duration-200"
                        title="ลบข้อมูล"
                        onclick="openDeleteUserModal(<?php echo $user_info['id']; ?>, '<?php echo htmlspecialchars($user_info['prefix'] . $user_info['first_name'] . ' ' . $user_info['last_name']); ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                <?php endif; ?>

                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center text-3xl text-blue-600 border-2 border-blue-100 mx-auto mb-3">
                    <i class="fas fa-user"></i>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-1">
                    <?php echo $user_info['prefix'] . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']; ?>
                </h3>

                <?php include_once __DIR__ . '/../../includes/html_change_dep.php'; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label class="text-xs text-gray-400 font-bold uppercase block mb-1"><?php echo $t['role_level']; ?></label>
                        <div class="flex items-center gap-1 justify-center">
                            <input type="hidden" name="current_page" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                            <?php renderUserRoleManageComponent($user_info, $conn) ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-4 text-white relative overflow-hidden flex items-center justify-between">

                <div class="absolute right-[-10px] top-[-20px] opacity-20 pointer-events-none rotate-12">
                    <i class="fas fa-wallet text-7xl"></i>
                </div>

                <div class="z-10">
                    <p class="text-blue-100 text-[10px] font-bold uppercase tracking-wider mb-0.5 opacity-80"><?php echo $t['net_balance']; ?></p>
                    <div class="flex items-baseline gap-1">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] != 'user'): ?>
                            <h3 class="text-2xl font-bold leading-none tracking-tight">
                                <?php echo number_format($user_info['remaining_balance'], 2); ?>
                            </h3>
                            <span class="text-[10px] font-light opacity-70"><?php echo $t['currency']; ?></span>
                        <?php else: ?>
                            <h3 class="text-2xl font-bold leading-none tracking-tight">
                                <?php echo number_format($user_info['remaining_balance'], 2); ?>
                            </h3>
                            <span class="text-[10px] font-light opacity-70"><?php echo $t['currency']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="z-10 pl-4 border-l border-blue-400/30">
                    <a hx-get="index.php?page=profile&page=profile&id=<?= $user_info['id'] ?>&search=&year=0&cat=0&type=all&min_amount=&max_amount=$total_balance=<?= $fiscal_year_th ?>"
                        hx-target="#txn-table-container"
                        hx-swap="outerHTML"
                        hx-select="#txn-table-container"
                        hx-push-url="true"
                        class="group flex flex-col items-center justify-center text-xs font-bold text-white hover:text-blue-100 transition-colors duration-200 cursor-pointer"
                        title="ดูรายการทั้งหมด">
                        <div class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center mb-1 backdrop-blur-sm transition-all">
                            <i class="fas fa-chevron-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                        </div>
                        <span class="text-[9px] opacity-80">ดูรายการ</span>
                    </a>
                </div>

            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 space-y-3 mt-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold"><?php echo $t['total_received']; ?></p>
                            <p class="font-bold text-gray-800"><?php echo number_format($user_info['total_received_all'], 2); ?></p>
                        </div>
                    </div>
                    <a hx-get="index.php?page=profile&page=profile&id=<?= $user_info['id'] ?>&search=&year=<?= $fiscal_year_th ?>&cat=0&type=income&min_amount=&max_amount=&prevYear=<?= $fiscal_year_th - 1 ?>"
                        hx-target="#txn-table-container"
                        hx-swap="outerHTML"
                        hx-select="#txn-table-container"
                        hx-push-url="true"
                        class="px-2 py-1 text-xs font-bold text-green-600 bg-green-50 hover:bg-green-100 rounded transition duration-200 whitespace-nowrap cursor-pointer">
                        ดูรายการ <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="border-t border-gray-100"></div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-xs">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold"><?php echo $t['used_this_year']; ?></p>
                            <p class="font-bold text-gray-800"><?php echo number_format($user_info['total_spent_this_year'], 2); ?></p>
                        </div>
                    </div>
                    <a hx-get="index.php?page=profile&id=<?= $user_info['id'] ?>&search=&year=<?= $fiscal_year_th ?>&cat=0&type=expense&min_amount=&max_amount="
                        hx-target="#txn-table-container"
                        hx-swap="outerHTML"
                        hx-select="#txn-table-container"
                        hx-push-url="true"
                        class="px-2 py-1 text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 rounded transition duration-200 whitespace-nowrap cursor-pointer">
                        ดูรายการ <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="border-t border-gray-100"></div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xs">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="flex flex-col">
                            <p class="text-[10px] text-gray-400 uppercase font-bold leading-tight"><?php echo $t['carried_over']; ?></p>

                            <?php
                            // --- 1. เตรียมตัวแปรและคำนวณ ---
                            $total_carried = $carry_over_data['carried_over_remaining']; // ยอดยกมาตั้งต้น
                            $used   = isset($carry_over_data['carried_over_used']) ? $carry_over_data['carried_over_used'] : 0;
                            $lapsed = isset($carry_over_data['carried_over_lapsed']) ? $carry_over_data['carried_over_lapsed'] : 0;

                            // คำนวณยอดคงเหลือปัจจุบัน (Total - Used - Lapsed)
                            $current_available = $total_carried - $used - $lapsed;
                            ?>

                            <p class="font-bold text-gray-800 text-sm leading-tight">
                                <?php echo number_format($total_carried, 0); ?>
                            </p>

                            <div class="flex items-center gap-2 mt-0.5 text-[10px] font-medium">

                                <span class="text-red-500 flex items-center gap-1 cursor-help" title="ยอดยกมาที่ใช้ไปแล้วในปีนี้">
                                    <i class="fas fa-minus-circle text-[8px]"></i>ตัด <?php echo number_format($used, 0); ?>
                                </span>

                                <span class="text-gray-300">|</span>

                                <span class="text-purple-500 flex items-center gap-1 cursor-help" title="ยอดยกมาที่หมดอายุ/คืนคลังแล้วในปีนี้">
                                    <i class="fas fa-exclamation-circle text-[8px]"></i>คืน <?php echo number_format($lapsed, 0); ?>
                                </span>

                                <span class="text-gray-300">|</span>

                                <span class="text-blue-600 flex items-center gap-1 font-bold cursor-help" title="ยอดยกมาที่เหลือใช้ได้จริง ณ ปัจจุบัน">
                                    <i class="fas fa-coins text-[8px]"></i>เหลือ <?php echo number_format($current_available, 0); ?>
                                </span>

                            </div>
                        </div>
                    </div>

                    <a hx-get="index.php?page=profile&id=<?= $user_info['id'] ?>&carried_over_remaining=true&cat=0&type=income"
                        hx-target="#txn-table-container"
                        hx-swap="outerHTML"
                        hx-select="#txn-table-container"
                        hx-push-url="true"
                        class="px-2 py-1 text-xs font-bold text-yellow-600 bg-yellow-50 hover:bg-yellow-100 rounded transition duration-200 whitespace-nowrap cursor-pointer">
                        ดูรายการ <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <?php
                // แบบประหยัด session 
                $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                $user_department  = $user_info['department_eng'];
                $admin_check  = 'admin-' . $user_department;

                ?>
                <?php if ($role == $admin_check || $role == 'high-admin'): ?>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                            onclick="openExpenseModal('<?php echo $user_info['id']; ?>', '<?php echo htmlspecialchars($user_info['prefix'] . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']); ?>', <?php echo $user_info['remaining_balance']; ?>)"
                            class="flex flex-col items-center justify-center gap-1 py-3 px-2 rounded-lg border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white hover:border-orange-600 hover:shadow-md transition-all duration-200 group">

                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center group-hover:bg-white/20 group-hover:text-white transition-colors">
                                <i class="fas fa-minus"></i>
                            </div>
                            <span class="text-sm font-bold">เพิ่มรายการตัดยอด</span>
                        </button>

                        <button type="button"
                            onclick="openAddBudgetModal('<?php echo $user_info['id']; ?>', '<?php echo htmlspecialchars($user_info['prefix'] . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']); ?>')"
                            class="flex flex-col items-center justify-center gap-1 py-3 px-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 hover:shadow-md transition-all duration-200 group">

                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-white/20 group-hover:text-white transition-colors">
                                <i class="fas fa-plus"></i>
                            </div>
                            <span class="text-sm font-bold">เพิ่มรายการรับยอด</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lg:col-span-4 rounded-xl flex flex-col ">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-purple-100 mb-6 ">
                <form hx-get="index.php?page=profile"
                    hx-target="#txn-table-container"
                    hx-swap="outerHTML"
                    hx-push-url="true"
                    class="flex flex-wrap items-end gap-2 w-full text-sm justify-between">

                    <input type="hidden" name="page" value="profile">
                    <input type="hidden" name="id" value="<?php echo $_GET['id'] ?? ''; ?>">

                    <div class="w-full md:w-[20%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['search_label'] ?? 'ค้นหา'; ?></label>
                        <div class="relative w-full">
                            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                            <input type="text" name="search"
                                value="<?php echo htmlspecialchars($filters['search']); ?>"
                                placeholder="<?php echo $t['search_placeholder'] ?? 'ระบุคำค้นหา...'; ?>"
                                class="pl-8 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:border-blue-500 shadow-sm transition"

                                hx-get="index.php?page=profile"
                                hx-target="#txn-table-container"
                                hx-swap="outerHTML"
                                hx-trigger="keyup changed delay:500ms">
                        </div>
                    </div>

                    <div class="w-[48%] md:w-[12%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['fiscal_year'] ?? 'ปีงบประมาณ'; ?></label>
                        <select name="year"
                            onchange="htmx.trigger(this.form, 'submit')"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="0" <?php echo ($filters['year'] == 0) ? 'selected' : ''; ?>><?php echo $t['all_years'] ?? 'ทุกปี'; ?></option>
                            <?php foreach ($years_list as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($filters['year'] == $y) ? 'selected' : ''; ?>>
                                    <?php echo $t['year_prefix'] ?? 'ปี'; ?> <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-[48%] md:w-[15%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['category'] ?? 'หมวดหมู่'; ?></label>
                        <select name="cat"
                            onchange="htmx.trigger(this.form, 'submit')"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="0"><?php echo $t['all_categories'] ?? 'ทุกหมวดหมู่'; ?></option>
                            <?php foreach ($cats_list as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($filters['cat'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo $c['name_th']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-[48%] md:w-[15%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['type'] ?? 'ประเภท'; ?></label>
                        <select name="type"
                            onchange="htmx.trigger(this.form, 'submit')"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="all" <?php echo ($filters['type'] == 'all') ? 'selected' : ''; ?>><?php echo $t['all_types'] ?? 'ทั้งหมด'; ?></option>
                            <option value="income" <?php echo ($filters['type'] == 'income') ? 'selected' : ''; ?>><?php echo $t['type_income'] ?? 'รายรับ'; ?></option>
                            <option value="expense" <?php echo ($filters['type'] == 'expense') ? 'selected' : ''; ?>><?php echo $t['type_expense'] ?? 'รายจ่าย'; ?></option>
                        </select>
                    </div>

                    <div class="w-full md:w-[20%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['range_label'] ?? 'ช่วงจำนวนเงิน'; ?></label>
                        <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 <?php echo $focusRing; ?>">
                            <input type="hidden" name="min_amount" id="min_amount_hidden" value="<?php echo $filters['min']; ?>">
                            <input type="text" inputmode="decimal" placeholder="Min"
                                value="<?php echo ($filters['min'] !== '') ? number_format((float)$filters['min']) : ''; ?>"
                                class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                                oninput="formatCurrency(this, 'min_amount_hidden')">

                            <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>

                            <input type="hidden" name="max_amount" id="max_amount_hidden" value="<?php echo $filters['max']; ?>">
                            <input type="text" inputmode="decimal" placeholder="Max"
                                value="<?php echo ($filters['max'] !== '') ? number_format((float)$filters['max']) : ''; ?>"
                                class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                                oninput="formatCurrency(this, 'max_amount_hidden')">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full fit:w-auto mt-2 fit:mt-0 pb-[1px]">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition whitespace-nowrap flex-1 fit:flex-none justify-center h-[39px] flex items-center">
                            <?php echo $t['btn_filter'] ?? 'กรองข้อมูล'; ?>
                        </button>

                        <button type="button"
                            hx-get="index.php?page=profile&id=<?php echo $_GET['id'] ?? ''; ?>&search=&year=<?= $fiscal_year_th ?>&cat=0&type=all&min_amount=&max_amount="
                            hx-target="#app-container"
                            hx-swap="innerHTML"
                            hx-push-url="true"
                            class="text-gray-500 hover:text-red-500 px-3 py-2 border border-transparent hover:bg-gray-100 rounded-lg transition h-[39px] flex items-center border border-gray-200"
                            title="Reset">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </form>
            </div>
            <?php include __DIR__ . '/transactions_table.php'; ?>
        </div>


    </div>
</div>
<script>
    function showDeptActions() {
        const select = document.getElementById('dept_select');
        const originalValue = document.getElementById('original_dept_id').value;
        const actionsDiv = document.getElementById('dept_actions');

        // ถ้าค่าปัจจุบัน ไม่ตรงกับค่าเดิม ให้แสดงปุ่ม
        if (select.value !== originalValue) {
            actionsDiv.classList.remove('hidden');
            select.classList.add('bg-white', 'ring-2', 'ring-blue-100'); // ใส่ effect ให้รู้ว่ากำลังแก้
        } else {
            actionsDiv.classList.add('hidden');
            select.classList.remove('bg-white', 'ring-2', 'ring-blue-100');
        }
    }

    function cancelDeptChange() {
        const select = document.getElementById('dept_select');
        const originalValue = document.getElementById('original_dept_id').value;
        const actionsDiv = document.getElementById('dept_actions');

        // คืนค่าเดิม
        select.value = originalValue;

        // ซ่อนปุ่ม
        actionsDiv.classList.add('hidden');
        select.classList.remove('bg-white', 'ring-2', 'ring-blue-100');
    }
</script>