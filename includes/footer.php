<?php
include_once __DIR__ . '/toast.php';
?>
<script src="assets/js/utils.js"></script>
<script src="assets/js/dashboard.js"></script>


<script>
    // รอให้หน้าเว็บโหลดเสร็จก่อน
    document.addEventListener("DOMContentLoaded", function() {

        // ตรวจสอบว่าใน URL มี parameter ชื่อ 'status' หรือ 'msg' ไหม
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status') || urlParams.has('toastMsg')) {

            // ใช้ History API เพื่อเปลี่ยน URL โดยไม่ Refresh หน้า
            // แบบนี้: index.php?page=dashboard&status=success -> index.php?page=dashboard

            // 1. สร้าง URL ใหม่โดยเอา parameter ที่ไม่ต้องการออก
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.delete('toastMsg'); // เผื่อคุณใช้ชื่อนี้

            // 2. สั่งเปลี่ยน URL ทันที (User จะไม่รู้ตัว)
            window.history.replaceState({}, document.title, newUrl.toString());
        }
    });

    document.body.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (!link) return;

    // กรณีกด Tab
    if (link.getAttribute('data-tab-name')) {
        // 1. Reset ทุกปุ่มให้เป็นสีเทา (Default)
        resetAllTabs();

        // 2. ใส่สี Active ให้ปุ่มที่กด และลบสี Default ออก
        const activeClass = link.getAttribute('data-active-class');
        const defaultClass = link.getAttribute('data-default-class');
        
        if (activeClass) link.classList.add(...activeClass.split(' '));
        if (defaultClass) link.classList.remove(...defaultClass.split(' ')); // เอาสีเทาออก
    } 
    // กรณีเปลี่ยนหน้า (เช่นกด Profile)
    else {
        const isPageChange = link.hasAttribute('hx-get') || 
                             (link.getAttribute('href') && link.getAttribute('href') !== 'javascript:void(0)');
        if (isPageChange) {
            resetAllTabs();
        }
    }
});

function resetAllTabs() {
    const allTabs = document.querySelectorAll('[data-tab-name]');
    allTabs.forEach(tab => {
        const activeClass = tab.getAttribute('data-active-class');
        const defaultClass = tab.getAttribute('data-default-class');

        // เอาสี Active ออก
        if (activeClass) tab.classList.remove(...activeClass.split(' '));
        
        // เอาสี Default (สีเทา) ใส่กลับเข้าไป
        if (defaultClass) tab.classList.add(...defaultClass.split(' '));
    });
}
</script>
</div>
</body>

</html>