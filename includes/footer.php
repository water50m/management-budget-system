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
</script>
</body>
</html>
