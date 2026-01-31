<?php
require_once __DIR__ . '/../../includes/userRoleManageFunction.php';
$role = $_SESSION['role'];
$title = $user_info['prefix'] . ' ' . $user_info['first_name'];
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/language.php';



?>


    <nav class="bg-white shadow-sm px-6 py-3 flex justify-between items-center sticky top-0 z-50">
        <div class="font-bold text-xl text-blue-800 flex items-center gap-2"><i class="fas fa-seedling"></i> Mali Project</div>
        <div class="flex items-center gap-4">
            <a href="index.php?page=dashboard" class="text-gray-600 hover:text-blue-600 font-medium"><i class="fas fa-home"></i> <?php echo $t['home']; ?></a>
            <a href="index.php?page=logout" class="text-red-500 hover:text-red-700 border border-red-200 px-4 rounded-full text-sm py-1 font-semibold transition hover:bg-red-50"><?php echo $t['logout']; ?></a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6 max-w-[1800px]">

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            <div class="lg:col-span-1 flex flex-col gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center text-3xl text-blue-600 border-2 border-blue-100 mx-auto mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="font-bold text-lg text-gray-800 leading-tight">
                        <?php echo $user_info['prefix'] . $user_info['first_name']; ?><br><?php echo $user_info['last_name']; ?>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1"><?php echo $user_info['position']; ?></p>
                    <div class="mt-2 inline-block bg-gray-100 px-3 py-1 rounded-full text-xs font-semibold text-gray-600">
                        <?php echo $user_info['department_name']; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label class="text-xs text-gray-400 font-bold uppercase block mb-1"><?php echo $t['role_level']; ?></label>
                        <div class="flex items-center gap-1 justify-center">
                            <input type="hidden" name="current_page" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                            <?php renderUserRoleManageComponent($user_info, $role, $conn) ?>

                        </div>
                        <div class="flex items-center gap-1 justify-center mt-2">

                            <button type="button" 
                                            class="text-red-500 hover:text-red-700" 
                                            onclick="openDeleteUserModal(<?php echo $user_info['id']; ?>, '<?php echo htmlspecialchars($user_info['prefix'] . $user_info['first_name'] . ' ' . $user_info['last_name']); ?>')">
                                        <i class="fas fa-trash"></i> ลบข้อมูล
                            </button>
                        </div>
                    </div>
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
            </div>

            <div class="lg:col-span-4 rounded-xl flex flex-col h-fit">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-purple-100 mb-6 ">
                    <form method="GET" action="index.php" class="flex flex-wrap items-end gap-2 w-full text-sm justify-between">
                        <input type="hidden" name="page" value="profile">
                        <input type="hidden" name="id" value="<?php echo $_GET['id'] ?? ''; ?>">

                        <div class="w-full md:w-[20%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['search_label']; ?></label>
                            <div class="relative w-full">
                                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="<?php echo $t['search_placeholder']; ?>"
                                    class="pl-8 pr-3 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:border-blue-500 shadow-sm transition">
                            </div>
                        </div>

                        <div class="w-[48%] md:w-[12%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['fiscal_year']; ?></label>
                            <select name="year" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                                <option value="0" <?php echo ($filters['year'] == 0) ? 'selected' : ''; ?>><?php echo $t['all_years']; ?></option>
                                <?php foreach ($years_list as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($filters['year'] == $y) ? 'selected' : ''; ?>>
                                        <?php echo $t['year_prefix']; ?> <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="w-[48%] md:w-[15%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['category']; ?></label>
                            <select name="cat" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                                <option value="0"><?php echo $t['all_categories']; ?></option>
                                <?php foreach ($cats_list as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($filters['cat'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo $c['name_th']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="w-[48%] md:w-[15%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['type']; ?></label>
                            <select name="type" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 bg-white shadow-sm cursor-pointer">
                                <option value="all" <?php echo ($filters['type'] == 'all') ? 'selected' : ''; ?>><?php echo $t['all_types']; ?></option>
                                <option value="income" <?php echo ($filters['type'] == 'income') ? 'selected' : ''; ?>><?php echo $t['type_income']; ?></option>
                                <option value="expense" <?php echo ($filters['type'] == 'expense') ? 'selected' : ''; ?>><?php echo $t['type_expense']; ?></option>
                            </select>
                        </div>

                        <div class="w-full md:w-[20%]">
                            <label class="block text-xs font-bold text-gray-700 mb-1"><?php echo $t['range_label']; ?></label>
                            <div class="flex items-center bg-white border border-gray-300 rounded-lg overflow-hidden shadow-sm w-full ">
                                <input type="number" name="min_amount" value="<?php echo $filters['min']; ?>" placeholder="Min" class="w-1/2 py-2 px-3 outline-none border-r text-center ">
                                <input type="number" name="max_amount" value="<?php echo $filters['max']; ?>" placeholder="Max" class="w-1/2 py-2 px-3 outline-none text-center ">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full fit:w-auto mt-2 fit:mt-0 pb-[1px]">

                            <button type="submit" class=" bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow transition whitespace-nowrap flex-1 fit:flex-none justify-center h-[39px] flex items-center">
                                <?php echo $t['btn_filter']; ?>
                            </button>

                            <a href="index.php?page=profile&id=<?php echo $_GET['id'] ?? ''; ?>" class="text-gray-500 hover:text-red-500 px-3 py-2 border border-transparent hover:bg-gray-100 rounded-lg transition h-[39px] flex items-center" title="Reset">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                    </form>

                </div>
                <div class="bg-white rounded-xl shadow-lg border flex flex-col min-h-0 overflow-hidden">
                    <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
                        <table class="w-full text-sm  text-left">
                            <thead class="bg-white border-b border-gray-200 text-gray-500 font-semibold text-sm sticky top-0 shadow-sm z-10">
                                <tr>
                                    <th class="px-6 py-4 w-20 text-center"><?php echo $t['th_seq']; ?></th>
                                    <th class="px-6 py-4 w-32"><?php echo $t['th_date']; ?></th>
                                    <th class="px-6 py-4"><?php echo $t['th_desc']; ?></th>
                                    <th class="px-6 py-4 w-48 text-center"><?php echo $t['th_cat']; ?></th>
                                    <th class="px-6 py-4 w-40 text-right"><?php echo $t['th_amount']; ?></th>
                                    <th class="px-6 py-4 w-28 text-center"><?php echo $t['th_type']; ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-base">
                                <?php if (count($transactions) > 0): ?>
                                    <?php $index = 0; ?>
                                    <?php foreach ($transactions as $txn): ?>
                                        <?php
                                        $row_bg = "bg-white";
                                        $fy_badge_color = "bg-gray-100 text-gray-500";
                                        if ($txn['type'] == 'expense') {
                                            $row_bg = "bg-red-50 hover:bg-red-100";
                                        } else {
                                            if ($txn['fiscal_year_num'] == $current_fiscal_year) {
                                                $row_bg = "bg-green-50 hover:bg-green-100";
                                                $fy_badge_color = "bg-green-100 text-green-700";
                                            } elseif ($txn['fiscal_year_num'] == ($current_fiscal_year - 1)) {
                                                $row_bg = "bg-yellow-50 hover:bg-yellow-100";
                                                $fy_badge_color = "bg-yellow-100 text-yellow-700";
                                            } else {
                                                $row_bg = "bg-gray-50/40 hover:bg-gray-100";
                                            }
                                        }
                                        ?>
                                        <tr class="<?php echo $row_bg; ?> transition group">
                                            <td class="px-6 py-4 text-center text-gray-400 font-mono text-sm">
                                                <?php $index += 1; ?>
                                                <?php echo $index; ?>
                                            </td>
                                            <td class="px-6 py-4 text-gray-500 font-mono text-sm"><?php echo date('d/m/Y', strtotime($txn['txn_date'])); ?></td>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-800"><?php echo $txn['description']; ?></div>

                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if (!empty($txn['category_name'])): ?>
                                                    <span class="inline-block bg-white border border-gray-200 text-gray-600 px-3 py-1 rounded-full text-xs shadow-sm"><?php echo $txn['category_name']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-300 text-sm">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono font-medium">
                                                <?php if ($txn['type'] == 'income'): ?>
                                                    <span class="text-green-600 text-lg">+<?php echo number_format($txn['amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-red-500 text-lg"><?php echo number_format($txn['amount'], 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if ($txn['type'] == 'income'): ?>
                                                    <div class="w-8 h-8 mx-auto rounded-full bg-green-100 text-green-600 flex items-center justify-center shadow-sm"><i class="fas fa-arrow-down"></i></div>
                                                <?php else: ?>
                                                    <div class="w-8 h-8 mx-auto rounded-full bg-red-100 text-red-500 flex items-center justify-center shadow-sm"><i class="fas fa-arrow-up"></i></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-400"><?php echo $t['no_data']; ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="10" class="p-0">
                                        <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 gap-8">
                                            <div class="flex items-center justify-center gap-4 flex-1 w-full sm:w-auto">
                                            </div>
                                            <div class="text-sm font-bold text-gray-600 whitespace-nowrap">
                                                <?php echo $t['summary_label']; ?>
                                            </div>


                                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
                                                <span class="text-base  uppercase text-green-600"><?php echo $t['label_income']; ?></span>
                                                <span class=" text-base text-green-700">+<?php echo number_format($sum_income, 2); ?></span>
                                            </div>

                                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
                                                <span class="text-base  uppercase text-red-500"><?php echo $t['label_expense']; ?></span>
                                                <span class=" text-base text-red-600">-<?php echo number_format($sum_expense, 2); ?></span>


                                            </div>

                                            <?php $net_total = $sum_income - $sum_expense; ?>
                                            <div class="flex items-center gap-2">
                                                <span class="text-base text-gray-400  uppercase tracking-wider"><?php echo $t['label_total']; ?></span>
                                                <span class="text-base   <?php echo ($net_total >= 0) ? 'text-blue-700' : 'text-red-600'; ?>">
                                                    <?php echo number_format($net_total, 2); ?>
                                                </span>
                                                <span class="text-base text-gray-400"><?php echo $t['currency_unit']; ?></span>
                                            </div>
                                            <div class="flex items-center justify-center gap-4 flex-1 w-full sm:w-auto">
                                            </div>

                                        </div>

                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>