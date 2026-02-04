<div class="w-full mx-auto">
    <style>
        #fpaTableBody>div[hx-swap-oob] {
            display: none;
        }
    </style>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4  p-4 rounded-xl shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-2">
                <i class="fa-solid fa-table-list text-blue-600"></i>

                <span>สรุปงบประมาณ FPA ปีงบประมาณ ปี</span>

                <select name="fiscal_year"
                    class="sync-fiscal-year  bg-blue-50 border border-blue-300 text-blue-900 text-sm rounded-lg p-1.5 cursor-pointer hover:bg-blue-100 transition-colors"
                    hx-get=""
                    hx-target="#tab-content"
                    hx-swap="innerHTML"
                    hx-trigger="change"
                    hx-include="[name='department_id']"
                    hx-indicator="#loadingIndicator">
                    <?php foreach ($year_options as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $current_year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>


                <span id="loadingIndicator" class="htmx-indicator text-sm text-gray-400 ml-2">

                </span>
            </h2>
            <?php if ($current_year == $real_fiscal_year):
            ?>
                <p class="text-xs text-red-400 mt-1 font-medium">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    ข้อมูลนี้เป็นยอดตั้งแต่เริ่มต้นปีงบประมาณ(ปีนี้)ถึงปัจจุบันเท่านั้น (อาจยังไม่ใช่ผลสรุปประจำปี)
                </p>
            <?php endif; ?>
        </div>
        <div class="flex gap-3">
            <button onclick="exportToExcel()"
                class="group flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <i class="fa-solid fa-file-excel text-lg group-hover:scale-110 transition-transform"></i>
                <span>Export Excel</span>
            </button>

            <button onclick="exportToPDF()"
                class="group flex items-center gap-2 bg-red-700 hover:bg-red-800 text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                <i class="fa-solid fa-file-pdf text-lg group-hover:scale-110 transition-transform"></i>
                <span>Export PDF</span>
            </button>
        </div>

    </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-200">
        <table class="w-full text-sm text-left border-collapse" id="fpaTable">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th colspan="9" class="px-4 py-4 text-center text-lg font-bold text-blue-900 bg-blue-50/50">
                        สรุปงบประมาณ FPA ของคณะฯ ปีงบประมาณ
                        <span id="headerYearText"><?php echo $current_year; ?></span>
                    </th>
                </tr>
                <tr class="border-b border-gray-300">
                    <th colspan="9" class="px-4 py-2 text-center font-bold bg-gray-100">
                        <div class="flex justify-center items-center gap-2">
                            <select name="department_id"
                                class="sync-depm bg-white border border-gray-300 text-gray-700 text-sm rounded p-1 cursor-pointer"
                                hx-get=""
                                hx-target="#tab-content"
                                hx-swap="innerHTML"
                                hx-trigger="change"
                                hx-include="[name='fiscal_year']"
                                hx-indicator="#loadingIndicator">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($dept_options as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($d['id'] == $current_dept) ? 'selected' : ''; ?>><?php echo $d['thai_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </th>
                </tr>
                <tr class="text-xs uppercase bg-gray-200 border-b border-gray-300">
                    <th rowspan="2" class="px-4 py-2 text-center w-16 border-r">ลำดับ</th>
                    <th rowspan="2" class="px-4 py-2 text-center min-w-[220px] border-r">ชื่อ - นามสกุล</th>
                    <th colspan="5" class="px-4 py-2 text-center text-red-700 font-bold bg-red-50 border-b border-red-200">
                        ความต้องการใช้เงิน FPA (ในปี
                        <span id="headerYearTextLastTwo">
                            <?php echo substr($current_year, -2); ?>
                        </span>)
                    </th>
                </tr>
                <tr class="text-xs text-red-800 bg-red-50 border-b border-gray-300">
                    <th class="px-2 py-2 text-center w-48 border-r border-red-200">ไปราชการ</th>
                    <th class="px-2 py-2 text-center w-48 border-r border-red-200">หนังสือ</th>
                    <th class="px-2 py-2 text-center w-48 border-r border-red-200">คอมฯ</th>
                    <th class="px-2 py-2 text-center w-48 border-r border-red-200">วิทย์</th>
                    <th class="px-2 py-2 text-center font-bold w-32">รวม</th>
                </tr>
            </thead>

            <tbody id="fpaTableBody" class="text-gray-700 text-sm transition-opacity htmx-indicating:opacity-50">
                <?php
                // --- ส่วนแสดงผลครั้งแรก (First Load) ---

                // 1. จำลองค่า $_GET เพื่อให้ Helper เข้าใจ
                $_GET['fiscal_year'] = $current_year;
                $_GET['department_id'] = $current_dept;
                $_GET['query_over_all'] = true;

                // 2. เรียกใช้ Helper (ตรวจสอบ Path ให้ถูกต้องนะครับ ถ้าไฟล์อยู่ src/Helper ก็ต้องถอย path ให้ถึง)
                // สมมติว่าโครงสร้างคือ views/dashboard/tabs/ ไฟล์นี้อยู่ ให้ถอย 3 ชั้น
                if (file_exists(__DIR__ . '/table_summary_FPA.php')) {
                    include __DIR__ . '/../Helper/table_summary_FPA.php';
                } elseif (file_exists(__DIR__ . '/../../src/Helper/table_summary_FPA.php')) {
                    include __DIR__ . '/../../src/Helper/table_summary_FPA.php';
                } else {
                    // Default Path ตามที่คุณแจ้งมา
                    include __DIR__ . '/table_summary_FPA.php';
                }

                ?>
            </tbody>

            <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-400 text-gray-800">
                <tr>
                    <td colspan="2" class="px-4 py-4 text-center border-r border-gray-300">ยอดรวมทั้งสิ้น</td>

                    <td class="px-2 py-4 text-right border-r border-gray-300 border-b-4 border-double border-gray-400">
                        <span id="totalTravel">
                            <?php echo isset($grand_total) ? number_format($sum_travel ?? 0, 2) : '-'; ?>
                        </span>
                    </td>

                    <td class="px-2 py-4 text-right border-r border-gray-300 border-b-4 border-double border-gray-400">
                        <span id="totalBook">
                            <?php echo isset($grand_total) ? number_format($sum_book ?? 0, 2) : '-'; ?>
                        </span>
                    </td>

                    <td class="px-2 py-4 text-right border-r border-gray-300 border-b-4 border-double border-gray-400">
                        <span id="totalComp">
                            <?php echo isset($grand_total) ? number_format($sum_comp ?? 0, 2) : '-'; ?>
                        </span>
                    </td>

                    <td class="px-2 py-4 text-right border-r border-gray-300 border-b-4 border-double border-gray-400">
                        <span id="totalSci">
                            <?php echo isset($grand_total) ? number_format($sum_sci ?? 0, 2) : '-'; ?>
                        </span>
                    </td>

                    <td class="px-2 py-4 text-right text-lg border-b-4 border-double border-gray-400">
                        <span id="grandTotalCell">
                            <?php echo isset($grand_total) ? number_format($grand_total, 2) : '0.00'; ?>
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
    function exportToExcel() {
        // 1. อ้างอิงตารางต้นฉบับ
        const originalTable = document.getElementById("fpaTable");

        // 2. สร้างตารางจำลอง (Clone) ขึ้นมา เพื่อไม่ให้กระทบหน้าเว็บจริง
        const tableClone = originalTable.cloneNode(true);

        // 3. ดึง Select ทั้งหมดจาก "ตารางจริง" และ "ตารางจำลอง"
        const originalSelects = originalTable.querySelectorAll("select");
        const cloneSelects = tableClone.querySelectorAll("select");

        // 4. วนลูปเปลี่ยน Select ในตารางจำลอง ให้เป็นแค่ข้อความ (Text)
        // สาเหตุที่ต้องดึงจาก Original เพราะค่าที่ user เลือก (Selected Index) อยู่ที่ตารางจริง
        originalSelects.forEach((origSelect, index) => {
            const cloneSelect = cloneSelects[index];

            if (cloneSelect) {
                // ดึงข้อความของ Option ที่ถูกเลือกอยู่ ณ ตอนนั้น
                const selectedText = origSelect.options[origSelect.selectedIndex]?.text || "";

                // สร้าง Span มาใส่แทน
                const textSpan = document.createElement("span");
                textSpan.innerText = selectedText; // ใส่ข้อความที่เลือกลงไป

                // ลบ Select ทิ้ง แล้วเอา Span ยัดเข้าไปแทนที่
                cloneSelect.parentNode.replaceChild(textSpan, cloneSelect);
            }
        });

        // 5. ส่งตารางจำลองไปทำ Excel
        const wb = XLSX.utils.table_to_book(tableClone, {
            sheet: "FPA_Summary"
        });

        XLSX.writeFile(wb, "สรุปงบประมาณ_FPA.xlsx");
    }

    function exportToPDF() {
        // 1. ดึงค่าปีงบประมาณที่เลือกอยู่ (จาก Class ที่เราตั้งไว้)
        const yearSelect = document.querySelector('.sync-fiscal-year');
        const selectedYear = yearSelect ? yearSelect.value : '';

        // 2. ดึงค่าภาควิชาที่เลือกอยู่
        const deptSelect = document.querySelector('.sync-depm'); // หรือ input[name='department_id']
        const selectedDept = deptSelect ? deptSelect.value : '';

        // 3. สร้าง URL พร้อมส่งค่าพวกนี้ไปด้วย
        // ส่งไปแบบ GET method (ต่อท้าย URL)
        const url = `index.php?page=show-pdf&fiscal_year=${selectedYear}&department_id=${selectedDept}`;

        // 4. สั่งให้ Browser เปิด URL นี้ (เหมือนการกดลิงก์ดาวน์โหลด)
        window.open(url, '_blank');
    }
</script>