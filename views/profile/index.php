<?php


include_once __DIR__ . '/language.php';
include_once __DIR__ . "/../../includes/userRoleManageFunction.php";
include_once __DIR__ . "/../../includes/saveLogFunction.php";

include_once __DIR__ . '/../../includes/confirm_delete.php';
include_once __DIR__ . '/../../includes/text_box_alert.php';
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../includes/add_new_profile.php';
include_once __DIR__ . '/../../includes/delete_user_modal.php';
include_once __DIR__ . '/../../includes/add_expense_modal.php';
include_once __DIR__ . "/../dashboard/modal_add_budget.php";

$role = $_SESSION['role'];
$title = $user_info['prefix'] . ' ' . $user_info['first_name'];
?>



<div class="container mx-auto px-4 py-6 max-w-[1800px] ">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        <div class="lg:col-span-1 flex flex-col gap-4 min-h-[256px]">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center flex flex-col h-full">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center text-3xl text-blue-600 border-2 border-blue-100 mx-auto mb-3">
                    <i class="fas fa-user"></i>
                </div>

                <h2 class="font-bold text-lg text-gray-800 leading-tight">
                    <?php echo $user_info['prefix'] . ' ' . $user_info['first_name']; ?><br><?php echo $user_info['last_name']; ?>
                </h2>

                <p class="text-sm text-gray-500 mt-1"><?php echo $user_info['position']; ?></p>

                <div class="mt-2 inline-block bg-gray-100 px-3 py-1 rounded-full text-xs font-semibold text-gray-600">
                    <?php echo $user_info['department_id'] == 5 ? 'สังกัด' : 'สังกัดภาควิชา' ?>
                    <?php echo $user_info['department_name']; ?>
                </div>

                <?php if ($role == 'high-admin'): ?>
                    <div class="mt-4 pt-4 border-t border-gray-100">

                        <label class="text-xs text-gray-400 font-bold uppercase block mb-1"><?php echo $t['role_level']; ?></label>

                        <div class="flex items-center gap-1 justify-center">
                            <input type="hidden" name="current_page" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                            <?php renderUserRoleManageComponent($user_info, $conn) ?>
                        </div>

                        <div class="flex items-center gap-1 justify-center mt-2">
                            <button type="button"
                                class="text-red-500 hover:text-red-700 text-sm"
                                onclick="openDeleteUserModal(<?php echo $user_info['id']; ?>, '<?php echo htmlspecialchars($user_info['prefix'] . $user_info['first_name'] . ' ' . $user_info['last_name']); ?>')">
                                <i class="fas fa-trash"></i> ลบข้อมูล
                            </button>
                        </div>

                    </div>
                <?php endif; ?>

            </div>

            <div class="bg-gradient-to-b from-blue-600 to-indigo-700 rounded-xl shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute right-[-10px] top-[-10px] opacity-20"><i class="fas fa-wallet text-8xl"></i></div>
                <p class="text-blue-100 text-xs font-medium uppercase tracking-wider mb-1"><?php echo $t['net_balance']; ?></p>
                <h3 class="text-3xl font-bold"><?php echo number_format($user_info['remaining_balance'], 2); ?></h3>
                <span class="text-xs font-light opacity-80"><?php echo $t['currency']; ?></span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-arrow-down"></i></div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold"><?php echo $t['total_received']; ?></p>
                            <p class="font-bold text-gray-800"><?php echo number_format($user_info['total_received_all'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-xs"><i class="fas fa-fire"></i></div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold"><?php echo $t['used_this_year']; ?></p>
                            <p class="font-bold text-gray-800"><?php echo number_format($user_info['total_spent_this_year'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xs"><i class="fas fa-history"></i></div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold"><?php echo $t['carried_over']; ?></p>
                            <p class="font-bold text-gray-800"><?php echo number_format($user_info['previous_year_budget'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <?php 
                // แบบประหยัด session 
                $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                $user_department  = $user_info['department_eng'];
                $admin_check  ='admin-'.$user_department;

                ?>
            <?php if ($role == $admin_check || $role == 'high-admin'):?>
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
                                hx-include="closest form"
                                hx-trigger="keyup changed delay:500ms">
                        </div>
                    </div>

                    <div class="w-[48%] md:w-[12%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['fiscal_year'] ?? 'ปีงบประมาณ'; ?></label>
                        <select name="year"
                            onchange="this.form.requestSubmit()"
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
                            onchange="this.form.requestSubmit()"
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
                            onchange="this.form.requestSubmit()"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="all" <?php echo ($filters['type'] == 'all') ? 'selected' : ''; ?>><?php echo $t['all_types'] ?? 'ทั้งหมด'; ?></option>
                            <option value="income" <?php echo ($filters['type'] == 'income') ? 'selected' : ''; ?>><?php echo $t['type_income'] ?? 'รายรับ'; ?></option>
                            <option value="expense" <?php echo ($filters['type'] == 'expense') ? 'selected' : ''; ?>><?php echo $t['type_expense'] ?? 'รายจ่าย'; ?></option>
                        </select>
                    </div>

                    <div class="w-full md:w-[20%]">
                        <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['range_label'] ?? 'ช่วงจำนวนเงิน'; ?></label>
                        <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 <?php echo $focusRing; ?>">
                            <input type="hidden" name="min_amount" id="min_amount_hidden"
                                value="<?php echo $filters['min']; ?>">

                            <input type="text" inputmode="decimal" placeholder="Min"
                                value="<?php echo ($filters['min'] !== '') ? number_format((float)$filters['min']) : ''; ?>"
                                class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                                oninput="formatCurrency(this, 'min_amount_hidden')"></input>

                            <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>

                            <input type="hidden" name="max_amount" id="max_amount_hidden"
                                value="<?php echo $filters['max']; ?>">

                            <input type="text" inputmode="decimal" placeholder="Max"
                                value="<?php echo ($filters['max'] !== '') ? number_format((float)$filters['max']) : ''; ?>"
                                class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent"
                                oninput="formatCurrency(this, 'max_amount_hidden')"></input>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full fit:w-auto mt-2 fit:mt-0 pb-[1px]">

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition whitespace-nowrap flex-1 fit:flex-none justify-center h-[39px] flex items-center">
                            <?php echo $t['btn_filter'] ?? 'กรองข้อมูล'; ?>
                        </button>

                        <button type="button"
                            hx-get="index.php?page=profile&id=<?php echo $_GET['id'] ?? ''; ?>"
                            hx-target="#txn-table-container"
                            hx-swap="outerHTML"
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