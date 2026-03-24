[33mcommit 82bf47ea72dcdf375dd57a52d50fc431074f7b37[m[33m ([m[1;36mHEAD[m[33m -> [m[1;32mmain[m[33m, [m[1;31morigin/main[m[33m, [m[1;31morigin/HEAD[m[33m)[m
Author: water50m <pmachaopa1@gmail.com>
Date:   Fri Mar 6 15:37:19 2026 +0700

    ui upload file

[1mdiff --git a/includes/add_expense_modal.php b/includes/add_expense_modal.php[m
[1mindex 53e641f..1f99970 100644[m
[1m--- a/includes/add_expense_modal.php[m
[1m+++ b/includes/add_expense_modal.php[m
[36m@@ -92,11 +92,11 @@[m [m$cats_list = $data['categories_list'];[m
                             </svg>[m
                             <div class="flex text-sm text-gray-600 justify-center">[m
                                 <label for="receipt_image" class="relative cursor-pointer bg-transparent rounded-md font-medium text-green-600 hover:text-green-800 focus-within:outline-none">[m
[31m-                                    <span>คลิกเพื่ออัปโหลดรูปภาพ</span>[m
[32m+[m[32m                                    <span>คลิกเพื่ออัปโหลดเอกสาร</span>[m
                                     <input id="receipt_image" name="receipt_image" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">[m
                                 </label>[m
                             </div>[m
[31m-                            <p class="text-xs text-gray-500">รองรับ PNG, JPG, JPEG (ขนาดไม่เกิน 5MB)</p>[m
[32m+[m[32m                            <p class="text-xs text-gray-500">รองรับ PNG, JPG, JPEG, PDF, DOC, DOCX, XLS, XLSX (ขนาดไม่เกิน 5MB)</p>[m
                         </div>[m
 [m
                         <div id="imagePreviewContainer" class="hidden absolute inset-0 p-2 bg-white rounded-lg flex flex-col items-center justify-center border-2 border-green-400">[m
[1mdiff --git a/includes/modal_show_img.php b/includes/modal_show_img.php[m
[1mindex 610d895..da3c426 100644[m
[1m--- a/includes/modal_show_img.php[m
[1m+++ b/includes/modal_show_img.php[m
[36m@@ -1,5 +1,5 @@[m
 <div id="receiptImageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-[60] backdrop-blur-sm">[m
[31m-    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">[m
[32m+[m[32m    <div id="modalBox" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform transition-all overflow-hidden flex flex-col max-h-[90vh]">[m
 [m
         <div class="flex justify-between items-center p-4 border-b bg-gray-50">[m
             <h3 class="text-lg font-bold text-gray-800">[m
[36m@@ -8,10 +8,10 @@[m
             <button onclick="closeImageModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>[m
         </div>[m
 [m
[31m-        <div class="p-6 flex-1 overflow-auto bg-gray-100 min-h-[350px]">[m
[32m+[m[32m        <div id="modalBodyArea" class="p-6 flex-1 flex flex-col overflow-auto bg-gray-100 min-h-[350px] transition-all duration-300">[m
             <p id="noImageText" class="text-gray-500 text-center mt-10 hidden">ไม่มีเอกสารแนบสำหรับรายการนี้</p>[m
 [m
[31m-            <div id="imageGrid" class="grid grid-cols-1 gap-6 h-full hidden">[m
[32m+[m[32m            <div id="imageGrid" class="flex-1 w-full hidden">[m
 [m
                 <div id="oldImageContainer" class="flex flex-col items-center justify-center border-2 border-gray-300 rounded-lg p-3 bg-white hidden relative w-full h-full min-h-[40vh]">[m
                     <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-200 text-gray-700 px-3 py-1 text-xs font-bold rounded-full shadow-sm">[m
[36m@@ -20,13 +20,18 @@[m
 [m
                     <img id="modalImageViewer" src="" class="max-w-full max-h-[50vh] object-contain rounded mt-2 hidden">[m
 [m
[31m-                    <div id="pdfViewerContainer" class="w-full flex-col hidden mt-2">[m
[31m-                        <div class="flex justify-end mb-2">[m
[31m-                            <a id="modalPdfFullscreenLink" href="" target="_blank" class="bg-gray-800 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition flex items-center gap-1 shadow">[m
[31m-                                <i class="fa-solid fa-expand"></i> เปิดดูเต็มจอ[m
[31m-                            </a>[m
[32m+[m[32m                    <div id="pdfViewerContainer" class="w-full flex-col hidden mt-2 h-full">[m
[32m+[m[32m                        <div class="flex justify-between items-center mb-2 px-2 pt-2">[m
[32m+[m[32m                            <span class="text-sm text-gray-600 font-bold"><i class="fa-solid fa-file-pdf text-red-500 mr-1"></i> พรีวิวเอกสาร PDF</span>[m
[32m+[m
[32m+[m[32m                            <div class="flex gap-2">[m
[32m+[m[32m                                <button type="button" onclick="toggleFullScreenModal()" id="btnToggleFullscreen" class="bg-gray-700 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-gray-800 transition flex items-center gap-2 shadow-md font-bold">[m
[32m+[m[32m                                    <i class="fa-solid fa-expand"></i> ขยายเต็มจอ[m
[32m+[m[32m                                </button>[m
[32m+[m
[32m+[m[32m                            </div>[m
                         </div>[m
[31m-                        <iframe id="modalPdfViewer" src="" class="w-full h-[50vh] border rounded shadow-inner bg-gray-50"></iframe>[m
[32m+[m[32m                        <iframe id="modalPdfViewer" src="" class="w-full h-[50vh] border-2 border-gray-200 rounded-lg shadow-inner bg-gray-50 transition-all duration-300"></iframe>[m
                     </div>[m
 [m
                     <div id="modalDocViewer" class="flex flex-col items-center justify-center mt-4 hidden">[m
[36m@@ -45,7 +50,7 @@[m
                     </div>[m
                 </div>[m
 [m
[31m-                <div id="newImageContainer" class="flex flex-col items-center justify-center border-2 border-blue-400 border-dashed rounded-lg p-3 bg-blue-50 hidden relative shadow-inner min-h-[40vh]">[m
[32m+[m[32m                <div id="newImageContainer" class="flex flex-col items-center justify-center border-2 border-blue-400 border-dashed rounded-lg p-3 bg-blue-50 hidden relative shadow-inner min-h-[40vh] w-full">[m
                     <span class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-blue-500 text-white px-3 py-1 text-xs font-bold rounded-full shadow-sm z-10">[m
                         ไฟล์ใหม่ (New)[m
                     </span>[m
[36m@@ -66,7 +71,6 @@[m
         </div>[m
 [m
         <div class="p-4 border-t bg-gray-50 flex flex-wrap justify-between items-center gap-3">[m
[31m-[m
             <form method="POST" action="index.php?page=dashboard" onsubmit="return confirm('ยืนยันการลบไฟล์เอกสารนี้ถาวร?');" class="m-0">[m
                 <input type="hidden" name="action" value="delete_receipt_image">[m
                 <input type="hidden" name="expense_id" id="deleteExpenseId" value="">[m
[36m@@ -87,8 +91,8 @@[m
                 <input type="hidden" name="submit_tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : '' ?>">[m
                 <input type="hidden" name="profile_id" value="<?= isset($_GET['id']) ? $_GET['id'] : 0 ?>">[m
 [m
[31m-                <label for="new_receipt_image" class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 shadow-sm">[m
[31m-                    <i class="fa-solid fa-upload mr-1"></i> เลือกไฟล์ใหม่[m
[32m+[m[32m                <label for="new_receipt_image" class="cursor-pointer px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 shadow-sm flex items-center gap-2">[m
[32m+[m[32m                    <i class="fa-solid fa-upload"></i> เลือกไฟล์ใหม่[m
                 </label>[m
                 <input type="file" id="new_receipt_image" name="new_receipt_image" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx" required onchange="handleFileSelect(this)">[m
 [m
[36m@@ -96,16 +100,71 @@[m
                     💾 ยืนยันบันทึกไฟล์[m
                 </button>[m
             </form>[m
[31m-[m
         </div>[m
     </div>[m
 </div>[m
 [m
 <script>[m
[32m+[m[32m    let isFullScreen = false; // ตัวแปรเก็บสถานะว่าเต็มจออยู่หรือไม่[m
[32m+[m
     function getFileExtension(filename) {[m
         return filename.split('.').pop().toLowerCase();[m
     }[m
 [m
[32m+[m[32m    // 🌟 ฟังก์ชันจัดการขนาดเต็มจอ (Fullscreen) 🌟[m
[32m+[m[32m    // 🌟 ฟังก์ชันจัดการขนาดเต็มจอ (Fullscreen) 🌟[m
[32m+[m[32m    function toggleFullScreenModal() {[m
[32m+[m[32m        const modalBox = document.getElementById('modalBox');[m
[32m+[m[32m        const modalBody = document.getElementById('modalBodyArea');[m
[32m+[m[32m        const pdfIframe = document.getElementById('modalPdfViewer');[m
[32m+[m[32m        const pdfContainer = document.getElementById('pdfViewerContainer');[m
[32m+[m[32m        const oldContainer = document.getElementById('oldImageContainer');[m
[32m+[m[32m        const btnToggle = document.getElementById('btnToggleFullscreen');[m
[32m+[m
[32m+[m[32m        isFullScreen = !isFullScreen;[m
[32m+[m
[32m+[m[32m        if (isFullScreen) {[m
[32m+[m[32m            // ขยายกล่องให้สุดขอบหน้าจอ[m
[32m+[m[32m            modalBox.classList.remove('max-w-4xl', 'mx-4', 'rounded-xl', 'max-h-[90vh]');[m
[32m+[m[32m            modalBox.classList.add('max-w-full', 'm-0', 'rounded-none', 'h-screen', 'max-h-screen');[m
[32m+[m[32m            // (ไม่จำเป็นต้องแอด w-full เพราะมันมีอยู่แล้ว)[m
[32m+[m
[32m+[m[32m            // เอา Padding ออกเพื่อให้ PDF ชิดขอบซ้ายขวา[m
[32m+[m[32m            modalBody.classList.remove('p-6');[m
[32m+[m[32m            modalBody.classList.add('p-0');[m
[32m+[m[32m            oldContainer.classList.remove('border-2', 'p-3', 'rounded-lg');[m
[32m+[m[32m            oldContainer.classList.add('border-0', 'p-0', 'rounded-none');[m
[32m+[m
[32m+[m[32m            // ยืดความสูง iframe และ Container แบบ 100%[m
[32m+[m[32m            pdfIframe.classList.remove('h-[50vh]', 'rounded-lg', 'border-2');[m
[32m+[m[32m            pdfIframe.classList.add('h-full', 'rounded-none', 'border-0');[m
[32m+[m
[32m+[m[32m            // เปลี่ยนหน้าตาปุ่มเป็น "ย่อหน้าจอ"[m
[32m+[m[32m            btnToggle.innerHTML = '<i class="fa-solid fa-compress"></i> ย่อหน้าจอ';[m
[32m+[m[32m            btnToggle.classList.replace('bg-gray-700', 'bg-red-600');[m
[32m+[m[32m            btnToggle.classList.replace('hover:bg-gray-800', 'hover:bg-red-700');[m
[32m+[m[32m        } else {[m
[32m+[m[32m            // ย่อกลับขนาดเดิม[m
[32m+[m[32m            modalBox.classList.add('max-w-4xl', 'mx-4', 'rounded-xl', 'max-h-[90vh]');[m
[32m+[m[32m            // 🌟 แก้ตรงนี้: เอา 'w-full' ออกจากวงเล็บ remove เพื่อรักษากล่องให้กว้างเท่าเดิม 🌟[m
[32m+[m[32m            modalBox.classList.remove('max-w-full', 'm-0', 'rounded-none', 'h-screen', 'max-h-screen');[m
[32m+[m
[32m+[m[32m            modalBody.classList.add('p-6');[m
[32m+[m[32m            modalBody.classList.remove('p-0');[m
[32m+[m[32m            oldContainer.classList.add('border-2', 'p-3', 'rounded-lg');[m
[32m+[m[32m            oldContainer.classList.remove('border-0', 'p-0', 'rounded-none');[m
[32m+[m
[32m+[m[32m            // ย่อความสูง iframe กลับไปค่าเดิม[m
[32m+[m[32m            pdfIframe.classList.add('h-[50vh]', 'rounded-lg', 'border-2');[m
[32m+[m[32m            pdfIframe.classList.remove('h-full', 'rounded-none', 'border-0');[m
[32m+[m
[32m+[m[32m            // เปลี่ยนหน้าตาปุ่มเป็น "ขยายเต็มจอ"[m
[32m+[m[32m            btnToggle.innerHTML = '<i class="fa-solid fa-expand"></i> ขยายเต็มจอ';[m
[32m+[m[32m            btnToggle.classList.replace('bg-red-600', 'bg-gray-700');[m
[32m+[m[32m            btnToggle.classList.replace('hover:bg-red-700', 'hover:bg-gray-800');[m
[32m+[m[32m        }[m
[32m+[m[32m    }[m
[32m+[m
     function openImageModal(expenseId, previewPath, originalPath) {[m
         const modal = document.getElementById('receiptImageModal');[m
         const grid = document.getElementById('imageGrid');[m
[36m@@ -115,10 +174,11 @@[m
 [m
         // Viewers[m
         const imgViewer = document.getElementById('modalImageViewer');[m
[31m-        const pdfContainer = document.getElementById('pdfViewerContainer'); // 🌟 2. อ้างอิง Container แทน iframe 🌟[m
[32m+[m[32m        const pdfContainer = document.getElementById('pdfViewerContainer');[m[41m [m
         const pdfViewer = document.getElementById('modalPdfViewer');[m
         const docViewer = document.getElementById('modalDocViewer');[m
[31m-        const pdfFullscreenLink = document.getElementById('modalPdfFullscreenLink'); // 🌟 ลิงก์สำหรับเปิดเต็มจอ 🌟[m
[32m+[m[41m        [m
[32m+[m[32m        // ❌ เอา const pdfFullscreenLink ออกไปแล้ว ❌[m
 [m
         const downloadArea = document.getElementById('downloadOriginalArea');[m
         const downloadLink = document.getElementById('modalDownloadLink');[m
[36m@@ -130,7 +190,7 @@[m
 [m
         // ซ่อนทั้งหมดก่อน[m
         if (imgViewer) imgViewer.classList.add('hidden');[m
[31m-        if (pdfContainer) pdfContainer.classList.add('hidden'); // ซ่อน Container แทน[m
[32m+[m[32m        if (pdfContainer) pdfContainer.classList.add('hidden');[m[41m [m
         if (pdfViewer) pdfViewer.classList.add('hidden');[m
         if (docViewer) docViewer.classList.add('hidden');[m
         if (downloadArea) downloadArea.classList.add('hidden');[m
[36m@@ -144,11 +204,11 @@[m
                     imgViewer.classList.remove('hidden');[m
                 }[m
             } else if (ext === 'pdf') {[m
[31m-                if (pdfContainer && pdfViewer && pdfFullscreenLink) {[m
[31m-                    pdfViewer.src = previewPath;[m
[31m-                    pdfFullscreenLink.href = previewPath; // 🌟 3. เซ็ต href ให้ปุ่มเต็มจอ 🌟[m
[32m+[m[32m                // 🌟 แก้ไขเงื่อนไข: เช็คแค่ Container กับ Viewer ก็พอ 🌟[m
[32m+[m[32m                if (pdfContainer && pdfViewer) {[m
[32m+[m[32m                    pdfViewer.src = previewPath + '#view=FitH';[m[41m [m
                     pdfContainer.classList.remove('hidden');[m
[31m-                    pdfContainer.classList.add('flex'); // ใช้ flex เพื่อให้ปุ่มจัดเรียงถูก[m
[32m+[m[32m                    pdfContainer.classList.add('flex');[m[41m [m
                     pdfViewer.classList.remove('hidden');[m
                 }[m
             } else {[m
[36m@@ -167,7 +227,7 @@[m
             if (oldContainer) oldContainer.classList.remove('hidden');[m
             if (grid) {[m
                 grid.classList.remove('hidden');[m
[31m-                grid.className = "grid grid-cols-1 gap-6 h-full w-full justify-items-center";[m
[32m+[m[32m                grid.className = "flex w-full h-full gap-6";[m
             }[m
             if (noImgText) noImgText.classList.add('hidden');[m
             if (btnDelete) btnDelete.classList.remove('hidden');[m
[36m@@ -182,11 +242,17 @@[m
         if (modal) modal.classList.remove('hidden');[m
     }[m
 [m
[32m+[m[32m    // 🌟 เพิ่มการสั่ง Reset เต็มจอ กลับเป็นขนาดปกติเมื่อปิด Modal 🌟[m
     function closeImageModal() {[m
         const modal = document.getElementById('receiptImageModal');[m
         if (modal) modal.classList.add('hidden');[m
         const pdfViewer = document.getElementById('modalPdfViewer');[m
         if (pdfViewer) pdfViewer.src = '';[m
[32m+[m
[32m+[m[32m        // ถ้าเปิดเต็มจอค้างไว้ ให้ย่อกลับอัตโนมัติ[m
[32m+[m[32m        if (isFullScreen) {[m
[32m+[m[32m            toggleFullScreenModal();[m
[32m+[m[32m        }[m
     }[m
 [m
     function handleFileSelect(input) {[m
[36m@@ -226,9 +292,10 @@[m
             if (noImgText) noImgText.classList.add('hidden');[m
 [m
             if (oldContainer && !oldContainer.classList.contains('hidden')) {[m
[31m-                if (grid) grid.className = "grid grid-cols-1 md:grid-cols-2 gap-6 h-full items-center";[m
[32m+[m[32m                // ถ้ามี 2 รูป แบ่งครึ่ง 50-50[m
[32m+[m[32m                if (grid) grid.className = "grid grid-cols-1 md:grid-cols-2 gap-6 h-full items-start w-full";[m
             } else {[m
[31m-                if (grid) grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";[m
[32m+[m[32m                if (grid) grid.className = "flex w-full h-full gap-6 justify-center";[m
             }[m
         }[m
     }[m
[36m@@ -248,7 +315,7 @@[m
         if (btnSubmitReupload) btnSubmitReupload.classList.add('hidden');[m
 [m
         if (oldContainer && !oldContainer.classList.contains('hidden')) {[m
[31m-            if (grid) grid.className = "grid grid-cols-1 gap-6 h-full justify-items-center";[m
[32m+[m[32m            if (grid) grid.className = "flex w-full h-full gap-6";[m
         } else {[m
             if (grid) grid.classList.add('hidden');[m
             if (noImgText) noImgText.classList.remove('hidden');[m
