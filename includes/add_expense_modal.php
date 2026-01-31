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
            <input type="hidden" name="target_name" id="modalFullName" value="">

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

<script>
    function openExpenseModal(userId, userName, balance) {
        // 1. ใส่ค่าลง Form
        const idInput = document.getElementById('modalUserId');
        const nameSpan = document.getElementById('modalUserName');

        // [ใหม่] จุดแสดงยอดเงิน
        const balanceDisplay = document.getElementById('modalBalanceDisplay');
        currentOriginalBalance = balance;

        if (idInput) idInput.value = userId;
        if (nameSpan) nameSpan.innerText = '👤 สำหรับ: ' + userName;

        // [ใหม่] อัปเดตตัวเลขเงินคงเหลือ (จัด Format มีลูกน้ำ)
        if (balanceDisplay) {
            // แปลงเลขเป็น format เงิน (เช่น 10,000.00)
            let formattedBalance = new Intl.NumberFormat('th-TH', {
                style: 'decimal',
                minimumFractionDigits: 2
            }).format(balance);

            balanceDisplay.innerText = formattedBalance + ' บาท';
        }

        document.getElementById('modalFullName').value = userName;
        // 2. แสดง Modal
        const modal = document.getElementById('expenseModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeExpenseModal() {
        const modal = document.getElementById('expenseModal');
        if (modal) modal.classList.add('hidden');
    }

    // ปิด Modal เมื่อกดพื้นที่สีดำข้างนอก
    window.onclick = function(event) {
        const modal = document.getElementById('expenseModal');
        if (event.target == modal) {
            closeExpenseModal();
        }
    }
</script>