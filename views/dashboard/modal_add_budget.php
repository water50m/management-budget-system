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
            <?php $this_page = $_GET['page']?>
            <?php $this_tab = isset($_GET['tab']) ? $_GET['tab'] : '' ;?>
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

                <input type="text" inputmode="decimal" placeholder="Min"
                    value=""
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                    oninput="formatCurrency(this, 'add_amount_hidden')"></input>

            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">วันที่ได้รับอนุมัติ</label>
                <input type="date" id="budget_date" name="approved_date"
                    oninput="checkManualDate(this, 'use_today_budget')"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                <div class="mt-2 flex items-center">
                    <input type="checkbox" id="use_today_budget"
                        onclick="toggleTodayDate(this, 'budget_date')"
                        class="w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500 cursor-pointer">
                    <label for="use_today_budget" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">
                        ใช้วันที่ปัจจุบัน
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด / ชื่อโครงการ</label>
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
</script>