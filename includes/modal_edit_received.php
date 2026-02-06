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

            <input type="hidden" id="default_amount" value="">
            <input type="hidden" id="default_date" value="">
            <input type="hidden" id="default_remark" value="">

            <input type="hidden" name="submit_page" value="<?= $_GET['page'] ?>">
            <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : ''  ?>">
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
                <input type="hidden" name="amount" id="edit_amount_hidden" value="">
                <input type="text" id="edit_amount_display" inputmode="decimal" placeholder="ระบุจำนวนเงิน" 
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    oninput="formatCurrency(this, 'edit_amount_hidden')">
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

            <div class="flex justify-between gap-2 pt-2 border-t">
                <button type="button" onclick="resetToOriginal()"
                    class="px-3 py-2 text-yellow-600 hover:text-yellow-800 text-sm font-bold transition flex items-center gap-1">
                    <i class="fas fa-undo"></i> คืนค่าเดิม
                </button>

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

        // 1. Fill IDs and Text
        document.getElementById('edit_budget_record_id').value = recordId;
        document.getElementById('edit_budget_user_id').value = userId;
        document.getElementById('edit_budget_user_name').innerText = name;

        // ----------------------------------------------------
        // ✅ STEP 1: จำค่าเดิมไว้ใน Hidden Input หรือตัวแปร
        // ----------------------------------------------------
        document.getElementById('default_amount').value = amount;
        document.getElementById('default_date').value = date;
        document.getElementById('default_remark').value = remark;

        // ----------------------------------------------------
        // ✅ STEP 2: แสดงผลค่าปัจจุบัน (เหมือนโค้ดเดิม)
        // ----------------------------------------------------

        // Amount
        const amountHidden = document.getElementById('edit_amount_hidden');
        const amountDisplay = document.getElementById('edit_amount_display');
        if (amountHidden) amountHidden.value = amount;
        if (amountDisplay) amountDisplay.value = Number(amount).toLocaleString('th-TH', {
            minimumFractionDigits: 2
        });

        if (amountHidden) {
            amountHidden.value = amount; // เก็บค่าดิบ 45000.00
        }

        if (amountDisplay) {
            // จัดรูปแบบให้มีลูกน้ำ แล้วใส่ลงในช่องแสดงผล
            amountDisplay.value = Number(amount).toLocaleString('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }


        if (isUsed) {
            // 1. สั่ง Readonly
            amountDisplay.setAttribute('readonly', 'true');

            // 2. เปลี่ยนสีให้ดูเหมือน Disabled (สีเทา + เมาส์ห้ามจอด)
            amountDisplay.classList.add('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');

            // 3. ฝัง Event mouseenter/mouseleave เพื่อโชว์ Alert
            amountDisplay.onmouseenter = function() {
                showGlobalAlert('⚠️ ไม่สามารถแก้ไขยอดเงินได้: งบประมาณบางส่วน หรือทั้งหมดของรายการนี้ถูกใช้ไปแล้ว');
            };
            amountDisplay.onmouseleave = function() {
                hideGlobalAlert();
            };
        } else {
            // กรณีแก้ไขได้ปกติ (ต้องเคลียร์ค่าเดิมออก เผื่อเปิด Modal ซ้ำ)
            amountDisplay.removeAttribute('readonly');
            amountDisplay.classList.remove('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');
            
            // ลบ Event ทิ้ง
            amountDisplay.onmouseenter = null;
            amountDisplay.onmouseleave = null;
        }

        // Date
        const dateInput = document.getElementById('edit_received_date');
        if (dateInput) {
            if (dateInput._flatpickr) {
                dateInput._flatpickr.setDate(date, true);
            } else {
                dateInput.value = date;
            }
        }

        // ตรวจสอบว่า Input นี้ถูกทำให้เป็น Flatpickr หรือยัง
        if (dateInput) {
            if (dateInput._flatpickr) {
                // ✅ แก้จาก dateStr เป็น date (ตามตัวแปรที่รับมาบรรทัดแรก)
                dateInput._flatpickr.setDate(date, true);
            } else {
                // กรณี Flatpickr ยังไม่โหลด ให้ใส่ค่า value ปกติไปก่อน
                dateInput.value = date;
            }
        } else {
            console.error("❌ ไม่พบ Input ID: edit_received_date กรุณาเช็ค HTML");
        }

        // 4. Fill Remark
        document.getElementById('edit_remark').value = remark;

        // 5. Show Modal
        document.getElementById('editBudgetModal').classList.remove('hidden');
    }

    function closeEditBudgetModal() {
        document.getElementById('editBudgetModal').classList.add('hidden');
    }

    function resetToOriginal() {
        // 1. ดึงค่าเดิมจากที่จำไว้
        const defAmount = document.getElementById('default_amount').value;
        const defDate = document.getElementById('default_date').value;
        const defRemark = document.getElementById('default_remark').value;

        // 2. คืนค่า Amount (ทั้งตัวโชว์และตัวซ่อน)
        document.getElementById('edit_amount_hidden').value = defAmount;
        document.getElementById('edit_amount_display').value = Number(defAmount).toLocaleString('th-TH', {
            minimumFractionDigits: 2
        });

        // 3. คืนค่า Date (ผ่าน Flatpickr)
        const dateInput = document.getElementById('edit_received_date');
        if (dateInput && dateInput._flatpickr) {
            dateInput._flatpickr.setDate(defDate, true); // true = trigger change event
        }

        // 4. คืนค่า Remark
        document.getElementById('edit_remark').value = defRemark;

        // (Optional) ใส่ Effect ให้รู้ว่าคืนค่าแล้ว (สั่น หรือ กระพริบ)
        const form = document.getElementById('formEditBudget');
        form.classList.add('opacity-50');
        setTimeout(() => form.classList.remove('opacity-50'), 200);
    }

    // Initialize Flatpickr for Edit Modal
    document.addEventListener('DOMContentLoaded', function() {
        const setBuddhistYear = (instance) => {
            if (instance.currentYearElement) {
                instance.currentYearElement.value = instance.currentYear + 543;
            }
        };

        editFp = flatpickr("#edit_received_date", {
            locale: "th",
            dateFormat: "m/d/y",
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