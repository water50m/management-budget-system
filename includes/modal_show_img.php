<div id="receiptImageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-[60] backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fa-solid fa-file-invoice text-blue-500 mr-2"></i> จัดการเอกสาร/ใบเสร็จ
            </h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
        </div>

        <div class="p-6 flex-1 overflow-auto bg-gray-100 min-h-[350px]">
            <p id="noImageText" class="text-gray-500 text-center mt-10 hidden">ไม่มีเอกสารแนบสำหรับรายการนี้</p>

            <div id="imageGrid" class="grid grid-cols-1 gap-6 h-full hidden">

                <div id="oldImageContainer" class="flex flex-col items-center justify-center border-2 border-gray-300 rounded-lg p-3 bg-white hidden relative w-full h-full min-h-[40vh]">
                    <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-200 text-gray-700 px-3 py-1 text-xs font-bold rounded-full shadow-sm">
                        ไฟล์เดิม (Current)
                    </span>

                    <img id="modalImageViewer" src="" class="max-w-full max-h-[50vh] object-contain rounded mt-2 hidden">

                    <div id="pdfViewerContainer" class="w-full flex-col hidden mt-2">
                        <div class="flex justify-end mb-2">
                            <a id="modalPdfFullscreenLink" href="" target="_blank" class="bg-gray-800 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition flex items-center gap-1 shadow">
                                <i class="fa-solid fa-expand"></i> เปิดดูเต็มจอ
                            </a>
                        </div>
                        <iframe id="modalPdfViewer" src="" class="w-full h-[50vh] border rounded shadow-inner bg-gray-50"></iframe>
                    </div>

                    <div id="modalDocViewer" class="flex flex-col items-center justify-center mt-4 hidden">
                        <i class="fa-solid fa-file-arrow-down text-blue-600 text-6xl mb-4"></i>
                        <p class="text-gray-600 mb-4 font-bold">เอกสารแนบ</p>
                        <a id="modalDocLink" href="" target="_blank" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-700 transition">
                            <i class="fa-solid fa-download mr-1"></i> ดาวน์โหลด / เปิดดูไฟล์
                        </a>
                    </div>

                    <div id="downloadOriginalArea" class="mt-4 pt-4 border-t w-full text-center hidden">
                        <p class="text-sm text-gray-500 mb-2">คุณสามารถดาวน์โหลดไฟล์ต้นฉบับได้ที่นี่</p>
                        <a id="modalDownloadLink" href="" download class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2 rounded shadow hover:bg-indigo-700 transition font-bold">
                            <i class="fa-solid fa-download"></i> โหลดไฟล์ต้นฉบับ (Original File)
                        </a>
                    </div>
                </div>

                <div id="newImageContainer" class="flex flex-col items-center justify-center border-2 border-blue-400 border-dashed rounded-lg p-3 bg-blue-50 hidden relative shadow-inner min-h-[40vh]">
                    <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-blue-500 text-white px-3 py-1 text-xs font-bold rounded-full shadow-sm z-10">
                        ไฟล์ใหม่ (New)
                    </span>
                    <button type="button" onclick="cancelNewImage()" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-600 shadow-md transition-transform hover:scale-110 z-10" title="ยกเลิกไฟล์นี้">
                        &times;
                    </button>

                    <img id="previewNewImage" src="" class="max-w-full max-h-[50vh] object-contain rounded mt-2 hidden">

                    <div id="previewNewDoc" class="flex flex-col items-center justify-center mt-4 hidden">
                        <i class="fa-solid fa-file-circle-check text-green-500 text-6xl mb-3"></i>
                        <p class="text-gray-700 font-bold">เลือกไฟล์เอกสารแล้ว</p>
                        <p id="previewNewDocName" class="text-sm text-gray-500 mt-2 text-center break-all px-4"></p>
                    </div>
                </div>

            </div>
        </div>

        <div class="p-4 border-t bg-gray-50 flex flex-wrap justify-between items-center gap-3">

            <form method="POST" action="index.php?page=dashboard" onsubmit="return confirm('ยืนยันการลบไฟล์เอกสารนี้ถาวร?');" class="m-0">
                <input type="hidden" name="action" value="delete_receipt_image">
                <input type="hidden" name="expense_id" id="deleteExpenseId" value="">
                <input type="hidden" name="submit_page" value="<?= isset($_GET['page']) ? $_GET['page'] : 'dashboard' ?>">
                <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">
                <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">

                <button type="submit" id="btnDeleteImage" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 hidden shadow-sm">
                    <i class="fa-solid fa-trash-can mr-1"></i> ลบไฟล์นี้
                </button>
            </form>

            <form method="POST" action="index.php?page=dashboard" enctype="multipart/form-data" class="flex items-center gap-2 m-0 ml-auto">
                <input type="hidden" name="action" value="reupload_receipt_image">
                <input type="hidden" name="expense_id" id="reuploadExpenseId" value="">

                <input type="hidden" name="submit_page" value="<?= isset($_GET['page']) ? $_GET['page'] : 'dashboard' ?>">
                <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">
                <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">

                <label for="new_receipt_image" class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 shadow-sm">
                    <i class="fa-solid fa-upload mr-1"></i> เลือกไฟล์ใหม่
                </label>
                <input type="file" id="new_receipt_image" name="new_receipt_image" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx" required onchange="handleFileSelect(this)">

                <button type="submit" id="btnSubmitReupload" class="px-5 py-2 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 hidden shadow-md">
                    💾 ยืนยันบันทึกไฟล์
                </button>
            </form>

        </div>
    </div>
