<div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden overflow-y-auto h-full w-full z-[100] backdrop-blur-sm transition-opacity flex items-center justify-center">

    <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl transform transition-all scale-100 mx-4 border border-gray-100">
        
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 animate-bounce-slow shadow-sm">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            
            <h3 class="text-xl font-bold text-gray-800 mb-1">ยืนยันการลบข้อมูล?</h3>
            
            <div class="bg-gray-50 rounded-lg p-2 mt-2 border border-gray-100">
                <p class="text-sm text-gray-500">คุณกำลังจะลบ:</p>
                <p id="delete_item_name" class="text-base font-semibold text-red-600 truncate px-2">...</p>
            </div>
        </div>

        <form id="deleteForm" method="POST" action="index.php?page=dashboard" class="px-6 pb-6">
            
            <input type="hidden" name="action" id="modal_action_value" value="">
            <input type="hidden" name="id_to_delete" id="modal_id_to_delete" value="">
            
            <input type="hidden" name="submit_page" value="<?php echo $_GET['page'] ?? 'dashboard'; ?>">
            <input type="hidden" name="submit_tab" value="<?php echo $_GET['tab'] ?? ''; ?>">

            <div class="mt-2">
                <label class="block text-sm text-gray-600 mb-2 text-center">
                    พิมพ์คำว่า <span class="font-bold text-gray-800 bg-gray-200 px-2 py-0.5 rounded border border-gray-300 select-all">ลบข้อมูลนี้</span> เพื่อยืนยัน
                </label>
                <input type="text" id="confirm_text_input" 
                    oninput="checkDeleteMatch()"
                    class="w-full px-4 py-2 text-center border-2 border-gray-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100 transition-all font-medium placeholder-gray-400"
                    placeholder="พิมพ์ที่นี่..." autocomplete="off">
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold transition duration-200 border border-gray-200">
                    ยกเลิก
                </button>

                <button id="btn_real_delete" type="submit" disabled
                    class="flex-1 px-4 py-2.5 bg-gray-300 text-white font-bold rounded-lg cursor-not-allowed transition-all duration-300 shadow-sm flex items-center justify-center gap-2">
                    <span>ลบทันที</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. ฟังก์ชันเปิด Modal (นี่คือหัวใจหลัก)
    // id: ID ของข้อมูลใน Database
    // actionType: ประเภทการลบ (เช่น 'delete_budget', 'delete_expense')
    // itemName: ชื่อรายการที่จะลบ (เอาไว้โชว์ให้ user มั่นใจ)
    function openDeleteModal(id, actionType, itemName = 'รายการนี้') {
        
        // ก. อัปเดตค่าลงใน Hidden Input
        document.getElementById('modal_id_to_delete').value = id;
        document.getElementById('modal_action_value').value = actionType;
        
        // ข. อัปเดตชื่อรายการบนหน้าจอ
        document.getElementById('delete_item_name').textContent = itemName;

        // ค. เปิด Modal
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');

        // ง. Reset ช่องพิมพ์และปุ่ม
        document.getElementById('confirm_text_input').value = '';
        checkDeleteMatch();

        // จ. Focus ช่องพิมพ์ (Delay นิดนึงเพื่อให้ Animation จบก่อน)
        setTimeout(() => {
            document.getElementById('confirm_text_input').focus();
        }, 100);
    }

    // 2. ฟังก์ชันปิด Modal
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // 3. ฟังก์ชันเช็คคำว่า "ลบข้อมูลนี้"
    function checkDeleteMatch() {
        const input = document.getElementById('confirm_text_input');
        const btn = document.getElementById('btn_real_delete');
        const keyword = 'ลบข้อมูลนี้';

        if (input.value === keyword) {
            // ✅ พิมพ์ถูก -> ปุ่มแดง กดได้
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'cursor-not-allowed', 'text-gray-500');
            btn.classList.add('bg-red-600', 'hover:bg-red-700', 'shadow-lg', 'transform', 'hover:scale-[1.02]');
        } else {
            // ❌ พิมพ์ผิด -> ปุ่มเทา กดไม่ได้
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'cursor-not-allowed', 'text-gray-500');
            btn.classList.remove('bg-red-600', 'hover:bg-red-700', 'shadow-lg', 'transform', 'hover:scale-[1.02]');
        }
    }

    // 4. (Optional) ปิด Modal เมื่อคลิกพื้นที่ว่างข้างนอก
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    
    // 5. (Optional) ปิด Modal ด้วยปุ่ม ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
            closeDeleteModal();
        }
    });
</script>