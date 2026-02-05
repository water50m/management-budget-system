<div id="addBudgetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-green-700">💰 เพิ่มงบประมาณ (รับเงิน)</h3>
            <button onclick="closeAddBudgetModal()" class="text-gray-400 hover:text-gray-600">
                ✖
            </button>
        </div>

        <form method="POST" action="index.php?page=dashboard">
            <input type="hidden" name="action" value="add_budget">
            <input type="hidden" name="user_id" id="add_budget_user_id">
            <?php $this_page = $_GET['page'] ?>
            <?php $this_tab = isset($_GET['tab']) ? $_GET['tab'] : ''; ?>
            <input type="hidden" name="submin_page" value="<?= $this_page ?>">
            <input type="hidden" name="submin_tab" value="<?= $this_tab ?>">
            <div class="mb-4 bg-green-50 p-3 rounded border border-green-200">
                <p class="text-sm text-gray-600">กำลังเพิ่มงบให้:</p>
                <p class="font-bold text-lg text-green-800" id="add_budget_user_name">-</p>
                <input type="hidden" name="target_full_name" id="add_budget_full_name">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">จำนวนเงินที่ได้รับ</label>

                <input type="hidden" name="amount" id="add_amount_hidden"
                    value="">

                <input type="text" inputmode="decimal" placeholder="ระบุจำนวนเงิน"
                    value=""
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                    oninput="formatCurrency(this, 'add_amount_hidden')"></input>

            </div>

            <div class="mb-4">
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-gray-700 text-sm font-bold">วันที่ได้รับอนุมัติ</label>

                    <button type="button" data-target="budget_date"
                        class="btn-use-today text-xs font-medium text-green-600 hover:text-green-800 hover:underline cursor-pointer flex items-center gap-1 transition-colors">
                        <i class="fa-regular fa-calendar-check"></i> คลิกเพื่อใช้วันที่ปัจจุบัน
                    </button>
                </div>

                <input type="text" id="budget_date" name="approved_date"
                    class="flatpickr-thai shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 bg-white"
                    placeholder="เลือกวันที่..." required readonly>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด / หมายเหตุ</label>
                <textarea name="remark" rows="2" class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="เช่น งบวิจัยงวดที่ 1 ปี 2569..."></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAddBudgetModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition font-bold shadow-lg">
                    💾 บันทึกรับเงิน
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddBudgetModal(userId, userName) {
        document.getElementById('add_budget_user_id').value = userId;
        document.getElementById('add_budget_user_name').innerText = userName;
        document.getElementById('add_budget_full_name').value = userName;
        document.getElementById('addBudgetModal').classList.remove('hidden');
    }

    function closeAddBudgetModal() {
        document.getElementById('addBudgetModal').classList.add('hidden');
    }


    document.addEventListener('DOMContentLoaded', function() {
        // ฟังก์ชันช่วยปรับปีในหัวปฏิทินให้เป็น พ.ศ.
        const setBuddhistYear = (instance) => {
            if (instance.currentYearElement) {
                const buddhistYear = instance.currentYear + 543;
                instance.currentYearElement.value = buddhistYear;
            }
        };

        const fp = flatpickr("#budget_date", {
            locale: "th",

            // ✅ เปลี่ยนตรงนี้: กำหนด Value เป็น mm/dd/yy (ปี ค.ศ. 2 หลัก)
            // ถ้าอยากได้ปี 4 หลัก (2026) ให้ใช้ "m/d/Y"
            dateFormat: "m/d/y",

            altInput: true, // เปิดใช้ช่องแสดงผลหลอก
            altFormat: "j F Y", // รูปแบบที่ตาเห็น (ยังเป็น พ.ศ. เต็ม)
            disableMobile: true,

            // Event Hooks สำหรับแก้ปี พ.ศ. ในปฏิทิน
            onReady: (d, dStr, instance) => setBuddhistYear(instance),
            onOpen: (d, dStr, instance) => setBuddhistYear(instance),
            onMonthChange: (d, dStr, instance) => setBuddhistYear(instance),
            onYearChange: (d, dStr, instance) => setBuddhistYear(instance),

            // แปลงวันที่สำหรับแสดงผล (altFormat) ให้เป็นปี พ.ศ.
            formatDate: (date, format, locale) => {
                if (format === "j F Y") {
                    return flatpickr.formatDate(date, "j F", locale) + " " + (date.getFullYear() + 543);
                }
                return flatpickr.formatDate(date, format, locale);
            }
        });

        // ปุ่ม "ใช้วันที่ปัจจุบัน"
        const btnUseToday = document.getElementById('btn_use_today');
        if (btnUseToday) {
            btnUseToday.addEventListener('click', function() {
                fp.setDate(new Date(), true);

                // Effect กระพริบ
                const input = document.querySelector(".flatpickr-input[type='text']");
                if (input) {
                    input.classList.add('ring-2', 'ring-green-500');
                    setTimeout(() => input.classList.remove('ring-2', 'ring-green-500'), 300);
                }
            });
        }
    });
</script>