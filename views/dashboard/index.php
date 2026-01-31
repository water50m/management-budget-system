<?php
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . "/modal_add_budget.php";
include_once __DIR__ . "/expenseTableFunction.php";
include_once __DIR__ . "/approveTableFunction.php";
include_once __DIR__ . "/userTableFunction.php";
include_once __DIR__ . "/../../includes/userRoleManageFunction.php";
include_once __DIR__ . "/../../includes/saveLogFunction.php";

?>
    <div class="w-full px-4 p-4 md:px-8 flex-1 flex flex-col overflow-hidden">

        <?php if (strpos($data['view_mode'], 'admin_') === 0): ?>

            <?php if ($data['view_mode'] == 'admin_approval_table'): ?>
                <?php
                renderApprovalTableComponent(
                    $data['approvals'],
                    $data['filters'],
                    $data['departments_list'],
                    $year = $data['years_list'],
                    $color = 'emerald'
                );
                ?>


            <?php elseif ($data['view_mode'] == 'admin_expense_table'): ?>
                <?php
                renderExpenseTableComponent(
                    $data['expenses'],          // ข้อมูลรายการ
                    $data['filters'],           // ข้อมูลการกรอง
                    $data['departments_list'],  // ข้อมูล dropdown แผนก
                    $data['categories_list'],    // ข้อมูล dropdown หมวดหมู่
                    $year = $data['years_list'],
                    $color = 'purple'
                );
                ?>

            <?php elseif ($data['view_mode'] == 'admin_user_table' && $_SESSION['role'] == 'high-admin'): ?>
                <?php
                renderUserTableComponent(
                    $data['user_list'],
                    $data['filters'],      // อย่าลืม update controller ให้รับ search_username, role_user ด้วย
                    $data['departments_list'],
                    $_SESSION['role'] ,     // ส่ง Role ของคนที่ Login เข้าไปเพื่อเช็คสิทธิ์แก้ไข
                    $conn
                );
                ?>


            <?php elseif ($data['view_mode'] == 'admin_activity_logs'): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-orange-200">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-orange-50 text-orange-900 border-b border-orange-200">
                            <tr>
                                <th class="px-6 py-4 font-bold w-40">วัน-เวลา</th>
                                <th class="px-6 py-4 font-bold w-1/4">ผู้ทำรายการ (Actor)</th>
                                <th class="px-6 py-4 font-bold w-1/4">ผู้ถูกกระทำ (Target)</th>
                                <th class="px-6 py-4 font-bold">รายละเอียดกิจกรรม</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($data['logs'] as $log): ?>
                                <tr class="hover:bg-orange-50/30 transition">

                                    <td class="px-6 py-4 text-gray-500 text-xs font-mono">
                                        <?php echo $log['thai_datetime']; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800"><?php echo $log['actor_name'] ?: 'Unknown'; ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo $log['actor_username']; ?>
                                            <span class="bg-gray-100 px-1 rounded ml-1"><?php echo $log['actor_role']; ?></span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php if ($log['target_name']): ?>
                                            <div class="font-bold text-blue-800"><?php echo $log['target_name']; ?></div>
                                            <div class="text-xs text-blue-400"><?php echo $log['target_username']; ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="uppercase text-xs font-bold px-2 py-0.5 rounded mr-2 
                                    <?php echo $log['action_type'] == 'update_role' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                            <?php echo $log['action_type']; ?>
                                        </span>
                                        <span class="text-gray-700"><?php echo $log['description']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php else: ?>
        <?php endif; ?>

    </div>

    <!-----------------------------------------------modal-------------------------------------------------------------------- -->
    <div id="expenseModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4 transform transition-all scale-100">

            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-bold text-gray-800">
                    📝 บันทึกรายจ่าย
                    <span  class="block text-sm text-blue-600 font-normal mt-1" id="modalUserName">กำลังโหลด...</span>
                    
                </h3>
                <button onclick="closeExpenseModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>

            <form method="POST" action="index.php?page=dashboard">
                

                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4 text-center">
                    <div class="flex justify-around items-center divide-x divide-green-300">
                        <div>
                            <span class="block text-[10px] uppercase font-bold opacity-70">ยอดเงินคงเหลือเดิม</span>
                            <span class="block text-lg font-bold" id="modalBalanceDisplay">0.00 บาท</span>
                        </div>
                        <div class="pl-4">
                            <span class="block text-[10px] uppercase font-bold opacity-70">ยอดคงเหลือ (หลังตัดใหม่)</span>
                            <span class="block text-xl font-bold text-blue-700" id="modalNewBalanceDisplay">0.00 บาท</span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">จำนวนเงิน (บาท)</label>

                    <input type="text"
                        id="inputAmountDisplay"
                        placeholder="0.00"
                        required
                        oninput="handleAmountInput(this)"
                        inputmode="decimal"
                        class="w-full border border-gray-300 rounded-lg p-2.5 text-right font-mono text-lg font-bold text-green-700 focus:ring-2 focus:ring-green-500 outline-none">

                    <input type="hidden" name="amount" id="inputAmountReal">
                </div>

                <input type="hidden" name="action" value="add_expense">
                <input type="hidden" name="target_user_id" id="modalUserId" value="">
                <input type="hidden" name="traget_name" id="modalFullName" value="" >

                <div class="space-y-3">
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            วันที่อนุมัติ (ตามเอกสาร)
                        </label>

                        <input type="date"
                            id="expense_date"
                            name="expense_date"
                            oninput="checkManualDate(this, 'use_today')"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>

                        <div class="mt-2 flex items-center">
                            <input type="checkbox"
                                id="use_today"
                                onclick="toggleTodayDate(this, 'expense_date')"
                                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                            <label for="use_today" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">
                                ใช้วันที่ปัจจุบัน (วันนี้)
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ประเภทการใช้เงิน</label>
                        <select name="category_id" required class="w-full border border-gray-300 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-green-500 outline-none">
                            <?php foreach ($data['categories_list'] as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_th']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">รายละเอียด</label>
                        <input type="text" name="description" placeholder="เช่น ค่าลงทะเบียนงานประชุมวิชาการ..."
                            class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>


                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeExpenseModal()" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 shadow-lg transform hover:-translate-y-0.5 transition-all">
                        💾 บันทึกรายการ
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php include_once __DIR__ . '/../../includes/footer.php';?>

    