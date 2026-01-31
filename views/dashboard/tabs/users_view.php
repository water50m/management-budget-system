<?php

renderUserTableComponent(
    $user_list ?? [],
    $filters ?? [],
    $departments_list ?? [],
    $_SESSION['role'],
    $conn // ตัวแปร $conn global จะถูกส่งผ่านมา
);

function renderUserTableComponent($users, $filters, $departments, $currentUserRole, $conn) {
    // 1. รวม search_user กับ search_username เป็นตัวเดียวกัน
    $filters = array_merge([
        'search_text' => '',
        'dept_user' => 0,
        'role_user' => ''
    ], $filters ?? []);

    // 2. ธีมสี (คงเดิม)
    $theme = 'blue';
    $bgHeader = "bg-{$theme}-50";
    $textHeader = "text-{$theme}-900";
    $borderBase = "border-{$theme}-200";
    $btnPrimary = "bg-{$theme}-600 hover:bg-{$theme}-700";
    $focusRing = "focus:border-{$theme}-500 focus:ring-{$theme}-500";
    
    ?>
    
    <div class="bg-white p-5 rounded-xl shadow-sm border <?php echo $borderBase; ?> mb-6 animate-fade-in">
        <form hx-get="index.php?page=dashboard&tab=users" 
              hx-target="#tab-content" 
              hx-push-url="true"
              class="w-full">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-700 mb-1">ค้นหา (ชื่อ / Username)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search_text" 
                               value="<?php echo htmlspecialchars($filters['search_text']); ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm <?php echo $focusRing; ?> pl-9 pr-3 py-2 border text-sm" 
                               placeholder="พิมพ์ชื่ออาจารย์ หรือ username..."
                               hx-trigger="keyup changed delay:500ms search" 
                               hx-get="index.php?page=dashboard&tab=users" 
                               hx-target="#tab-content">
                        </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1">ภาควิชา</label>
                    <select name="dept_user" 
                            class="w-full border-gray-300 rounded-md shadow-sm <?php echo $focusRing; ?> px-3 py-2 border text-sm"
                            onchange="this.form.requestSubmit()"> 
                            <option value="0">-- ทุกภาควิชา --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo ($filters['dept_user'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo $dept['thai_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 
                <?php if ($_SESSION['role'] == 'high-admin'):  ?>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1">สิทธิ์ (Role)</label>
                    <select name="role_user" 
                            class="w-full border-gray-300 rounded-md shadow-sm <?php echo $focusRing; ?> px-3 py-2 border text-sm"
                            onchange="this.form.requestSubmit()">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="user" <?php echo ($filters['role_user'] == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($filters['role_user'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="high-admin" <?php echo ($filters['role_user'] == 'high-admin') ? 'selected' : ''; ?>>High Admin</option>
                    </select>
                </div>
                <?php endif;?>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full <?php echo $btnPrimary; ?> text-white px-4 py-2 rounded-md shadow-sm transition h-[38px] flex items-center justify-center text-sm font-medium">
                        ค้นหา
                    </button>
                    
                    <?php if(!empty($filters['search_text']) || $filters['dept_user'] > 0 || !empty($filters['role_user'])): ?>
                        <button hx-get="index.php?page=dashboard&tab=users" 
                                hx-target="#tab-content"
                                type="button"
                                class="text-gray-400 hover:text-red-500 transition p-2 border border-gray-200 rounded-md h-[38px] w-[38px] flex items-center justify-center bg-gray-50" 
                                title="ล้างค่า">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border <?php echo $borderBase; ?> flex flex-col min-h-0 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 z-10 <?php echo $bgHeader; ?> <?php echo $textHeader; ?> border-b <?php echo $borderBase; ?>">
                    <tr>
                        <th class="px-6 py-4 font-bold text-center w-16">#</th>
                        <th class="px-6 py-4 font-bold">ชื่อ - นามสกุล</th>
                        <th class="px-6 py-4 font-bold">ภาควิชา</th>
                        <th class="px-6 py-4 font-bold">Username</th>
                        <th class="px-6 py-4 font-bold text-center">สิทธิ์ (Role)</th>
                        <th class="px-6 py-4 font-bold text-right">ยอดคงเหลือ (บาท)</th> 
                        <th class="px-6 py-4 font-bold text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-base">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="p-10 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-user-slash text-4xl mb-3 text-gray-300"></i>
                                ไม่พบข้อมูลผู้ใช้งาน
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $index => $u): ?>
                        <tr class="hover:bg-blue-50/40 transition group">
                            <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800"><?php echo $u['prefix'] . $u['first_name'] . ' ' . $u['last_name']; ?></div>
                                <div class="text-xs text-gray-400"><?php echo $u['position']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs border border-gray-200"><?php echo $u['department'] ?? '-'; ?></span>
                            </td>
                            <td class="px-6 py-4 font-mono text-gray-600 text-xs"><?php echo $u['username']; ?></td>
                            <td class="px-6 py-4">
                                <?php if(function_exists('renderUserRoleManageComponent') || true) {
                                     renderUserRoleManageComponent($u, $currentUserRole, $conn); 
                                } else {
                                     echo "<span class='text-xs'>{$u['role_user']}</span>";
                                } ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php 
                                    $balance = floatval($u['remaining_balance'] ?? 0);
                                    $balanceColor = ($balance > 0) ? 'text-green-600' : (($balance < 0) ? 'text-red-600' : 'text-gray-400');
                                ?>
                                <span class="font-mono font-bold <?php echo $balanceColor; ?> text-base"><?php echo number_format($balance, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2 opacity-100 sm:opacity-80 group-hover:opacity-100 transition">
                                    <a href="index.php?page=profile&id=<?php echo $u['id']; ?>" class="bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1 rounded hover:bg-blue-100 text-xs font-bold transition flex items-center gap-1">
                                        <i class="fas fa-user"></i>
                                    </a>
                                    
                                    <button type="button" onclick="openExpenseModal('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['first_name']); ?>', <?php echo $balance; ?>)" 
                                            class="bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded hover:bg-orange-100 text-xs font-bold transition" title="ตัดยอด">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    
                                    <button type="button" onclick="openAddBudgetModal('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['first_name']); ?>')" 
                                            class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1 rounded hover:bg-emerald-100 text-xs font-bold transition" title="เติมเงิน">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    
                                    <button type="button" onclick="openDeleteUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>')"
                                            class="bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-100 text-xs font-bold transition" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>