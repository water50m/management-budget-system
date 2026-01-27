<?php

function renderDeleteModal($actionUrl, $actionValue, $targetInputId) {
    // ป้องกัน XSS เบื้องต้น
    $actionUrl = htmlspecialchars($actionUrl);
    $actionValue = htmlspecialchars($actionValue);
    $targetInputId = htmlspecialchars($targetInputId);
    

    echo <<<HTML
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-2xl rounded-xl bg-white transform transition-all scale-100">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4 animate-pulse">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <h3 class="text-lg leading-6 font-bold text-red-600">ยืนยันการลบข้อมูล?</h3>
                
                <form id="deleteForm" action="{$actionUrl}" method="POST" class="mt-4 px-4">
                    <input type="hidden" name="action" value="{$actionValue}">
                    
                    <input type="hidden" name="id" id="{$targetInputId}">
                    
                    <p class="text-sm text-gray-500 mb-2">
                        เพื่อยืนยัน กรุณาพิมพ์คำว่า <br>
                        <span class="font-bold text-gray-800 bg-gray-100 px-2 py-1 rounded select-all border border-gray-300">ลบข้อมูลนี้</span>
                    </p>

                    <input type="text" id="confirm_text_input" 
                           oninput="checkDeleteMatch()"
                           class="w-full px-3 py-2 text-center border-2 border-gray-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 transition"
                           placeholder="พิมพ์คำว่า 'ลบข้อมูลนี้'" autocomplete="off">

                    <input type="hidden" name="delete_reason" value="User Typed Confirmation">

                    <div class="mt-5 flex gap-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition duration-200">
                            ยกเลิก
                        </button>
                        
                        <button id="btn_real_delete" type="submit" disabled
                            class="flex-1 px-4 py-2 bg-gray-300 text-white font-bold rounded-lg cursor-not-allowed transition duration-300 shadow-sm">
                            🗑️ ลบทันที
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันตรวจสอบข้อความที่พิมพ์
        function checkDeleteMatch() {
            const input = document.getElementById('confirm_text_input');
            const btn = document.getElementById('btn_real_delete');
            const keyword = 'ลบข้อมูลนี้'; // คำที่ต้องการให้พิมพ์

            if (input.value === keyword) {
                // กรณีพิมพ์ถูก: เปลี่ยนปุ่มเป็นสีแดง กดได้
                btn.disabled = false;
                btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
                btn.classList.add('bg-red-600', 'hover:bg-red-700', 'shadow-md', 'transform', 'hover:scale-105');
            } else {
                // กรณีพิมพ์ผิด: กลับเป็นสีเทา กดไม่ได้
                btn.disabled = true;
                btn.classList.add('bg-gray-300', 'cursor-not-allowed');
                btn.classList.remove('bg-red-600', 'hover:bg-red-700', 'shadow-md', 'transform', 'hover:scale-105');
            }
        }

        // ฟังก์ชันปิด Modal และเคลียร์ค่า
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const input = document.getElementById('confirm_text_input');
            const btn = document.getElementById('btn_real_delete');
            
            modal.classList.add('hidden');
            input.value = ''; // ล้างช่องพิมพ์
            checkDeleteMatch(); // รีเซ็ตสถานะปุ่ม
        }

        // ฟังก์ชันเปิด Modal (เรียกจากปุ่มถังขยะข้างนอก)
        // param id: ID ของข้อมูลที่จะลบ (database ID)
        // param targetInputId: ID ของ input hidden ที่จะรับค่า (ส่งมาจาก PHP)
        function openDeleteModal(id) {
            // ใส่ ID ลงใน Hidden Input ตามชื่อ ID ที่เราตั้งไว้ใน PHP
            document.getElementById('{$targetInputId}').value = id;
            
            // เปิด Modal
            document.getElementById('deleteModal').classList.remove('hidden');
            
            // Focus ช่องพิมพ์ทันที เพื่อความสะดวก
            setTimeout(() => {
                document.getElementById('confirm_text_input').focus();
            }, 100);
        }
    </script>
HTML;
}
?>


<!-- การใช้งาน -->
 <!-- <button onclick="openDeleteModal(<?php //echo $row['id']; ?>)" 
        class="text-red-500 hover:text-red-700">
    ลบ
</button> -->


<?php 
    // เรียกฟังก์ชัน พร้อมส่งค่าตามที่คุณต้องการ
    // renderDeleteModal(
    //     "index.php?page=dashboard",  // action
    //     "delete_budget",             // value (action name)
    //     "delete_target_id"           // id ของ hidden input
    // ); 
?>