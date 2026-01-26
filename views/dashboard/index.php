<?php include_once "modal_add_budget.php";?>
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

    <div class="container mx-auto p-4 md:p-6">

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
            <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
                <form method="GET" action="index.php" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="hidden" name="tab" value="approval">

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหาชื่ออาจารย์</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($data['search_keyword']); ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 px-3 py-2 border" 
                               placeholder="พิมพ์ชื่อ หรือ นามสกุล...">
                    </div>

                    <div class="w-full md:w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ภาควิชา</label>
                        <select name="dept" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 px-3 py-2 border">
                            <option value="0">-- ทุกภาควิชา --</option>
                            <?php foreach ($data['departments_list'] as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($data['search_dept'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['thai_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-full md:w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ปีงบประมาณ</label>
                        <select name="year" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 px-3 py-2 border">
                            <option value="0">-- ทุกปี --</option>
                            <?php foreach ($data['year_list'] as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo (($data['search_year'] ?? 0) == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 shadow-sm transition">
                        🔍 ค้นหา
                    </button>
                    
                    <?php if(!empty($data['search_keyword']) || $data['search_dept'] > 0): ?>
                        <a href="index.php?page=dashboard&tab=approval" class="text-gray-500 text-sm hover:underline ml-2">ล้างตัวกรอง</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-green-200">
                <table class="w-full text-sm text-left">
                    <thead class="bg-green-50 text-green-900 border-b border-green-200">
                        <tr>
                            <th class="px-6 py-4 font-bold text-center w-16">#</th>
                            <th class="px-6 py-4 font-bold">ภาควิชา</th>
                            <th class="px-6 py-4 font-bold">ชื่อ - นามสกุล</th>
                            <th class="px-6 py-4 font-bold text-right">จำนวนเงิน (บาท)</th>
                            <th class="px-6 py-4 font-bold text-center">วันที่อนุมัติ</th>
                            <th class="px-6 py-4 font-bold">หมายเหตุ</th>
                            <th class="px-6 py-4 font-bold text-center w-24">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($data['approvals'])): ?>
                            <tr><td colspan="7" class="p-8 text-center text-gray-400">ไม่พบข้อมูลที่ค้นหา</td></tr>
                        <?php else: ?>
                            <?php foreach ($data['approvals'] as $index => $row): ?>
                            <tr class="hover:bg-green-50/50 transition odd:bg-white even:bg-gray-50">
                                <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>
                                <td class="px-6 py-4 font-medium text-gray-600">
                                    <span class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        <?php echo $row['department'] ? $row['department'] : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-800"><?php echo $row['prefix'].$row['first_name'].' '.$row['last_name']; ?></td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-green-700 text-lg">
                                    <?php echo number_format($row['approved_amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-600"><?php echo $row['thai_date']; ?></td>
                                <td class="px-6 py-4 text-gray-500 italic"><?php echo $row['remark'] ? $row['remark'] : '-'; ?></td>
                                
                                <td class="px-6 py-4 text-center">
                                <?php 
                                    $used = isset($row['total_used']) ? $row['total_used'] : 0;
                                    include __DIR__ . "/../../includes/confirm_delete.php";  
                                    include __DIR__ . "/../../includes/text_box_alert.php";  
                                ?>

                                <?php if ($used > 0): ?>
                                    <div class="inline-block"
                                        onmouseenter="showGlobalAlert('⚠️ ไม่สามารถทำรายการได้: งบประมาณบางส่วนถูกใช้ไปแล้ว')"
                                        onmouseleave="hideGlobalAlert()">
                                        
                                        <button type="button" class="text-gray-300 cursor-not-allowed">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" onclick="openDeleteModal('<?php echo $row['id']; ?>')" 
                                            class="text-red-500 hover:text-red-700 hover:scale-110 transition" title="ลบรายการนี้">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </td>

                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($data['view_mode'] == 'admin_expense_table'): ?>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-purple-100 mb-6">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="hidden" name="tab" value="expense">
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        
                        <div class="md:col-span-3 flex flex-col justify-end">
                            <div class="flex items-center gap-2 mb-1.5">
                                <label class="block text-xs font-bold text-gray-700">คำค้นหา</label>
                            </div>

                            <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-purple-500 focus-within:border-purple-500">
                                <div class="pl-3 pr-2 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                
                                <input type="text" name="search" value="<?php echo htmlspecialchars($data['filters']['search']); ?>" 
                                    class="w-full border-none text-xs text-gray-700 py-2 focus:ring-0 bg-transparent placeholder-gray-400 leading-tight" 
                                    placeholder="ระบุชื่อ / รายละเอียด...">
                            </div>
                        </div>

                        <div class="md:col-span-3 flex flex-col justify-end">
                            <div class="flex items-center gap-2 mb-1.5">
                                <label class="block text-xs font-bold text-gray-700">ช่วงวันที่</label>
                                
                                <div class="relative">
                                    <select name="date_type" class="appearance-none bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-700 text-[11px] font-bold rounded px-2 py-0.5 pr-6 cursor-pointer focus:outline-none focus:ring-1 focus:ring-purple-500 transition">
                                        <option value="approved" <?php echo ($data['filters']['date_type'] == 'approved') ? 'selected' : ''; ?>>
                                            ตามเอกสาร
                                        </option>
                                        <option value="created" <?php echo ($data['filters']['date_type'] == 'created') ? 'selected' : ''; ?>>
                                            วันที่คีย์
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1.5 text-purple-600">
                                        <svg class="h-3 w-3 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-purple-500 focus-within:border-purple-500">
                                <input type="date" name="start_date" value="<?php echo $data['filters']['start_date']; ?>" 
                                    class="w-1/2 border-none text-xs text-gray-600 py-2 px-2 text-center focus:ring-0 bg-transparent"
                                    placeholder="เริ่มต้น">
                                
                                <div class="bg-gray-100 border-l border-r border-gray-200 px-2 py-2 text-xs text-gray-500 font-medium">
                                    ถึง
                                </div>
                                
                                <input type="date" name="end_date" value="<?php echo $data['filters']['end_date']; ?>" 
                                    class="w-1/2 border-none text-xs text-gray-600 py-2 px-2 text-center focus:ring-0 bg-transparent"
                                    placeholder="สิ้นสุด">
                            </div>
                        </div>

                        <div class="md:col-span-2 flex flex-col justify-end">
                            <div class="flex items-center mb-1.5 h-[21px]">
                                <label class="block text-xs font-bold text-gray-700">หมวดหมู่</label>
                            </div>
                            
                            <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-purple-500 focus-within:border-purple-500">
                                <div class="pl-2 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <select name="cat_id" class="w-full border-none text-xs text-gray-700 py-2 pl-2 pr-8 focus:ring-0 bg-transparent cursor-pointer">
                                    <option value="0">--ทุกหมวด--</option>
                                    <?php foreach ($data['categories_list'] as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($data['filters']['cat_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['name_th']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="md:col-span-3 flex flex-col justify-end">
                            <div class="flex items-center mb-1.5 h-[21px]">
                                <label class="block text-xs font-bold text-gray-700">จำนวนเงิน (บาท)</label>
                            </div>

                            <div class="flex items-center bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-purple-500 focus-within:border-purple-500">
                                <input type="number" name="min_amount" placeholder="Min" value="<?php echo $data['filters']['min_amount']; ?>" 
                                    class="w-1/2 border-none text-xs text-gray-600 py-2 px-2 text-center focus:ring-0 bg-transparent" step="0.01">
                                
                                <div class="bg-gray-100 border-l border-r border-gray-200 px-3 py-2 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </div>
                                
                                <input type="number" name="max_amount" placeholder="Max" value="<?php echo $data['filters']['max_amount']; ?>" 
                                    class="w-1/2 border-none text-xs text-gray-600 py-2 px-2 text-center focus:ring-0 bg-transparent" step="0.01">
                            </div>
                        </div>

                        <div class="md:col-span-1 flex flex-col justify-end">
                            <div class="h-[21px] mb-1.5"></div>
                            
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-md text-sm font-medium transition shadow-sm flex justify-center items-center h-[38px]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                ค้นหา
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-purple-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-purple-50 text-purple-900 border-b border-purple-100">
                            <tr>
                                <th class="px-6 py-4 font-bold text-center w-16">#</th>
                                <th class="px-6 py-4 font-bold whitespace-nowrap">วันที่รายการ</th>
                                <th class="px-6 py-4 font-bold">ผู้เบิกจ่าย</th>
                                <th class="px-6 py-4 font-bold whitespace-nowrap">หมวดหมู่</th> <th class="px-6 py-4 font-bold">รายละเอียด</th> <th class="px-6 py-4 font-bold text-right">จำนวนเงิน (บาท)</th>
                                </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($data['expenses'])): ?>
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                            ไม่พบรายการที่ค้นหา
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['expenses'] as $index => $row): ?>
                                    <tr class="hover:bg-purple-50/30 transition group">
                                        <td class="px-6 py-4 text-center text-gray-400"><?php echo $index + 1; ?></td>
                                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                            <?php echo $row['thai_date']; ?>
                                            <div class="text-[10px] text-gray-400">เวลา: <?php echo date('H:i', strtotime($row['created_at'])); ?> น.</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800"><?php echo $row['prefix'].$row['first_name'].' '.$row['last_name']; ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $row['department']; ?></div>
                                        </td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-700">
                                                <?php echo $row['category_name'] ? $row['category_name'] : '-'; ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo $row['description']; ?>
                                        </td>

                                        <td class="px-6 py-4 text-right font-mono font-bold text-red-600 text-lg whitespace-nowrap">
                                            - <?php echo number_format($row['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
</body>
</html>