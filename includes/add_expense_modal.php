<?php 
if (!isset($cats_list)){
$cats_list = $data['categories_list'];
}

?>
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
            <input type="hidden" name="submit_page" value="<?= $_GET['page'] ?>">
            <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : ''  ?>">
            <input type="hidden" name="target_user_id" id="modalUserId" value="">
            <input type="hidden" name="target_name" id="modalFullName" value="">
            <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0  ?>">

            <div class="space-y-3">
                <div class="mb-4">
                    <div class="flex justify-between items-end mb-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            วันที่อนุมัติ (ตามเอกสาร)
                        </label>
                        <button type="button" data-target="approved_date"
                            class="btn-use-today text-xs font-medium text-green-600 hover:text-green-800 hover:underline cursor-pointer flex items-center gap-1 transition-colors">
                            <i class="fa-regular fa-calendar-check"></i> คลิกเพื่อใช้วันที่ปัจจุบัน
                        </button>
                    </div>
                    <input type="text"
                        id="approved_date"
                        name="approved_date"
                        class="flatpickr-thai shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="เลือกวันที่..."
                        required readonly>


                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ประเภทการใช้เงิน</label>
                    <select name="category_id" required class="w-full border border-gray-300 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        <?php foreach ($cats_list as $cat): ?>
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
        calculateRealtimeBalance(0); 
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

    function handleAmountInput(input) {
    // 1. ล้างค่าที่ไม่ใช่ตัวเลขและจุดทศนิยมออก (ป้องกันคนพิมพ์ตัวอักษร)
    let value = input.value.replace(/[^0-9.]/g, '');
    
    // ป้องกันจุดทศนิยมซ้ำ
    const parts = value.split('.');
    if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');

    // 2. เก็บค่าตัวเลขจริงลงใน Hidden Input เพื่อส่งไป PHP
    const realValue = parseFloat(value) || 0;
    document.getElementById('inputAmountReal').value = realValue;

    // 3. ทำ Format สำหรับแสดงผลในช่อง Input (เฉพาะตอนที่ไม่ได้พิมพ์จุดค้างไว้)
    if (value !== "" && !value.endsWith('.')) {
        input.value = new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 0, // ไม่บังคับ .00 ขณะพิมพ์เพื่อให้พิมพ์ถนัด
            maximumFractionDigits: 2
        }).format(realValue);
        
        // ถ้ามีทศนิยม ให้เติมทศนิยมกลับเข้าไป (เพราะ Intl.NumberFormat อาจจะตัดออกขณะกำลังพิมพ์)
        if (value.includes('.')) {
            const decimalPart = value.split('.')[1];
            if (decimalPart !== undefined) {
                // ถ้ามีเลขหลังจุดแล้ว ไม่ต้องทำอะไรเพิ่ม แต่ถ้าเพิ่งพิมพ์จุด Intl จะจัดการให้
            }
        }
    } else {
        input.value = value; // แสดงค่าดิบถ้ากำลังพิมพ์จุด หรือค่าว่าง
    }

    // 4. คำนวณยอดคงเหลือใหม่
    calculateRealtimeBalance(realValue);
}


// ฟังก์ชันคำนวณสด (Real-time)
function calculateRealtimeBalance(realValue) {
    const inputVal = realValue;
    const amountToCut = parseFloat(inputVal) || 0;
    
        
    // คำนวณยอดใหม่ โดยใช้ตัวแปรกลางที่จำค่า 18,000 ไว้
    const newBalance = currentOriginalBalance - amountToCut;

    // ส่งค่าไปอัปเดต โดยฝั่งซ้ายต้องเป็น currentOriginalBalance เสมอ!
    updateBalanceUI(currentOriginalBalance, newBalance);
}

// ฟังก์ชันช่วยจัดการตัวเลขและสีสัน
function updateBalanceUI(originalBal, nextBal) {
    const formatter = new Intl.NumberFormat('th-TH', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });

    const oldDisplay = document.getElementById('modalBalanceDisplay');
    const newDisplay = document.getElementById('modalNewBalanceDisplay');

    // จุดสำคัญ: ฝั่งซ้ายต้องโชว์ยอดเดิม (originalBal) ห้ามเปลี่ยนเป็น 0
    if (oldDisplay) oldDisplay.innerText = formatter.format(originalBal) + ' บาท';
    
    if (newDisplay) {
        newDisplay.innerText = formatter.format(nextBal) + ' บาท';
        
        // เช็คสีเตือน
        if (nextBal < 0) {
            newDisplay.classList.replace('text-blue-700', 'text-red-600');
        } else {
            newDisplay.classList.replace('text-red-600', 'text-blue-700');
        }
    }
}

</script>