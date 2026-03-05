<div id="receiptImageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-[60] backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fa-solid fa-image text-blue-500 mr-2"></i> จัดการรูปภาพเอกสาร/ใบเสร็จ
            </h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
        </div>

        <div class="p-6 flex-1 overflow-auto bg-gray-100 min-h-[350px]">
            <p id="noImageText" class="text-gray-500 text-center mt-10 hidden">ไม่มีรูปภาพแนบสำหรับรายการนี้</p>

            <div id="imageGrid" class="grid grid-cols-1 gap-6 h-full hidden">

                <div id="oldImageContainer" class="flex flex-col items-center justify-center border-2 border-gray-300 rounded-lg p-3 bg-white hidden relative">
                    <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-200 text-gray-700 px-3 py-1 text-xs font-bold rounded-full shadow-sm">
                        รูปเดิม (Current)
                    </span>
                    <img id="modalImageViewer" src="" alt="Old Receipt" class="max-w-full max-h-[50vh] object-contain rounded mt-2">
                </div>

                <div id="newImageContainer" class="flex flex-col items-center justify-center border-2 border-blue-400 border-dashed rounded-lg p-3 bg-blue-50 hidden relative shadow-inner">
                    <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-blue-500 text-white px-3 py-1 text-xs font-bold rounded-full shadow-sm">
                        รูปใหม่ (New)
                    </span>
                    <button type="button" onclick="cancelNewImage()" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-600 shadow-md transition-transform hover:scale-110 focus:outline-none" title="ยกเลิกรูปนี้">
                        &times;
                    </button>
                    <img id="previewNewImage" src="" alt="New Receipt" class="max-w-full max-h-[50vh] object-contain rounded mt-2">
                </div>

            </div>
        </div>

        <div class="p-4 border-t bg-gray-50 flex flex-wrap justify-between items-center gap-3">

            <form method="POST" action="index.php?page=dashboard" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพนี้? (รูปจะถูกลบออกจากระบบถาวร)');" class="m-0">
                <input type="hidden" name="action" value="delete_receipt_image">
                <input type="hidden" name="expense_id" id="deleteExpenseId" value="">

                <input type="hidden" name="submit_page" value="<?= isset($_GET['page']) ? $_GET['page'] : 'dashboard' ?>">
                <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">
                <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">

                <button type="submit" id="btnDeleteImage" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 transition-colors hidden shadow-sm">
                    <i class="fa-solid fa-trash-can mr-1"></i> ลบรูปภาพนี้
                </button>
            </form>

            <form method="POST" action="index.php?page=dashboard" enctype="multipart/form-data" class="flex items-center gap-2 m-0 ml-auto">
                <input type="hidden" name="action" value="reupload_receipt_image">
                <input type="hidden" name="expense_id" id="reuploadExpenseId" value="">

                <input type="hidden" name="submit_page" value="<?= isset($_GET['page']) ? $_GET['page'] : 'dashboard' ?>">
                <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">
                <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">

                <label for="new_receipt_image" class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 transition-colors shadow-sm">
                    <i class="fa-solid fa-upload mr-1"></i> เลือกรูปใหม่
                </label>
                <input type="file" id="new_receipt_image" name="new_receipt_image" class="hidden" accept="image/*" required onchange="handleFileSelect(this)">

                <button type="submit" id="btnSubmitReupload" class="px-5 py-2 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition-colors hidden shadow-md ">
                    💾 ยืนยันบันทึกรูปใหม่
                </button>
            </form>

        </div>
    </div>
</div>

<script>
    // เปิด Modal แสดงรูป
    function openImageModal(expenseId, imagePath) {
        const modal = document.getElementById('receiptImageModal');
        const grid = document.getElementById('imageGrid');
        const oldContainer = document.getElementById('oldImageContainer');
        const imgViewer = document.getElementById('modalImageViewer');
        const noImgText = document.getElementById('noImageText');
        const btnDelete = document.getElementById('btnDeleteImage');

        // เซ็ตค่า ID
        document.getElementById('deleteExpenseId').value = expenseId;
        document.getElementById('reuploadExpenseId').value = expenseId;

        // เคลียร์พรีวิวรูปใหม่ทิ้งเสมอเมื่อเปิด Modal ใหม่
        cancelNewImage();

        // เช็คว่ามีรูปเดิมหรือไม่
        if (imagePath && imagePath !== 'NULL' && imagePath !== '') {
            imgViewer.src = imagePath;
            oldContainer.classList.remove('hidden');
            grid.classList.remove('hidden');
            noImgText.classList.add('hidden');
            btnDelete.classList.remove('hidden');

            // ตั้งค่าให้ Grid มี 1 คอลัมน์ (โชว์ตรงกลาง)
            grid.className = "grid grid-cols-1 gap-6 h-full w-full justify-items-center";
        } else {
            oldContainer.classList.add('hidden');
            grid.classList.add('hidden');
            noImgText.classList.remove('hidden');
            btnDelete.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    // ปิด Modal
    function closeImageModal() {
        document.getElementById('receiptImageModal').classList.add('hidden');
    }

    // เมื่อมีการเลือกไฟล์รูปภาพใหม่
    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                const previewImg = document.getElementById('previewNewImage');
                const newContainer = document.getElementById('newImageContainer');
                const btnSubmit = document.getElementById('btnSubmitReupload');
                const grid = document.getElementById('imageGrid');
                const noImgText = document.getElementById('noImageText');
                const oldContainer = document.getElementById('oldImageContainer');

                previewImg.src = e.target.result;
                newContainer.classList.remove('hidden');
                btnSubmit.classList.remove('hidden');
                grid.classList.remove('hidden');
                noImgText.classList.add('hidden');

                // เช็คว่าถ้ามี "รูปเดิม" อยู่ ให้แบ่งหน้าจอเป็น 2 ฝั่ง (2 Columns)
                if (!oldContainer.classList.contains('hidden')) {
                    grid.className = "grid grid-cols-1 md:grid-cols-2 gap-6 h-full items-center";
                } else {
                    // ถ้าไม่มีรูปเดิม ให้โชว์รูปใหม่ตรงกลาง (1 Column)
                    grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";
                }
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // เมื่อกดยกเลิกการเลือกรูปใหม่ (ปุ่มกากบาท)
    function cancelNewImage() {
        document.getElementById('new_receipt_image').value = '';
        document.getElementById('previewNewImage').src = '';
        document.getElementById('newImageContainer').classList.add('hidden');
        document.getElementById('btnSubmitReupload').classList.add('hidden');

        const grid = document.getElementById('imageGrid');
        const oldContainer = document.getElementById('oldImageContainer');
        const noImgText = document.getElementById('noImageText');

        // กลับไปเช็คสถานะรูปเดิม
        if (!oldContainer.classList.contains('hidden')) {
            // มีรูปเดิม ให้จัดหน้ากลับมาตรงกลาง 1 คอลัมน์
            grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";
        } else {
            // ไม่มีรูปเดิมเลย ก็ซ่อน Grid แล้วโชว์ Text แจ้งเตือน
            grid.classList.add('hidden');
            noImgText.classList.remove('hidden');
        }
    }

    // ปิด Modal เมื่อคลิกพื้นที่สีดำข้างนอก
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('receiptImageModal');
        if (event.target == modal) {
            closeImageModal();
        }
    });
</script>