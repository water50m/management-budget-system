<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-orange-200 flex flex-col min-h-0">
    <div class="overflow-x-auto overflow-y-auto flex flex-col min-h-0">
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
                        <?php
                        // 1. กำหนดสีของ Badge ตามประเภท Action
                        $action = $log['action_type'];
                        $badge_class = 'bg-gray-100 text-gray-600 border-gray-200'; // สี Default

                        if (strpos($action, 'delete_') === 0) {
                            // กลุ่มลบ (สีแดง)
                            $badge_class = 'bg-red-100 text-red-700 border border-red-200';
                        } elseif (strpos($action, 'add_') === 0) {
                            // กลุ่มเพิ่ม (สีเขียว)
                            $badge_class = 'bg-green-100 text-green-700 border border-green-200';
                        } elseif (strpos($action, 'update_') === 0) {
                            // กลุ่มแก้ไข (สีม่วง)
                            $badge_class = 'bg-purple-100 text-purple-700 border border-purple-200';
                        }

                        // 2. เช็คว่าเป็น Action ลบหรือไม่ (เพื่อแสดงปุ่มกู้คืน)
                        $is_delete = (strpos($action, 'delete_') === 0);
                        ?>

                        <td class="px-6 py-4">
                            <div class="flex flex-col items-start gap-1">

                                <div class="flex items-center gap-2">
                                    <span class="uppercase text-[10px] font-bold px-2 py-0.5 rounded shadow-sm <?php echo $badge_class; ?>">
                                        <?php echo $action; ?>
                                    </span>

                                    <?php if ($is_delete && $log['status'] != 'restored'): ?>
                                        <button type="button"
                                            onclick="confirmRestore('<?php echo $log['id']; ?>', '<?php echo $action; ?>', '<?php echo $log['target_id']; ?>')"
                                            class="group flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium text-gray-500 bg-white border border-gray-300 rounded hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm"
                                            title="กู้คืนข้อมูลรายการนี้">
                                            <i class="fas fa-undo-alt text-gray-400 group-hover:text-blue-500"></i>
                                            <span>กู้คืน</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($is_delete && $log['status'] == 'restored'): ?>
                                        <span class="flex items-center text-[12px] bg-white border border-gray-300 font-medium text-gray-400 shadow-sm">กู้คืนข้อมูลแล้ว</span>
                                    <?php endif; ?>

                                </div>

                                <span class="text-sm text-gray-700 mt-1 leading-snug">
                                    <?php echo $log['description']; ?>
                                </span>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination): ?>
        <div class="bg-gray-50 border-t <?php echo $borderBase; ?> px-6 py-4 flex flex-col md:flex-row items-center justify-center gap-4">

            <div class="flex items-center gap-1">
                <?php
                $curr = $pagination['current_page'];
                $total = $pagination['total_pages'];
                $limit = $pagination['limit'];

                // --- ส่วน Logic คำนวณเลขหน้า (Fixed 3 หน้า) ---
                $window = 3;
                $start = $curr - 1;
                $end   = $curr + 1;

                if ($start < 1) {
                    $start = 1;
                    $end   = min($total, $start + $window - 1);
                }
                if ($end > $total) {
                    $end   = $total;
                    $start = max(1, $end - $window + 1);
                }
                // ---------------------------------------------
                ?>

                <button hx-get="index.php?page=dashboard&tab=logs&page_num=1&limit=<?php echo $limit; ?>"
                    hx-target="#tab-content"
                    hx-include="[name='search_text']" 
                    <?php echo ($curr == 1) ? 'disabled class="px-2 py-1 rounded border border-gray-200 text-gray-300 cursor-not-allowed"' : 'class="px-2 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition"'; ?>
                    title="หน้าแรก">
                    <i class="fas fa-angle-double-left text-xs"></i>
                </button>

                <button hx-get="index.php?page=dashboard&tab=logs&page_num=<?php echo max(1, $curr - 1); ?>&limit=<?php echo $limit; ?>"
                    hx-target="#tab-content"
                    hx-include="[name='search_text']"
                    <?php echo ($curr == 1) ? 'disabled class="px-3 py-1  rounded border border-gray-200 text-gray-300 cursor-not-allowed"' : 'class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition"'; ?>
                    title="ย้อนกลับ">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>

                <?php
                // แสดงเลข 1 และ ...
                if ($start > 1) {
                    echo "<button hx-get='index.php?page=dashboard&tab=logs&page_num=1&limit=$limit' hx-target='#tab-content' hx-include=\"[name='search_text']\" class='px-3 py-1 rounded border text-sm font-medium transition bg-white text-gray-600 border-gray-300 hover:bg-gray-50'>1</button>";
                    if ($start > 2) echo "<span class='px-1 text-gray-400'>...</span>";
                }

                // Loop แสดงเลขหน้าตรงกลาง
                for ($i = $start; $i <= $end; $i++) {
                    $isActive = ($i == $curr);
                    $cls = $isActive
                        ? "bg-purple-600 text-white border-purple-600 shadow-sm pointer-events-none" // เปลี่ยนสีเป็น purple ตามธีม Logs (หรือใช้ตัวแปร $theme)
                        : "bg-white text-gray-600 border-gray-300 hover:bg-gray-50";

                    echo "<button hx-get='index.php?page=dashboard&tab=logs&page_num=$i&limit=$limit' 
                                  hx-target='#tab-content' 
                                  hx-include=\"[name='search_text']\"
                                  class='px-3 py-1 rounded border text-sm font-medium transition $cls'>
                            $i
                          </button>";
                }

                // แสดง ... และเลขหน้าสุดท้าย
                if ($end < $total) {
                    if ($end < $total - 1) echo "<span class='px-1 text-gray-400'>...</span>";
                    echo "<button hx-get='index.php?page=dashboard&tab=logs&page_num=$total&limit=$limit' hx-target='#tab-content' hx-include=\"[name='search_text']\" class='px-3 py-1 rounded border text-sm font-medium transition bg-white text-gray-600 border-gray-300 hover:bg-gray-50'>$total</button>";
                }
                ?>

                <button hx-get="index.php?page=dashboard&tab=logs&page_num=<?php echo min($total, $curr + 1); ?>&limit=<?php echo $limit; ?>"
                    hx-target="#tab-content"
                    hx-include="[name='search_text']"
                    <?php echo ($curr == $total) ? 'disabled class="px-3 py-1 rounded border border-gray-200 text-gray-300 cursor-not-allowed"' : 'class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition"'; ?>
                    title="ถัดไป">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>

                <button hx-get="index.php?page=dashboard&tab=logs&page_num=<?php echo $total; ?>&limit=<?php echo $limit; ?>"
                    hx-target="#tab-content"
                    hx-include="[name='search_text']"
                    <?php echo ($curr == $total) ? 'disabled class="px-2 py-1 rounded border border-gray-200 text-gray-300 cursor-not-allowed"' : 'class="px-2 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition"'; ?>
                    title="หน้าสุดท้าย">
                    <i class="fas fa-angle-double-right text-xs"></i>
                </button>

            </div>
        </div>
    <?php endif; ?>
</div> 