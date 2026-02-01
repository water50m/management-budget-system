<?php 
// ---------------------------------------------------------
// ฟังก์ชันวาดปุ่ม Pagination (Update: Center Buttons + Right Info)
// ---------------------------------------------------------
function renderPaginationBar($pagination, $tab_name, $hx_include_selector, $theme = 'blue') {
    // ถ้าไม่มีข้อมูล ไม่ต้องแสดงอะไรเลย
    if (!$pagination) return;

    // ดึงค่าต่างๆ
    $curr = $pagination['current_page'];
    $total_pages = $pagination['total_pages']; // จำนวนหน้า
    $limit = $pagination['limit'];
    $total_rows = $pagination['total_rows'];   // จำนวนรายการทั้งหมด

    // ถ้ามีหน้าน้อยกว่า 1 และไม่ได้เลือก "แสดงทั้งหมด" อยู่ ก็ไม่ต้องโชว์
    // แต่ถ้าเลือก "แสดงทั้งหมด" อยู่ ($limit สูงๆ) ควรโชว์บาร์เพื่อให้กดกลับไปแบบแบ่งหน้าได้
    $is_show_all = ($limit >= 1000000); // เช็คว่าเลือกดูทั้งหมดอยู่ไหม

    if ($total_pages <= 1 && !$is_show_all) return;


    // --- Logic คำนวณเลขหน้า ---
    $window = 3;
    $start = $curr - 1;
    $end   = $curr + 1;
    if ($start < 1) {
        $start = 1;
        $end   = min($total_pages, $start + $window - 1);
    }
    if ($end > $total_pages) {
        $end   = $total_pages;
        $start = max(1, $end - $window + 1);
    }

    // --- เตรียม Class สี ---
    $btn_base = "px-3 py-1 rounded border text-sm font-medium transition";
    $btn_inactive = "bg-white text-gray-600 border-gray-300 hover:bg-gray-50";
    $btn_active   = "bg-{$theme}-600 text-white border-{$theme}-600 shadow-sm pointer-events-none";
    $btn_disabled = "bg-gray-50 text-gray-300 border-gray-200 cursor-not-allowed";

    // 🟢 Container: ใช้ justify-between
    echo '<div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-4">';

    // ==========================================
    // 1. ฝั่งซ้าย (Spacer) - ใส่ไว้เพื่อให้ตรงกลางมัน Center จริงๆ
    // ==========================================
    echo '<div class="flex-1 flex items-center gap-3 text-sm text-gray-600">';
    // แสดงจำนวน
    echo '<span>ทั้งหมด <b class="text-gray-900">' . number_format($total_rows) . '</b>  รายการ</span>';

    // div ปิด
    echo '</div>';


    // ==========================================
    // 2. ตรงกลาง (ปุ่มตัวเลข Pagination)
    // ==========================================
    echo '<div class="flex items-center gap-1 justify-center">';

    // ถ้าไม่ได้เลือก "แสดงทั้งหมด" ให้โชว์ปุ่มตัวเลขปกติ
    if (!$is_show_all) {
        // Helper สร้างปุ่มย่อย
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
                $url = "index.php?page=dashboard&tab={$tab_name}&page_num={$pageNum}&limit={$limit}";
                $attr = "hx-get='$url' hx-target='#tab-content' hx-include=\"$hx_include_selector\"";
            }
            echo "<button $attr class='$class'>$iconOrText</button>";
        };

        // ปุ่ม First & Prev
        $renderBtn(1, '<i class="fas fa-angle-double-left text-xs"></i>', ($curr == 1));
        $renderBtn(max(1, $curr - 1), '<i class="fas fa-chevron-left text-xs"></i>', ($curr == 1));

        // เลขหน้า
        if ($start > 1) {
            $renderBtn(1, '1');
            if ($start > 2) echo "<span class='px-1 text-gray-400'>...</span>";
        }
        for ($i = $start; $i <= $end; $i++) {
            $renderBtn($i, $i, false, ($i == $curr));
        }
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo "<span class='px-1 text-gray-400'>...</span>";
            $renderBtn($total_pages, $total_pages);
        }

        // ปุ่ม Next & Last
        $renderBtn(min($total_pages, $curr + 1), '<i class="fas fa-chevron-right text-xs"></i>', ($curr == $total_pages));
        $renderBtn($total_pages, '<i class="fas fa-angle-double-right text-xs"></i>', ($curr == $total_pages));
    } else {
        // ถ้าเลือก "แสดงทั้งหมด" อยู่ ให้ขึ้นข้อความแทนปุ่ม
        echo "<span class='text-sm text-gray-500 font-medium'>แสดงข้อมูลทั้งหมด ($total_rows รายการ)</span>";
    }

    echo '</div>'; // จบส่วนตรงกลาง


    // ==========================================
    // 3. ฝั่งขวา (จำนวนรวม + ปุ่มแสดงทั้งหมด)
    // ==========================================
    echo '<div class="flex-1 flex items-center justify-end gap-3 text-sm text-gray-600">';

    // ปุ่ม Toggle (แสดงทั้งหมด / แบ่งหน้า)
    if (!$is_show_all) {
        // ปุ่ม: กดเพื่อดูทั้งหมด (ส่ง limit = 1000000)
        echo "<button hx-get='index.php?page=dashboard&tab={$tab_name}&limit=1000000' 
                      hx-target='#tab-content' 
                      hx-include=\"$hx_include_selector\"
                      class='text-{$theme}-600 hover:text-{$theme}-800 underline text-xs font-bold transition whitespace-nowrap'>
                  แสดงทั้งหมด
              </button>";
    } else {
        // ปุ่ม: กดเพื่อกลับมาแบ่งหน้า (ส่ง limit = 10 ค่า Default)
        echo "<button hx-get='index.php?page=dashboard&tab={$tab_name}&limit=10' 
                      hx-target='#tab-content' 
                      hx-include=\"$hx_include_selector\"
                      class='text-gray-500 hover:text-gray-800 underline text-xs font-bold transition whitespace-nowrap'>
                  แสดงแบบแบ่งหน้า
              </button>";
    }

    echo '</div>'; // จบส่วนขวา

    echo '</div>'; // จบ Container
}
?>