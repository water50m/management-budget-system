<?php 
if (!isset($cats_list)){
$cats_list = $data['categories_list'] ;
}
?>
<div id="editExpenseModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4 transform transition-all scale-100 border-t-4 border-orange-500">

        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">
                ✏️ แก้ไขรายการใช้จ่าย
                <span class="block text-sm text-orange-600 font-normal mt-1" id="editModalUserName">กำลังโหลด...</span>
            </h3>
            <button onclick="closeEditExpenseModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>

        <form method="POST" action="index.php?page=dashboard" id="formEditExpense">
            <input type="hidden" name="action" value="edit_budget_expense">
            <input type="hidden" name="expense_id" id="edit_expense_id">
            <input type="hidden" name="target_user_id" id="edit_modal_user_id">

            <input type="hidden" id="default_exp_amount" value="">
            <input type="hidden" id="default_exp_date" value="">
            <input type="hidden" id="default_exp_category" value="">
            <input type="hidden" id="default_exp_description" value="">

            <input type="hidden" name="submit_page" value="<?= $_GET['page'] ?>">
            <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">
            <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">จำนวนเงิน (บาท)</label>
                <input type="text"
                    id="editInputAmountDisplay"
                    placeholder="0.00"
                    required
                    oninput="handleAmountInputExp(this, 'editInputAmountReal')"
                    inputmode="decimal"
                    class="w-full border border-gray-300 rounded-lg p-2.5 text-right font-mono text-lg font-bold text-orange-700 focus:ring-2 focus:ring-orange-500 outline-none">
                <input type="hidden" name="amount" id="editInputAmountReal">
            </div>

            <div class="space-y-3">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">วันที่จ่าย (ตามเอกสาร)</label>
                    <input type="text"
                        id="edit_expense_date"
                        name="approved_date"
                        class="flatpickr-thai shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white"
                        placeholder="เลือกวันที่..."
                        required readonly>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ประเภทการใช้เงิน</label>
                    <select id="edit_category_id" name="category_id" required class="w-full border border-gray-300 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-orange-500 outline-none">
                        <?php foreach ($cats_list  as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_th']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">รายละเอียด</label>
                    <input type="text" id="edit_description" name="description" placeholder="ระบุรายละเอียด..."
                        class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
            </div>

            <div class="mt-6 flex justify-between gap-3 pt-4 border-t">
                <button type="button" onclick="resetExpenseToOriginal()"
                    class="px-3 py-2 text-yellow-600 hover:text-yellow-800 text-sm font-bold transition flex items-center gap-1">
                    <i class="fas fa-undo"></i> คืนค่าเดิม
                </button>

                <div class="flex gap-2">
                    <button type="button" onclick="closeEditExpenseModal()" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 shadow-lg transition-all">
                        💾 บันทึกการแก้ไข
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    // ฟังก์ชันเปิด Modal และจำค่า Default
    function openEditExpenseModal(expId, userId, userName, amount, date, catId, desc) {
        // 1. Set Hidden IDs & User Info
        document.getElementById('edit_expense_id').value = expId;
        document.getElementById('edit_modal_user_id').value = userId;
        const nameLabel = document.getElementById('editModalUserName');
        if (nameLabel) nameLabel.innerText = '👤 แก้ไขรายการของ: ' + userName;

        // ----------------------------------------------------
        // ✅ STEP 1: จำค่าเดิมไว้ (Store Defaults)
        // ----------------------------------------------------
        document.getElementById('default_exp_amount').value = amount;
        document.getElementById('default_exp_date').value = date;
        document.getElementById('default_exp_category').value = catId;
        document.getElementById('default_exp_description').value = desc;

        // ----------------------------------------------------
        // ✅ STEP 2: แสดงผลค่าปัจจุบัน
        // ----------------------------------------------------

        // Set Amount
        document.getElementById('editInputAmountReal').value = amount;
        document.getElementById('editInputAmountDisplay').value = Number(amount).toLocaleString('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Set Date (แก้ไข Bug ชื่อตัวแปร dateStr -> date)
        const dateInput = document.getElementById('edit_expense_date'); // ID ตรงกับ HTML แล้ว
        if (dateInput) {
            if (dateInput._flatpickr) {
                dateInput._flatpickr.setDate(date, true);
            } else {
                dateInput.value = date;
            }
        }

        // Set Category and Description
        document.getElementById('edit_category_id').value = catId;
        document.getElementById('edit_description').value = desc;

        // Show Modal
        document.getElementById('editExpenseModal').classList.remove('hidden');
    }

    function closeEditExpenseModal() {
        document.getElementById('editExpenseModal').classList.add('hidden');
    }

    // ✅ ฟังก์ชันคืนค่าเดิม (Reset Function)
    function resetExpenseToOriginal() {
        // 1. ดึงค่าเดิม
        const defAmount = document.getElementById('default_exp_amount').value;
        const defDate = document.getElementById('default_exp_date').value;
        const defCat = document.getElementById('default_exp_category').value;
        const defDesc = document.getElementById('default_exp_description').value;

        // 2. คืนค่า Amount
        document.getElementById('editInputAmountReal').value = defAmount;
        document.getElementById('editInputAmountDisplay').value = Number(defAmount).toLocaleString('th-TH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });

        // 3. คืนค่า Date
        const dateInput = document.getElementById('edit_expense_date');
        if (dateInput && dateInput._flatpickr) {
            dateInput._flatpickr.setDate(defDate, true);
        }

        // 4. คืนค่า Category & Description
        document.getElementById('edit_category_id').value = defCat;
        document.getElementById('edit_description').value = defDesc;

        // Effect
        const form = document.getElementById('formEditExpense');
        form.classList.add('opacity-50');
        setTimeout(() => form.classList.remove('opacity-50'), 200);
    }

    // Utility สำหรับ Input เงิน
    function handleAmountInputExp(el, hiddenId) {
        // 1. ลบ Commas เดิมออกก่อน เพื่อให้ได้ค่าดิบ (เช่น "1,234" -> "1234")
        let rawValue = el.value.replace(/,/g, '');

        // 2. กรองให้เหลือแค่ตัวเลข (0-9) และจุด (.) เท่านั้น
        // ป้องกันกรณี User พิมพ์ตัวหนังสือเข้ามา
        rawValue = rawValue.replace(/[^0-9.]/g, '');

        // 3. ป้องกันกรณีมีจุดหลายจุด (เช่น 10.5.5 -> 10.55)
        // โดยการแยกส่วนด้วยจุด แล้วเอาเฉพาะส่วนแรกกับส่วนที่สองมาต่อกัน
        let parts = rawValue.split('.');
        if (parts.length > 2) {
            // เชื่อมส่วนแรกกับส่วนที่เหลือที่ไม่มีจุด
            rawValue = parts[0] + '.' + parts.slice(1).join('');
            parts = rawValue.split('.'); // split ใหม่เพื่อให้ parts ถูกต้อง
        }

        // 4. อัปเดตค่าลง Hidden Input (ค่าดิบสำหรับส่งเข้า DB)
        document.getElementById(hiddenId).value = rawValue;

        // 5. แปลงค่าเพื่อแสดงผล (ใส่ Comma)
        // ถ้าไม่มีค่า ให้จบเลย
        if (rawValue === '') {
            el.value = '';
            return;
        }

        // แยกส่วนจำนวนเต็ม (Integer)
        let integerPart = parts[0];

        // แยกส่วนทศนิยม (Decimal) - ถ้ามีจุด ให้เก็บจุดและตัวเลขหลังจุดไว้
        let decimalPart = parts.length > 1 ? '.' + parts[1] : '';

        // ใส่ Comma ให้ส่วนจำนวนเต็ม (ใช้ Regex มาตรฐาน)
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        // 6. นำค่ากลับไปใส่ Input ที่แสดงผล
        el.value = integerPart + decimalPart;
    }

    // Initialize Flatpickr (ใช้ ID ให้ตรงกัน)
    document.addEventListener('DOMContentLoaded', function() {
        const editDateEl = document.getElementById("edit_expense_date");
        if (editDateEl) {
            flatpickr(editDateEl, {
                locale: "th",
                dateFormat: "Y-m-d", // แนะนำให้ใช้ format มาตรฐานส่งเข้า DB
                altInput: true,
                altFormat: "j F Y",
                // ... ใส่ logic Buddhist Year ของคุณที่นี่ ...
                onReady: function(selectedDates, dateStr, instance) {
                    // (โค้ดแปลง พ.ศ. ของคุณ)
                }
            });
        }
    });
</script>