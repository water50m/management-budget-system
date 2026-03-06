<div id="receiptImageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-[60] backdrop-blur-sm">
    <div id="modalBox" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">

        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fa-solid fa-file-invoice text-blue-500 mr-2"></i> จัดการเอกสาร/ใบเสร็จ
            </h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
        </div>

        <div id="modalBodyArea" class="p-6 flex-1 flex flex-col overflow-auto bg-gray-100 min-h-[350px] transition-all duration-300">
            <p id="noImageText" class="text-gray-500 text-center mt-10 hidden">ไม่มีเอกสารแนบสำหรับรายการนี้</p>

            <div id="imageGrid" class="flex-1 w-full hidden">

                <div id="oldImageContainer" class="flex flex-col items-center justify-center border-2 border-gray-300 rounded-lg p-3 bg-white hidden relative w-full h-full min-h-[40vh]">
                    <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-200 text-gray-700 px-3 py-1 text-xs font-bold rounded-full shadow-sm">
                        ไฟล์เดิม (Current)
                    </span>

                    <img id="modalImageViewer" src="" class="max-w-full max-h-[50vh] object-contain rounded mt-2 hidden">

                    <div id="pdfViewerContainer" class="w-full flex-col hidden mt-2 h-full">
                        <div class="flex justify-between items-center mb-2 px-2 pt-2">
                            <span class="text-sm text-gray-600 font-bold"><i class="fa-solid fa-file-pdf text-red-500 mr-1"></i> พรีวิวเอกสาร PDF</span>

                            <div class="flex gap-2">
                                <button type="button" onclick="toggleFullScreenModal()" id="btnToggleFullscreen" class="bg-gray-700 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-gray-800 transition flex items-center gap-2 shadow-md font-bold">
                                    <i class="fa-solid fa-expand"></i> ขยายเต็มจอ
                                </button>

                            </div>
                        </div>
                        <iframe id="modalPdfViewer" src="" class="w-full h-[50vh] border-2 border-gray-200 rounded-lg shadow-inner bg-gray-50 transition-all duration-300"></iframe>
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

                <div id="newImageContainer" class="flex flex-col items-center justify-center border-2 border-blue-400 border-dashed rounded-lg p-3 bg-blue-50 hidden relative shadow-inner min-h-[40vh] w-full">
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

                <label for="new_receipt_image" class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-upload"></i> เลือกไฟล์ใหม่
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
    let isFullScreen = false; // ตัวแปรเก็บสถานะว่าเต็มจออยู่หรือไม่

    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    // 🌟 ฟังก์ชันจัดการขนาดเต็มจอ (Fullscreen) 🌟
    // 🌟 ฟังก์ชันจัดการขนาดเต็มจอ (Fullscreen) 🌟
    function toggleFullScreenModal() {
        const modalBox = document.getElementById('modalBox');
        const modalBody = document.getElementById('modalBodyArea');
        const pdfIframe = document.getElementById('modalPdfViewer');
        const pdfContainer = document.getElementById('pdfViewerContainer');
        const oldContainer = document.getElementById('oldImageContainer');
        const btnToggle = document.getElementById('btnToggleFullscreen');

        isFullScreen = !isFullScreen;

        if (isFullScreen) {
            // ขยายกล่องให้สุดขอบหน้าจอ
            modalBox.classList.remove('max-w-4xl', 'mx-4', 'rounded-xl', 'max-h-[90vh]');
            modalBox.classList.add('max-w-full', 'm-0', 'rounded-none', 'h-screen', 'max-h-screen');
            // (ไม่จำเป็นต้องแอด w-full เพราะมันมีอยู่แล้ว)

            // เอา Padding ออกเพื่อให้ PDF ชิดขอบซ้ายขวา
            modalBody.classList.remove('p-6');
            modalBody.classList.add('p-0');
            oldContainer.classList.remove('border-2', 'p-3', 'rounded-lg');
            oldContainer.classList.add('border-0', 'p-0', 'rounded-none');

            // ยืดความสูง iframe และ Container แบบ 100%
            pdfIframe.classList.remove('h-[50vh]', 'rounded-lg', 'border-2');
            pdfIframe.classList.add('h-full', 'rounded-none', 'border-0');

            // เปลี่ยนหน้าตาปุ่มเป็น "ย่อหน้าจอ"
            btnToggle.innerHTML = '<i class="fa-solid fa-compress"></i> ย่อหน้าจอ';
            btnToggle.classList.replace('bg-gray-700', 'bg-red-600');
            btnToggle.classList.replace('hover:bg-gray-800', 'hover:bg-red-700');
        } else {
            // ย่อกลับขนาดเดิม
            modalBox.classList.add('max-w-4xl', 'mx-4', 'rounded-xl', 'max-h-[90vh]');
            // 🌟 แก้ตรงนี้: เอา 'w-full' ออกจากวงเล็บ remove เพื่อรักษากล่องให้กว้างเท่าเดิม 🌟
            modalBox.classList.remove('max-w-full', 'm-0', 'rounded-none', 'h-screen', 'max-h-screen');

            modalBody.classList.add('p-6');
            modalBody.classList.remove('p-0');
            oldContainer.classList.add('border-2', 'p-3', 'rounded-lg');
            oldContainer.classList.remove('border-0', 'p-0', 'rounded-none');

            // ย่อความสูง iframe กลับไปค่าเดิม
            pdfIframe.classList.add('h-[50vh]', 'rounded-lg', 'border-2');
            pdfIframe.classList.remove('h-full', 'rounded-none', 'border-0');

            // เปลี่ยนหน้าตาปุ่มเป็น "ขยายเต็มจอ"
            btnToggle.innerHTML = '<i class="fa-solid fa-expand"></i> ขยายเต็มจอ';
            btnToggle.classList.replace('bg-red-600', 'bg-gray-700');
            btnToggle.classList.replace('hover:bg-red-700', 'hover:bg-gray-800');
        }
    }

    function openImageModal(expenseId, previewPath, originalPath) {
        const modal = document.getElementById('receiptImageModal');
        const grid = document.getElementById('imageGrid');
        const oldContainer = document.getElementById('oldImageContainer');
        const noImgText = document.getElementById('noImageText');
        const btnDelete = document.getElementById('btnDeleteImage');

        // Viewers
        const imgViewer = document.getElementById('modalImageViewer');
        const pdfContainer = document.getElementById('pdfViewerContainer'); 
        const pdfViewer = document.getElementById('modalPdfViewer');
        const docViewer = document.getElementById('modalDocViewer');
        
        // ❌ เอา const pdfFullscreenLink ออกไปแล้ว ❌

        const downloadArea = document.getElementById('downloadOriginalArea');
        const downloadLink = document.getElementById('modalDownloadLink');

        if(document.getElementById('deleteExpenseId')) document.getElementById('deleteExpenseId').value = expenseId;
        if(document.getElementById('reuploadExpenseId')) document.getElementById('reuploadExpenseId').value = expenseId;

        cancelNewImage();

        // ซ่อนทั้งหมดก่อน
        if (imgViewer) imgViewer.classList.add('hidden');
        if (pdfContainer) pdfContainer.classList.add('hidden'); 
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
                // 🌟 แก้ไขเงื่อนไข: เช็คแค่ Container กับ Viewer ก็พอ 🌟
                if (pdfContainer && pdfViewer) {
                    pdfViewer.src = previewPath + '#view=FitH'; 
                    pdfContainer.classList.remove('hidden');
                    pdfContainer.classList.add('flex'); 
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
                grid.className = "flex w-full h-full gap-6";
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

    // 🌟 เพิ่มการสั่ง Reset เต็มจอ กลับเป็นขนาดปกติเมื่อปิด Modal 🌟
    function closeImageModal() {
        const modal = document.getElementById('receiptImageModal');
        if (modal) modal.classList.add('hidden');
        const pdfViewer = document.getElementById('modalPdfViewer');
        if (pdfViewer) pdfViewer.src = '';

        // ถ้าเปิดเต็มจอค้างไว้ ให้ย่อกลับอัตโนมัติ
        if (isFullScreen) {
            toggleFullScreenModal();
        }
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
                // ถ้ามี 2 รูป แบ่งครึ่ง 50-50
                if (grid) grid.className = "grid grid-cols-1 md:grid-cols-2 gap-6 h-full items-start w-full";
            } else {
                if (grid) grid.className = "flex w-full h-full gap-6 justify-center";
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
            if (grid) grid.className = "flex w-full h-full gap-6";
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