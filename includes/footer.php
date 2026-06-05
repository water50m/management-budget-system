<?php
include_once __DIR__ . '/toast.php';

if (!isset($conn)) {
    include_once __DIR__ . '/db.php';
}

$evaluationLink = '';
if (isset($conn)) {
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'system_links'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $linkKey = 'system_evaluation';
        $stmt = $conn->prepare("SELECT link_url FROM system_links WHERE link_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $linkKey);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $evaluationLink = $row['link_url'] ?? '';
            $stmt->close();
        }
    }
}
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
<footer class="flex-shrink-0 bg-white border-t border-gray-200 px-6 py-2 text-center text-sm font-medium">
    <?php if ($evaluationLink !== ''): ?>
        <a href="<?= htmlspecialchars($evaluationLink) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 no-underline cursor-pointer hover:text-blue-700">
            ประเมินประสิทธิภาพการใช้งานระบบ
        </a>
    <?php else: ?>
        <span class="text-blue-600 no-underline">ประเมินประสิทธิภาพการใช้งานระบบ</span>
    <?php endif; ?>
</footer>
</body>

</html>
