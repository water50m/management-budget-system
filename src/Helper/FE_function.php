<?php 
// ---------------------------------------------------------
// ฟังก์ชันวาดปุ่ม Pagination (ใช้ฝั่ง View/HTML)
// ---------------------------------------------------------
function renderPaginationBar($pagination, $tab_name, $hx_include_selector, $theme = 'blue') {
    // ถ้าไม่มีข้อมูล หรือมีแค่หน้าเดียว ไม่ต้องแสดง
    if (!$pagination || $pagination['total_pages'] <= 1) return;

    $curr = $pagination['current_page'];
    $total = $pagination['total_pages'];
    $limit = $pagination['limit'];

    // Logic คำนวณ Window (3 หน้า)
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

    // เตรียม Class สี
    $btn_base = "px-3 py-1 rounded border text-sm font-medium transition";
    $btn_inactive = "bg-white text-gray-600 border-gray-300 hover:bg-gray-50";
    $btn_active   = "bg-{$theme}-600 text-white border-{$theme}-600 shadow-sm pointer-events-none";
    $btn_disabled = "bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed";

    echo '<div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex flex-col md:flex-row items-center justify-center gap-4">';
    echo '<div class="flex items-center gap-1">';

    // --- Helper สร้างปุ่มย่อย ---
    $renderBtn = function($pageNum, $iconOrText, $isDisabled = false, $isActive = false) 
                 use ($tab_name, $limit, $hx_include_selector, $btn_base, $btn_inactive, $btn_active, $btn_disabled) {
        
        if ($isActive) {
            $class = "$btn_base $btn_active";
            $attr = "";
        } elseif ($isDisabled) {
            $class = "$btn_base $btn_disabled";
            $attr = "disabled";
        } else {
            $class = "$btn_base $btn_inactive";
            // สร้าง URL แบบ Dynamic ตาม Tab Name
            $url = "index.php?page=dashboard&tab={$tab_name}&page_num={$pageNum}&limit={$limit}";
            $attr = "hx-get='$url' hx-target='#tab-content' hx-include=\"$hx_include_selector\"";
        }

        echo "<button $attr class='$class'>$iconOrText</button>";
    };

    // 1. ปุ่มหน้าแรก (<<)
    $renderBtn(1, '<i class="fas fa-angle-double-left text-xs"></i>', ($curr == 1));

    // 2. ปุ่มย้อนกลับ (<)
    $renderBtn(max(1, $curr - 1), '<i class="fas fa-chevron-left text-xs"></i>', ($curr == 1));

    // 3. ตัวเลข
    if ($start > 1) {
        $renderBtn(1, '1');
        if ($start > 2) echo "<span class='px-1 text-gray-400'>...</span>";
    }

    for ($i = $start; $i <= $end; $i++) {
        $renderBtn($i, $i, false, ($i == $curr));
    }

    if ($end < $total) {
        if ($end < $total - 1) echo "<span class='px-1 text-gray-400'>...</span>";
        $renderBtn($total, $total);
    }

    // 4. ปุ่มถัดไป (>)
    $renderBtn(min($total, $curr + 1), '<i class="fas fa-chevron-right text-xs"></i>', ($curr == $total));

    // 5. ปุ่มสุดท้าย (>>)
    $renderBtn($total, '<i class="fas fa-angle-double-right text-xs"></i>', ($curr == $total));

    echo '</div></div>';
}
