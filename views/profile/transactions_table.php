<div id="txn-table-container" class="flex-1 bg-white rounded-xl shadow-lg border flex flex-col min-h-0 overflow-hidden h-screen mb-5">
    <div class="flex-1 overflow-x-auto overflow-y-auto flex flex-col min-h-0">
        <table class="w-full text-sm text-left">
            <thead class="bg-white border-b border-gray-200 text-gray-500 font-semibold text-sm sticky top-0 shadow-sm z-10">
                <tr>
                    <th class="px-6 py-4 w-20 text-center"><?php echo $t['th_seq']; ?></th>
                    <th class="px-6 py-4 w-32"><?php echo $t['th_date']; ?></th>
                    <th class="px-6 py-4 w-64"><?php echo $t['th_desc']; ?></th>
                    <th class="px-6 py-4 w-48 text-center"><?php echo $t['th_cat']; ?></th>
                    <th class="px-6 py-4 w-40 text-right"><?php echo $t['th_amount']; ?></th>
                    <th class="px-6 py-4 w-28 text-center"><?php echo $t['th_type']; ?></th>
                    <?php if ($_SESSION['role'] == 'high-admin' || $_SESSION['seer'] == $user_info['department_id']): ?>
                        <th class="px-6 py-4 w-28 text-center"> จัดการ </th>
                    <?php endif; ?>
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
                            <td class="px-6 py-4 text-gray-500 font-mono text-sm"><?php echo $txn['thai_date']; ?></td>
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
                            <?php if ($_SESSION['role'] == 'high-admin' || $_SESSION['seer'] == $user_info['department_id']): ?>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2 opacity-100 sm:opacity-80 group-hover:opacity-100 transition">

                                        <?php if ($_SESSION['role'] == 'high-admin'): ?>

                                            <button type="button" onclick="openDeleteUserModal(<?php echo $user_info['id']; ?>, '<?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?>')"
                                                class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition" title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>

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
                    <td colspan="10" class="p-0 sticky bottom-0 z-20 bg-gray-50 shadow-inner">
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