<div id="txn-table-container" class="flex flex-col h-auto max-h-[calc(78vh-100px)] bg-white rounded-xl shadow-lg border min-h-0 overflow-hidden mb-5">
    <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
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

                        <tr class="border-b transition-colors hover:bg-opacity-75 <?php echo ($txn['type'] == 'income') ? 'bg-green-50' : 'bg-red-50'; ?>">
                            <td class="px-6 py-4 text-center text-gray-400 font-mono text-sm">
                                <?php $index += 1; ?>
                                <?php echo $index; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left align-top">
                                <div class="text-gray-900 font-medium">
                                    <?php echo $txn['thai_date']; ?>
                                </div>

                                <?php if ($txn['type'] == 'income'): ?>
                                    <?php
                                    // --- A. เตรียมข้อมูลสำหรับ "คำนวณ" (ต้องใช้ Eng เท่านั้น) ---
                                    $today = date('Y-m-d');

                                    // ใช้ expire_date ดิบจาก DB (ถ้าไม่มี ให้เอา txn_date ดิบมา +2 ปี)
                                    // *ห้ามเอา thai_date มาใช้นะครับ เพราะคำนวณไม่ได้*
                                    $raw_expire_date = isset($txn['expire_date']) ? $txn['expire_date'] : date('Y-m-d', strtotime($txn['txn_date'] . ' +2 years'));

                                    // เช็คว่าหมดอายุหรือยัง (เทียบด้วย Eng)
                                    $is_expired = ($raw_expire_date < $today);


                                    // --- B. เตรียมข้อมูลสำหรับ "แสดงผล" (ใช้ Thai) ---
                                    if (isset($txn['expire_date_th'])) {
                                        // ถ้ามีวันที่ไทยส่งมาแล้ว ให้ใช้เลย (ไม่ต้องเข้า date/strtotime อีก)
                                        $show_expire_date = $txn['expire_date_th'];
                                    } else {
                                        // ถ้าไม่มี ต้องแปลงเอง (แปลง $raw_expire_date เป็นไทย)
                                        // สมมติว่าแปลงง่ายๆ แบบนี้ (หรือคุณใช้ function แปลงที่มีอยู่แล้วก็ได้)
                                        $timestamp = strtotime($raw_expire_date);
                                        $thai_months = [null, 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                        $show_expire_date = date('j', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543);
                                    }
                                    ?>

                                    <div class="mt-1">
                                        <?php if ($is_expired): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800" title="รายการนี้หมดอายุแล้ว">
                                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                                หมดอายุ: <?php echo $show_expire_date; ?> </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center text-xs text-gray-500" title="วันที่จะหมดอายุ">
                                                <svg class="mr-1 h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                หมดเขต: <?php echo $show_expire_date; ?> </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
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
                            <td class="px-6 py-4 text-right font-mono font-medium align-top rounded-xl ">
                                <?php if ($txn['type'] == 'income'): ?>
                                    <?php
                                    // --- 1. เตรียมข้อมูลตัวเลข ---
                                    $amount = (float)$txn['amount'];
                                    $net = isset($txn['net_carried_over']) ? (float)$txn['net_carried_over'] : $amount;
                                    $raw_left = isset($txn['current_remaining']) ? $txn['current_remaining'] : null;
                                    $display_left = is_null($raw_left) ? $amount : (float)$raw_left;

                                    // --- 2. เช็คเรื่องเวลา (Time Logic) ---
                                    $today = date('Y-m-d');
                                    $expire_date = isset($txn['expire_date']) ? $txn['expire_date'] : '9999-12-31';

                                    // เช็คว่าเป็นรายการจากปีก่อนๆ หรือไม่? (เทียบปี Approved กับปีปัจจุบัน)
                                    // (ใช้ txn_date ที่เป็น Eng จาก DB นะครับ อย่าใช้ thai_date)
                                    $txn_year = date('Y', strtotime($txn['txn_date']));
                                    $current_year = date('Y');
                                    $is_from_past = ($txn_year < $current_year);

                                    // --- 3. สรุปเงื่อนไขการแสดงผล ---
                                    $is_lapsed = ($expire_date < $today && $display_left > 0);
                                    $is_depleted = ($net == 0 || $display_left <= 0);

                                    // --- 4. Logic Tooltip (หัวใจสำคัญ) ---
                                    // โชว์ Tooltip เมื่อ:
                                    //  A. ยอดไม่เท่ากัน (net != amount) -> แปลว่ามีการหักใช้ไปแล้ว
                                    //  B. หรือ เป็นรายการจากปีก่อน ($is_from_past) -> แปลว่ายกยอดมาเต็มๆ
                                    $show_tooltip = ($net != $amount) || $is_from_past;

                                    $tooltip_attr = $show_tooltip ? 'title="ยกยอดมา ' . number_format($net, 2) . ' บาท"' : '';
                                    $cursor_cls = $show_tooltip ? 'cursor-help' : '';
                                    ?>

                                    <?php if ($is_lapsed): ?>
                                        <div class="flex flex-col items-end <?php echo $cursor_cls; ?>" <?php echo $tooltip_attr; ?>>
                                            <span class="text-gray-400 text-lg line-through decoration-gray-400">
                                                <?php echo number_format($amount, 2); ?>
                                            </span>
                                            <span class="text-purple-600 font-bold text-xs mt-1">
                                                คืนคลัง: <?php echo number_format($display_left, 2); ?>
                                            </span>
                                            <span class="text-[10px] text-gray-400">
                                                (จากยอด <?php echo number_format($amount, 0); ?>)
                                            </span>
                                        </div>

                                    <?php elseif ($is_depleted): ?>
                                        <div class="flex flex-col items-end opacity-60 <?php echo $cursor_cls; ?>" <?php echo $tooltip_attr; ?>>

                                            <?php if ($net != $amount): ?>
                                                <span class="text-green-700 text-lg font-bold">
                                                    <?php echo number_format($net, 2); ?>
                                                </span>
                                                <span class="text-gray-500 text-xs line-through decoration-gray-500">
                                                    <?php echo number_format($amount, 2); ?>
                                                </span>
                                                <span class="text-[10px] text-gray-600 mt-0.5">
                                                    *ยกยอดมา <?php echo number_format($net, 0); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-lg decoration-0">
                                                    <?php echo number_format($amount, 2); ?>
                                                </span>
                                            <?php endif; ?>

                                            <span class="text-[10px] text-red-500 font-bold mt-1">
                                                *ยอดรายการนี้ถูกใช้ทั้งหมดแล้ว
                                            </span>
                                        </div>

                                    <?php elseif ($net != $amount): ?>
                                        <div class="flex flex-col items-end <?php echo $cursor_cls; ?>" <?php echo $tooltip_attr; ?>>
                                            <span class="text-green-600 text-lg font-bold">
                                                <?php echo number_format($net, 2); ?>
                                            </span>
                                            <span class="text-gray-400 text-xs line-through decoration-gray-400">
                                                <?php echo number_format($amount, 2); ?>
                                            </span>
                                            <span class="text-[10px] text-gray-500 mt-1">
                                                *ยกยอดมา <?php echo number_format($net, 0); ?> บาท จาก <?php echo number_format($amount, 0); ?> บาท
                                            </span>
                                            <span class="text-[10px] text-blue-600 font-semibold">
                                                (คงเหลือปัจจุบัน: <?php echo number_format($display_left, 2); ?>)
                                            </span>
                                        </div>

                                    <?php else: ?>
                                        <div class="flex flex-col items-end <?php echo $cursor_cls; ?>" <?php echo $tooltip_attr; ?>>
                                            <span class="text-green-600 text-lg">
                                                <?php echo number_format($amount, 2); ?>
                                            </span>

                                            <?php if ($display_left < $amount): ?>
                                                <span class="text-[10px] text-blue-600">
                                                    (เหลือ: <?php echo number_format($display_left, 2); ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                    <?php endif; ?>

                                <?php else: ?>
                                    <span class="text-red-500 text-lg font-bold">
                                        -<?php echo number_format($txn['amount'], 2); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($txn['type'] == 'income'): ?>
                                    <div class="w-8 h-8 mx-auto rounded-full bg-green-100 text-green-500 flex items-center justify-center shadow-sm"><i class="fas fa-arrow-up"></i></div>

                                <?php else: ?>
                                    <div class="w-8 h-8 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center shadow-sm"><i class="fas fa-arrow-down"></i></div>

                                <?php endif; ?>
                            </td>
                            <?php $isUsed = (isset($txn['total_used']) && $txn['total_used'] > 0); ?>
                            <?php if ($_SESSION['role'] == 'high-admin' || $_SESSION['seer'] == $user_info['department_id']): ?>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2 opacity-100 sm:opacity-80 group-hover:opacity-100 transition">

                                        <?php if ($_SESSION['role'] == 'high-admin'): ?>
                                            <?php

                                            if ($txn['type'] == 'income') { ?>
                                                <input type="hidden" id="delete_received_id" name="id_to_delete" value="<?= $txn['id'] ?>">

                                                <button type="button"
                                                    onclick="openEditBudgetReceivedModal(
                                                        '<?php echo $txn['id']; ?>', 
                                                        '<?php echo $user_info['id']; ?>', 
                                                        '<?php echo $user_info['prefix'] . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']; ?>',
                                                        '<?php echo $txn['amount']; ?>', 
                                                        '<?php echo $txn['txn_date']; ?>', 
                                                        '<?php echo addslashes($txn['description']); ?>',
                                                        '<?php echo $isUsed ?>'
                                                    )"
                                                    class="bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded hover:bg-orange-100 text-xs font-bold transition flex items-center gap-1">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <?php if ($isUsed): ?>
                                                    <button type="button" disabled
                                                        onmouseenter="showGlobalAlert('⚠️ ไม่สามารถลบได้: งบประมาณบางส่วน หรือทั้งหมดของรายการนี้ถูกใช้ไปแล้ว')"
                                                        onmouseleave="hideGlobalAlert()"
                                                        class="text-gray-300 cursor-not-allowed p-2 rounded-full">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button"
                                                        onclick="openDeleteModal(
                                                        '<?php echo $txn['id']; ?>', 
                                                        'delete_budget'
                                                    )"
                                                        class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition"
                                                        title="ลบรายการนี้">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php
                                            } else {
                                            ?>
                                                <input type="hidden" id="delete_target_id" name="id_to_delete" value="<?= $txn['id'] ?>">
                                                <button type="button"
                                                    onclick="openEditExpenseModal(
                                                                '<?= $txn['id'] ?>', 
                                                                '<?= $user_info['id'] ?>', 
                                                                '<?php echo $user_info['prefix'] . ' ' . $user_info['first_name'] . ' ' . $user_info['last_name']; ?>', 
                                                                '<?= $txn['amount'] ?>', 
                                                                '<?= $txn['txn_date'] ?>', 
                                                                '<?= isset($txn['category_id']) ? $txn['category_id'] : '' ?>', 
                                                                '<?= addslashes($txn['description']) ?>'
                                                            )"
                                                    class="bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded hover:bg-orange-100 text-xs font-bold transition flex items-center gap-1">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <button type="button"
                                                    onclick="openDeleteModal(
                                                        '<?php echo $txn['id']; ?>', 
                                                        'delete_expense', 
                                                        '<?php echo addslashes($txn['description']); ?>'
                                                    )"
                                                    class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php
                                            }
                                            ?>

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

    <?php
    include_once __DIR__ . '/../../includes/confirm_delete.php';
    include_once __DIR__ . '/../../includes/modal_edit_expense.php';
    include_once __DIR__ . '/../../includes/modal_edit_received.php';
    ?>
</div>