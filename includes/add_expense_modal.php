<?php 
if (!isset($cats_list)){
$cats_list = $data['categories_list'];
}
?>
<div id="expenseModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4 transform transition-all scale-100 max-h-[90vh] overflow-y-auto">

        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">
                📝 บันทึกรายจ่าย
                <span class="block text-sm text-blue-600 font-normal mt-1" id="modalUserName">กำลังโหลด...</span>
            </h3>
            <button onclick="closeExpenseModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
        </div>

        <form method="POST" action="index.php?page=dashboard" enctype="multipart/form-data">

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
            <input type="hidden" name="submit_page" value="<?= isset($_GET['page']) ? $_GET['page'] : '' ?>">
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

                <div class="cursor-pointer">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">แนบรูปภาพเอกสาร/ใบเสร็จ (ถ้ามี)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-green-500 transition-colors relative bg-gray-50 group">
                        
                        <div class="space-y-1 text-center" id="uploadPlaceholder">
                            <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-green-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="receipt_image" class="relative cursor-pointer bg-transparent rounded-md font-medium text-green-600 hover:text-green-800 focus-within:outline-none">
                                    <span>คลิกเพื่ออัปโหลดรูปภาพ</span>
                                    <input id="receipt_image" name="receipt_image" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500">รองรับ PNG, JPG, JPEG (ขนาดไม่เกิน 5MB)</p>
                        </div>

                        <div id="imagePreviewContainer" class="hidden absolute inset-0 p-2 bg-white rounded-lg flex flex-col items-center justify-center border-2 border-green-400">
                            <img id="imagePreview" src="" alt="Preview" class="max-h-full max-w-full object-contain rounded">
                            <button type="button" onclick="removeImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 focus:outline-none shadow-md">
                                &times;
                            </button>
                        </div>
                    </div>
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
    let currentOriginalBalance = 0; // ย้ายออกมาประกาศข้างนอกเพื่อให้ฟังก์ชันอื่นใช้ได้สะดวก

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
        
        // เคลียร์ค่ารูปภาพเวลาปิด Modal
        removeImage(); 
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

        // 3. ทำ Format สำหรับแสดงผลในช่อง Input
        if (value !== "" && !value.endsWith('.')) {
            input.value = new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 0, 
                maximumFractionDigits: 2
            }).format(realValue);
            
            if (value.includes('.')) {
                const decimalPart = value.split('.')[1];
                // รักษาจุดทศนิยมไว้เวลาพิมพ์
            }
        } else {
            input.value = value; 
        }

        // 4. คำนวณยอดคงเหลือใหม่
        calculateRealtimeBalance(realValue);
    }

    // ฟังก์ชันคำนวณสด (Real-time)
    function calculateRealtimeBalance(realValue) {
        const inputVal = realValue;
        const amountToCut = parseFloat(inputVal) || 0;
        const newBalance = currentOriginalBalance - amountToCut;
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

    // -------------------------------------------------------------
    // 🌟 ฟังก์ชันจัดการรูปภาพ (Image Preview) 🌟
    // -------------------------------------------------------------
    function previewImage(input) {
        const previewContainer = document.getElementById('imagePreviewContainer');
        const previewImage = document.getElementById('imagePreview');
        const placeholder = document.getElementById('uploadPlaceholder');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden'); // โชว์รูป
                placeholder.classList.add('hidden'); // ซ่อนกล่องอัปโหลด
            }
            
            reader.readAsDataURL(input.files[0]); // อ่านไฟล์เป็น Base64 เพื่อมาโชว์
        }
    }

    function removeImage() {
        const input = document.getElementById('receipt_image');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const previewImage = document.getElementById('imagePreview');
        const placeholder = document.getElementById('uploadPlaceholder');

        if(input) input.value = ''; // เคลียร์ไฟล์ที่เลือก
        if(previewImage) previewImage.src = ''; // เคลียร์รูป
        if(previewContainer) previewContainer.classList.add('hidden'); // ซ่อนกรอบโชว์รูป
        if(placeholder) placeholder.classList.remove('hidden'); // โชว์กล่องอัปโหลดกลับมา
    }
</script>