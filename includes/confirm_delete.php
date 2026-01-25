<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-red-600">ยืนยันการลบข้อมูล?</h3>
            
            <form id="deleteForm" action="index.php?page=dashboard" method="POST" class="mt-4 px-4">
                <input type="hidden" name="action" value="delete_budget">
                <input type="hidden" name="id" id="delete_target_id">
                
                <p class="text-sm text-gray-500 mb-2">
                    เพื่อยืนยัน กรุณาพิมพ์คำว่า <br>
                    <span class="font-bold text-gray-800 bg-gray-100 px-2 py-1 rounded select-all">ลบข้อมูลนี้</span>
                </p>

                <input type="text" id="confirm_text_input" 
                       oninput="checkDeleteMatch()"
                       class="w-full px-3 py-2 text-center border-2 border-gray-300 rounded-lg focus:outline-none focus:border-red-500 transition"
                       placeholder="พิมพ์คำว่า 'ลบข้อมูลนี้'" autocomplete="off">

                <input type="hidden" name="delete_reason" value="User Typed Confirmation">

                <div class="mt-5 flex gap-2">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        ยกเลิก
                    </button>
                    <button id="btn_real_delete" type="submit" disabled
                        class="flex-1 px-4 py-2 bg-gray-300 text-white font-bold rounded cursor-not-allowed transition duration-300">
                        🗑️ ลบทันที
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันเปิด Modal
    function openDeleteModal(id) {
        document.getElementById('delete_target_id').value = id;
        document.getElementById('confirm_text_input').value = ''; // เคลียร์ช่องพิมพ์
        checkDeleteMatch(); // รีเซ็ตปุ่ม
        document.getElementById('deleteModal').classList.remove('hidden');
        
        // Auto Focus ช่องพิมพ์เพื่อให้ User พิมพ์ได้เลย
        setTimeout(() => {
            document.getElementById('confirm_text_input').focus();
        }, 100);
    }

    // ฟังก์ชันปิด Modal
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // ฟังก์ชันเช็คคำที่พิมพ์ (หัวใจสำคัญ)
    function checkDeleteMatch() {
        const input = document.getElementById('confirm_text_input').value;
        const btn = document.getElementById('btn_real_delete');
        
        // ถ้าพิมพ์ตรงเป๊ะ -> ปุ่มแดง กดได้
        if (input === 'ลบข้อมูลนี้') {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            btn.classList.add('bg-red-600', 'hover:bg-red-700', 'shadow-lg');
        } else {
            // ถ้าไม่ตรง -> ปุ่มเทา กดไม่ได้
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'cursor-not-allowed');
            btn.classList.remove('bg-red-600', 'hover:bg-red-700', 'shadow-lg');
        }
    }
</script>