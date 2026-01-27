<?php 
include_once "modal_add_budget.php";
include_once  __DIR__ . "/../../includes/renderExpenseTableComponent.php";
include_once  __DIR__ . "/../../includes/renderApproveTableComponent.php";


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo $data['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div>
            <h1 class="text-xl font-bold text-blue-800">ระบบบริหารงานวิจัย</h1>
            <p class="text-xs text-gray-500">Mali Project</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-gray-700"><?php echo $_SESSION['fullname']; ?></div>
                <div class="text-xs text-gray-500 capitalize"><?php echo $_SESSION['role']; ?></div>
            </div>
            <a href="index.php?page=logout" class="bg-red-50 text-red-600 px-3 py-1 rounded hover:bg-red-100 text-sm">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="w-full px-4 p-4 md:px-8">

    <?php if (strpos($data['view_mode'], 'admin_') === 0): ?>
        
        <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    <?php echo ($data['current_tab'] == 'approval') ? 'สรุปยอดงบประมาณที่อนุมัติ' : 'ภาพรวมคำขอ (Request)'; ?>
                </h2>
                <p class="text-gray-500 text-sm">ปีงบประมาณ 2569</p>
            </div>
            
            <div class="flex bg-white rounded-lg shadow-sm p-1 border">
                <a href="index.php?page=dashboard&tab=approval" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $data['current_tab'] == 'approval' ? 'bg-green-100 text-green-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50'; ?>">
                   ✅ ยอดที่อนุมัติ (Approved)
                </a>
                <a href="index.php?page=dashboard&tab=expense" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $data['current_tab'] == 'expense' ? 'bg-purple-100 text-purple-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50'; ?>">
                   📝 ยอดที่ขอ (Request)
                </a>

                <a href="index.php?page=dashboard&tab=users" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $data['current_tab'] == 'users' ? 'bg-blue-100 text-blue-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50'; ?>">
                   👥 ผู้ใช้งาน (Users)
                </a>
                <?php if ($_SESSION['role'] == 'high-admin'): ?>
                <a href="index.php?page=dashboard&tab=logs" 
                   class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $data['current_tab'] == 'logs' ? 'bg-orange-100 text-orange-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50'; ?>">
                   🕒 ประวัติระบบ (Logs)
                </a>
            <?php endif; ?>
            </div>
        </div>
        <?php if ($data['view_mode'] == 'admin_approval_table'): ?>
           <?php 
            renderApprovalTableComponent(
                $data['approvals'],        
                $data['filters'], 
                $data['departments_list'],
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

        <?php elseif ($data['view_mode'] == 'admin_user_list'): ?>
            
            <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
                <form method="GET" action="index.php" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="hidden" name="tab" value="users">

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหาชื่อ - นามสกุล</label>
                        <input type="text" name="search_user" value="<?php echo htmlspecialchars($data['filter_user_name'] ?? ''); ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring-purple-500 px-3 py-2 border" 
                               placeholder="พิมพ์ชื่ออาจารย์...">
                    </div>

                    <div class="w-full md:w-64">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ภาควิชา</label>
                        <select name="dept_user" class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring-purple-500 px-3 py-2 border">
                            <option value="0">-- ทุกภาควิชา --</option>
                            <?php foreach ($data['departments_list'] as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (($data['filter_user_dept'] ?? 0) == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['thai_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 shadow-sm transition h-[42px]">
                        🔍 ค้นหา
                    </button>

                    <?php if(!empty($data['filter_user_name']) || ($data['filter_user_dept'] ?? 0) > 0): ?>
                        <a href="index.php?page=dashboard&tab=users" class="text-gray-500 text-sm hover:underline ml-2">ล้างค่า</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-purple-200">
                <table class="w-full text-sm text-left">
                    
                    <thead class="bg-purple-50 text-purple-900 border-b border-purple-200">
                        <tr>
                            <th class="px-6 py-4 font-bold text-center w-16">#</th>
                            <th class="px-6 py-4 font-bold">ชื่อ - นามสกุล</th>
                            <th class="px-6 py-4 font-bold">ภาควิชา</th>
                            <th class="px-6 py-4 font-bold">Username</th>
                            
                            <?php if ($_SESSION['role'] == 'high-admin'): ?>
                                <th class="px-6 py-4 font-bold text-center">สิทธิ์ (Role)</th>
                            <?php endif; ?>

                            <th class="px-6 py-4 font-bold text-center">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($data['user_list'] as $index => $u): ?>
                        <tr class="hover:bg-purple-50/30 transition">
                            <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800"><?php echo $u['prefix'] . $u['first_name'] . ' ' . $u['last_name']; ?></div>
                                <div class="text-xs text-gray-400"><?php echo $u['position']; ?></div>
                            </td>
                            <td class="px-6 py-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"><?php echo $u['department'] ?? '-'; ?></span></td>
                            <td class="px-6 py-4 font-mono text-gray-600"><?php echo $u['username']; ?></td>

                            <?php if ($_SESSION['role'] == 'high-admin'): ?>
                                <td class="px-6 py-4 text-center min-w-[180px]">
                                    
                                    <?php if ($u['role'] != 'high-admin'): ?> 
                                        <form method="POST" action="index.php?page=dashboard" class="flex items-center justify-center gap-2">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                                            
                                            <select name="new_role" 
                                                    data-original="<?php echo $u['role']; ?>"
                                                    onchange="checkRoleChange(this)"
                                                    class="border border-gray-300 rounded text-sm px-2 py-1 bg-white focus:ring-2 focus:ring-purple-500 cursor-pointer shadow-sm">
                                                <option value="user" <?php echo $u['role']=='user'?'selected':''; ?>>User</option>
                                                <option value="admin" <?php echo $u['role']=='admin'?'selected':''; ?>>Admin</option>
                                            </select>

                                            <div class="role-actions hidden flex items-center gap-1">
                                                <button type="submit" class="text-green-600 hover:bg-green-100 p-1 rounded" title="บันทึก">💾</button>
                                                <button type="button" onclick="cancelRoleEdit(this)" class="text-red-500 hover:bg-red-100 p-1 rounded" title="ยกเลิก">❌</button>
                                            </div>
                                        </form>
                                    
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">High Admin</span>
                                    <?php endif; ?>

                                </td>
                            <?php endif; ?>

                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="index.php?page=profile&id=<?php echo $u['id']; ?>" 
                                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 text-xs font-bold transition whitespace-nowrap flex items-center gap-1">
                                            👤 ดูโปรไฟล์
                                    </a>
                                    <button type="button" 
                                            onclick="openExpenseModal(
                                                '<?php echo $u['id']; ?>', 
                                                '<?php echo $u['prefix'] . $u['first_name'] . ' ' . $u['last_name']; ?>', 
                                                <?php echo floatval($u['remaining_balance'] ); ?> 
                                            )"
                                            class="bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 text-xs font-bold transition flex items-center gap-1 whitespace-nowrap">
                                        ➕ เพิ่มรายการ
                                    </button>
                                    
                                    <button type="button" 
                                            onclick="openAddBudgetModal(
                                                '<?php echo $u['id']; ?>', 
                                                '<?php echo $u['prefix'] . $u['first_name'] . ' ' . $u['last_name']; ?>'
                                            )"
                                            class="bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 text-xs font-bold transition flex items-center gap-1 whitespace-nowrap">
                                        💰 เพิ่มเงิน
                                    </button>

                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

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
                                    <?php echo $log['action_type']=='update_role'?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700'; ?>">
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
                <span class="block text-sm text-blue-600 font-normal mt-1" id="modalUserName">กำลังโหลด...</span>
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

<script src="assets/js/dashboard.js"></script>
<script src="assets/js/utils.js"></script>
</body>
</html>