</div>

<script>
    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    function openImageModal(expenseId, previewPath, originalPath) {
        const modal = document.getElementById('receiptImageModal');
        const grid = document.getElementById('imageGrid');
        const oldContainer = document.getElementById('oldImageContainer');
        const noImgText = document.getElementById('noImageText');
        const btnDelete = document.getElementById('btnDeleteImage');

        // Viewers
        const imgViewer = document.getElementById('modalImageViewer');
        const pdfContainer = document.getElementById('pdfViewerContainer'); // 🌟 2. อ้างอิง Container แทน iframe 🌟
        const pdfViewer = document.getElementById('modalPdfViewer');
        const docViewer = document.getElementById('modalDocViewer');
        const pdfFullscreenLink = document.getElementById('modalPdfFullscreenLink'); // 🌟 ลิงก์สำหรับเปิดเต็มจอ 🌟

        const downloadArea = document.getElementById('downloadOriginalArea');
        const downloadLink = document.getElementById('modalDownloadLink');

        if(document.getElementById('deleteExpenseId')) document.getElementById('deleteExpenseId').value = expenseId;
        if(document.getElementById('reuploadExpenseId')) document.getElementById('reuploadExpenseId').value = expenseId;

        cancelNewImage();

        // ซ่อนทั้งหมดก่อน
        if (imgViewer) imgViewer.classList.add('hidden');
        if (pdfContainer) pdfContainer.classList.add('hidden'); // ซ่อน Container แทน
        if (pdfViewer) pdfViewer.classList.add('hidden');
        if (docViewer) docViewer.classList.add('hidden');
        if (downloadArea) downloadArea.classList.add('hidden');

        if (previewPath && previewPath !== 'NULL' && previewPath !== '') {
            const ext = getFileExtension(previewPath);

            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                if (imgViewer) {
                    imgViewer.src = previewPath;
                    imgViewer.classList.remove('hidden');
                }
            } else if (ext === 'pdf') {
                if (pdfContainer && pdfViewer && pdfFullscreenLink) {
                    pdfViewer.src = previewPath;
                    pdfFullscreenLink.href = previewPath; // 🌟 3. เซ็ต href ให้ปุ่มเต็มจอ 🌟
                    pdfContainer.classList.remove('hidden');
                    pdfContainer.classList.add('flex'); // ใช้ flex เพื่อให้ปุ่มจัดเรียงถูก
                    pdfViewer.classList.remove('hidden');
                }
            } else {
                const docLink = document.getElementById('modalDocLink');
                if (docLink) docLink.href = previewPath;
                if (docViewer) docViewer.classList.remove('hidden');
            }

            if (originalPath && originalPath !== 'NULL' && originalPath !== '') {
                if (originalPath !== previewPath) {
                    if (downloadLink) downloadLink.href = originalPath;
                    if (downloadArea) downloadArea.classList.remove('hidden');
                }
            }

            if (oldContainer) oldContainer.classList.remove('hidden');
            if (grid) {
                grid.classList.remove('hidden');
                grid.className = "grid grid-cols-1 gap-6 h-full w-full justify-items-center";
            }
            if (noImgText) noImgText.classList.add('hidden');
            if (btnDelete) btnDelete.classList.remove('hidden');
            
        } else {
            if (oldContainer) oldContainer.classList.add('hidden');
            if (grid) grid.classList.add('hidden');
            if (noImgText) noImgText.classList.remove('hidden');
            if (btnDelete) btnDelete.classList.add('hidden');
        }

        if (modal) modal.classList.remove('hidden');
    }

    function closeImageModal() {
        const modal = document.getElementById('receiptImageModal');
        if (modal) modal.classList.add('hidden');
        const pdfViewer = document.getElementById('modalPdfViewer');
        if (pdfViewer) pdfViewer.src = '';
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const ext = getFileExtension(file.name);

            const newContainer = document.getElementById('newImageContainer');
            const previewImg = document.getElementById('previewNewImage');
            const previewDoc = document.getElementById('previewNewDoc');
            const docName = document.getElementById('previewNewDocName');
            const btnSubmit = document.getElementById('btnSubmitReupload');
            const grid = document.getElementById('imageGrid');
            const noImgText = document.getElementById('noImageText');
            const oldContainer = document.getElementById('oldImageContainer');

            if (previewImg) previewImg.classList.add('hidden');
            if (previewDoc) previewDoc.classList.add('hidden');

            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('hidden');
                    }
                }
                reader.readAsDataURL(file);
            } else {
                if (docName) docName.innerText = file.name;
                if (previewDoc) previewDoc.classList.remove('hidden');
            }

            if (newContainer) newContainer.classList.remove('hidden');
            if (btnSubmit) btnSubmit.classList.remove('hidden');
            if (grid) grid.classList.remove('hidden');
            if (noImgText) noImgText.classList.add('hidden');

            if (oldContainer && !oldContainer.classList.contains('hidden')) {
                if (grid) grid.className = "grid grid-cols-1 md:grid-cols-2 gap-6 h-full items-center";
            } else {
                if (grid) grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";
            }
        }
    }

    function cancelNewImage() {
        const newReceiptImage = document.getElementById('new_receipt_image');
        const previewNewImage = document.getElementById('previewNewImage');
        const newImageContainer = document.getElementById('newImageContainer');
        const btnSubmitReupload = document.getElementById('btnSubmitReupload');
        const grid = document.getElementById('imageGrid');
        const oldContainer = document.getElementById('oldImageContainer');
        const noImgText = document.getElementById('noImageText');

        if (newReceiptImage) newReceiptImage.value = '';
        if (previewNewImage) previewNewImage.src = '';
        if (newImageContainer) newImageContainer.classList.add('hidden');
        if (btnSubmitReupload) btnSubmitReupload.classList.add('hidden');

        if (oldContainer && !oldContainer.classList.contains('hidden')) {
            if (grid) grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";
        } else {
            if (grid) grid.classList.add('hidden');
            if (noImgText) noImgText.classList.remove('hidden');
        }
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('receiptImageModal');
        if (event.target == modal) {
            closeImageModal();
        }
    });
</script>