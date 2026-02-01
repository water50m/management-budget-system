<?php
include_once __DIR__ . '/../../../src/Helper/FE_function.php';

?>
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

                                        
    <?php
    $hx_selectors = "[name='search_text']";

    // 2. เรียกใช้ฟังก์ชัน
    if (function_exists('renderPaginationBar')) {
        renderPaginationBar(
            $pagination,       // ข้อมูล Pagination
            'logs',            // ชื่อ Tab (tab=logs)
            $hx_selectors,     // hx-include
            'purple'           // ธีมสี (ใช้ purple ตามหน้า Logs)
        ); 
    }
    ?>
    
</div> 