
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-red-100">

            <form action="index.php?page=dashboard&tab=users" method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="modalDeleteUserId">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">ยืนยันการลบผู้ใช้งาน</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    คุณกำลังจะลบผู้ใช้งาน: <span id="modalDeleteUserName" class="font-bold text-red-600 text-base"></span>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    การกระทำนี้ไม่สามารถย้อนกลับได้ ข้อมูลทั้งหมดที่เกี่ยวข้องจะถูกลบหรือระงับการใช้งาน
                                </p>

                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        พิมพ์คำว่า <span class="font-bold select-all bg-gray-100 px-1 rounded border border-gray-300">ลบข้อมูลบุคลากรนี้</span> เพื่อยืนยัน
                                    </label>
                                    <input type="text" id="confirmDeleteInput"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm border p-2"
                                        placeholder="ลบข้อมูลบุคลากรนี้"
                                        oninput="checkDeleteConfirmation()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" id="btnConfirmDelete" disabled
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        ยืนยันลบข้อมูล
                    </button>
                    <button type="button" onclick="closeDeleteUserModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันเปิด Modal รับค่า ID และ ชื่อ
    function openDeleteUserModal(userId, fullName) {
        // 1. ดึง Element ต่างๆ มาเตรียมไว้
        const idInput = document.getElementById('modalDeleteUserId');
        const nameSpan = document.getElementById('modalDeleteUserName');
        const modal = document.getElementById('deleteUserModal');
        const confirmInput = document.getElementById('confirmDeleteInput');
        const submitBtn = document.getElementById('btnConfirmDelete');

        // Protection: ถ้าหา Element ไม่เจอ (เช่น หน้าเว็บยังโหลดไม่เสร็จ) ให้หยุดทำงาน
        if (!idInput || !nameSpan || !modal) {
            console.error("Error: ไม่พบ Element ของ Modal");
            return;
        }

        // 2. ใส่ค่า ID และชื่อลงไป
        idInput.value = userId;
        nameSpan.innerText = fullName;

        // 3. Reset ช่องกรอกและปุ่มยืนยัน ให้กลับเป็นค่าเริ่มต้น
        confirmInput.value = '';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

        // 4. แสดง Modal (เอา class hidden ออก)
        modal.classList.remove('hidden');

        // 5. Focus ไปที่ช่องกรอกข้อความทันที เพื่อความสะดวก
        setTimeout(() => {
            confirmInput.focus();
        }, 100);
    }

    // ฟังก์ชันตรวจสอบคำยืนยัน (ทำงานตอนพิมพ์)
    function checkDeleteConfirmation() {
        const inputVal = document.getElementById('confirmDeleteInput').value;
        const btn = document.getElementById('btnConfirmDelete');
        const confirmText = "ลบข้อมูลบุคลากรนี้"; // ข้อความที่ต้องพิมพ์ให้ตรงเป๊ะ

        if (inputVal === confirmText) {
            // ถ้าพิมพ์ถูก -> เปิดปุ่ม
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            // ถ้าพิมพ์ผิด/ยังไม่ครบ -> ปิดปุ่ม
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // ฟังก์ชันปิด Modal
    function closeDeleteUserModal() {
        const modal = document.getElementById('deleteUserModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // (แถม) สั่งให้กดปุ่ม ESC แล้วปิด Modal ได้
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeDeleteUserModal();
        }
    });
</script>