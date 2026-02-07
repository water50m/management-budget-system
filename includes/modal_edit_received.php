<div id="editBudgetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-orange-700">✏️ แก้ไขงบประมาณ</h3>
            <button onclick="closeEditBudgetModal()" class="text-gray-400 hover:text-gray-600">
                ✖
            </button>
        </div>

        <form method="POST" action="index.php?page=dashboard" id="formEditBudget">

            <input type="hidden" name="action" value="edit_budget_received">
            <input type="hidden" name="received_id" id="edit_budget_record_id">
            <input type="hidden" name="user_id" id="edit_budget_user_id">


            <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0  ?>">

            <?php
            $this_page = $_GET['page'];
            $this_tab = isset($_GET['tab']) ? $_GET['tab'] : '';
            ?>
            <input type="hidden" name="submit_page" value="<?= $this_page ?>">
            <input type="hidden" name="submit_tab" value="<?= $this_tab ?>">

            <div class="mb-4 bg-orange-50 p-3 rounded border border-orange-200">
                <p class="text-sm text-gray-600">แก้ไขข้อมูลของ:</p>
                <p class="font-bold text-lg text-orange-800" id="edit_budget_user_name">-</p>
            </div>

            <div class="mb-4">
                <div class="flex justify-between">
                    <label class="block text-gray-700 text-sm font-bold mb-2">จำนวนเงิน</label>
                </div>
                <input type="hidden" name="amount_real" id="edit_amount_hidden" value="">

                <input type="text" id="edit_amount_display" inputmode="decimal" placeholder="ระบุจำนวนเงิน"
                    oninput="handleAmountInputRec(this, 'edit_amount_hidden')"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">วันที่ได้รับอนุมัติ</label>
                <input type="text" id="edit_received_date" name="approved_date"
                    class="flatpickr-thai shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white"
                    placeholder="เลือกวันที่..." required readonly>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด / หมายเหตุ</label>
                <textarea id="edit_remark" name="remark" rows="2"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="ระบุเหตุผลการแก้ไข..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditBudgetModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">ยกเลิก</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition font-bold shadow-lg">
                        💾 บันทึก
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    function openEditBudgetReceivedModal(recordId, userId, name, amount, date, remark, isUsed) {

        document.getElementById('edit_budget_record_id').value = recordId;
        document.getElementById('edit_budget_user_id').value = userId;
        document.getElementById('edit_budget_user_name').innerText = name;

        const amountHidden = document.getElementById('edit_amount_hidden');
        const amountDisplay = document.getElementById('edit_amount_display');

        amountHidden.value = amount;

        amountDisplay.value = Number(amount).toLocaleString('th-TH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        console.log('let mes ee');
        console.log(document.getElementById('edit_amount_hidden').value);
        if (isUsed) {
            amountDisplay.setAttribute('readonly', true);
            amountDisplay.classList.add('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');

            amountDisplay.onmouseenter = () =>
                showGlobalAlert('⚠️ ไม่สามารถแก้ไขยอดเงินได้: งบประมาณถูกใช้ไปแล้ว');

            amountDisplay.onmouseleave = hideGlobalAlert;

        } else {
            amountDisplay.removeAttribute('readonly');
            amountDisplay.classList.remove('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');
            amountDisplay.onmouseenter = null;
            amountDisplay.onmouseleave = null;
        }

        const dateInput = document.getElementById('edit_received_date');
        if (dateInput?._flatpickr) {
            dateInput._flatpickr.setDate(date, true);
        } else if (dateInput) {
            dateInput.value = date;
        }

        document.getElementById('edit_remark').value = remark;

        document.getElementById('editBudgetModal').classList.remove('hidden');
    }

    function closeEditBudgetModal() {
        document.getElementById('editBudgetModal').classList.add('hidden');
    }

    // ❌ ฟังก์ชัน resetToOriginal() ถูกลบออกไปแล้ว
    // Utility สำหรับ Input เงิน
    function handleAmountInputRec(el, hiddenId) {
        // 1. ลบ Commas เดิมออกก่อน
        let rawValue = el.value.replace(/,/g, '');

        // 2. กรองให้เหลือแค่ตัวเลข (0-9) เท่านั้น (ตัดจุด . ทิ้งด้วย)
        rawValue = rawValue.replace(/[^0-9]/g, '');

        // 3. อัปเดตค่าลง Hidden Input (ค่าดิบไม่มีลูกน้ำ)
        if (hiddenId) {
            document.getElementById(hiddenId).value = rawValue;
        }

        // 4. ถ้าไม่มีค่า ให้จบเลย
        if (rawValue === '') {
            el.value = '';
            return;
        }

        // 5. แปลงค่าเพื่อแสดงผล (ใส่ Comma อย่างเดียว ไม่ต้องสนทศนิยม)
        el.value = rawValue.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    document.getElementById('formEditBudget').addEventListener('submit', () => {
        // console.log เช็คค่าได้ถ้าต้องการ
    });

    // Initialize Flatpickr for Edit Modal
    document.addEventListener('DOMContentLoaded', function() {
        const setBuddhistYear = (instance) => {
            if (instance.currentYearElement) {
                instance.currentYearElement.value = instance.currentYear + 543;
            }
        };

        flatpickr("#edit_received_date", {
            locale: "th",
            dateFormat: "Y-m-d", // ✅ แนะนำใช้ Y-m-d เพื่อส่งค่ามาตรฐานเข้า DB (หรือใช้ m/d/y ตามเดิมถ้าหลังบ้านรับ format นั้น)
            altInput: true,
            altFormat: "j F Y",
            disableMobile: true,
            onReady: (d, dStr, instance) => setBuddhistYear(instance),
            onOpen: (d, dStr, instance) => setBuddhistYear(instance),
            onMonthChange: (d, dStr, instance) => setBuddhistYear(instance),
            onYearChange: (d, dStr, instance) => setBuddhistYear(instance),
            formatDate: (date, format, locale) => {
                if (format === "j F Y") {
                    return flatpickr.formatDate(date, "j F", locale) + " " + (date.getFullYear() + 543);
                }
                return flatpickr.formatDate(date, format, locale);
            }
        });
    });
</script>