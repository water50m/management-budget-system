<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลส่วนตัว - ระบบบริหารงานวิจัย</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="font-bold text-xl text-blue-800">Mali Project</div>
        <div class="flex items-center gap-4">
            <a href="index.php?page=dashboard" class="text-gray-600 hover:text-blue-600">🏠 กลับหน้าหลัก</a>
            <a href="index.php?page=logout" class="text-red-500 hover:text-red-700 text-sm font-semibold">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
    
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 flex flex-col items-center text-center">
                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center text-4xl mb-4">
                    👤
                </div>
                <h2 class="text-xl font-bold text-gray-800">
                    <?php echo $user_info['prefix'] . $user_info['first_name'] . ' ' . $user_info['last_name']; ?>
                </h2>
                <p class="text-gray-500 font-medium"><?php echo $user_info['position']; ?></p>
                <span class="mt-2 px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                    ภาควิชา: <?php echo $user_info['department_name']; ?>
                </span>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 col-span-2 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="fas fa-wallet text-9xl text-green-500"></i>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-500 uppercase font-bold">ยอดเงินคงเหลือสุทธิ (Net Balance)</p>
                    <h1 class="text-4xl font-bold text-green-600 mt-1">
                        <?php echo number_format($user_info['remaining_balance'], 2); ?> <span class="text-lg text-gray-400 font-normal">บาท</span>
                    </h1>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4 border-t pt-4">
                    <div>
                        <p class="text-xs text-gray-500">💰 งบจากปีก่อนหน้า (Carry Over)</p>
                        <p class="text-lg font-bold text-blue-600">
                            <?php echo number_format($user_info['previous_year_budget'], 2); ?>
                        </p>
                        <p class="text-[10px] text-gray-400">* ต้องใช้ก่อนหมดอายุ</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">🆕 งบปีปัจจุบัน (Current Year)</p>
                        <p class="text-lg font-bold text-indigo-600">
                            <?php echo number_format($user_info['current_year_budget'], 2); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="border-b bg-gray-50 px-6 py-3 flex gap-6">
                <h3 class="font-bold text-gray-700 flex items-center gap-2">
                    📉 ประวัติการใช้จ่าย
                    <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full"><?php echo mysqli_num_rows($expenses); ?></span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3">วันที่ใช้</th>
                            <th class="px-6 py-3">รายการ</th>
                            <th class="px-6 py-3">หมวดหมู่</th>
                            <th class="px-6 py-3 text-right">จำนวนเงิน</th>
                            <th class="px-6 py-3 text-center">แหล่งเงิน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (mysqli_num_rows($expenses) > 0): ?>
                            <?php while ($exp = mysqli_fetch_assoc($expenses)): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-mono text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($exp['expense_date'])); ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <?php echo $exp['description']; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-md bg-blue-50 text-blue-600 text-xs">
                                        <?php echo $exp['category_name']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-red-500">
                                    -<?php echo number_format($exp['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if (($exp['budget_source_type'] ?? '') == 'carry_over'): ?>
                                        <span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-1 rounded border border-yellow-200">
                                            งบปีก่อน
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded border border-green-200">
                                            งบปีนี้
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">ยังไม่มีประวัติการใช้จ่าย</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden border border-green-100">
            <div class="border-b bg-green-50 px-6 py-3">
                <h3 class="font-bold text-green-800">📥 ประวัติการได้รับอนุมัติงบ</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white border-b text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">วันที่อนุมัติ</th>
                            <th class="px-6 py-3 text-left">รายละเอียด</th>
                            <th class="px-6 py-3 text-left">ปีงบประมาณ</th>
                            <th class="px-6 py-3 text-right">ยอดอนุมัติ</th>
                            <th class="px-6 py-3 text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php while ($app = mysqli_fetch_assoc($approvals)): ?>
                        <tr class="<?php echo ($app['status'] == 'expire') ? 'bg-gray-50 opacity-60' : ''; ?>">
                            <td class="px-6 py-3 font-mono"><?php echo date('d/m/Y', strtotime($app['approved_date'])); ?></td>
                            <td class="px-6 py-3"><?php echo $app['remark']; ?></td>
                            <td class="px-6 py-3">
                                <span class="font-bold text-gray-600">ปี <?php echo $app['fiscal_year_th']; ?></span>
                            </td>
                            <td class="px-6 py-3 text-right font-bold text-green-600">
                                +<?php echo number_format($app['approved_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <?php if ($app['status'] == 'active'): ?>
                                    <span class="text-xs text-green-600 font-bold">✅ ใช้งานได้</span>
                                <?php else: ?>
                                    <span class="text-xs text-red-500 font-bold">❌ หมดอายุ (เกิน 2 ปี)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>