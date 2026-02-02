<?php

// showToast('restore', 'This is operation test toast alert!');

function showToast($type, $message)
{
    // --- Style พื้นฐาน (Theme สว่าง สะอาดตา) ---
    // bg-white: พื้นขาว
    // shadow-lg: เงาแบบนุ่ม ดูมีมิติ
    // border border-gray-100: ขอบจางๆ เพื่อตัดกับพื้นหลังเว็บ
    $baseClass = 'bg-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-200 border-l-4';
    switch ($type) {
        case 'deleted':
        case 'error':
            // สีแดง (Error / Delete)
            // ใช้ text-red-600 สำหรับไอคอน และ bg-red-50 สำหรับพื้นหลังไอคอน
            $statusClass = 'border-l-red-500';
            $iconBg = 'bg-red-50';
            $iconColor = 'text-red-500';
            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'; // ไอคอนกากบาทวงกลม
            break;

        case 'restore':
            // สีฟ้า (Info / Restore)
            $statusClass = 'border-l-blue-500';
            $iconBg = 'bg-blue-50';
            $iconColor = 'text-blue-500';
            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
            break;

        case 'success':
        case 'add':
        default:
            // สีเขียว (Success)
            $statusClass = 'border-l-emerald-500';
            $iconBg = 'bg-emerald-50';
            $iconColor = 'text-emerald-500';
            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'; // ไอคอนติ๊กถูกวงกลม
            break;
    }


    // จัดการ URL สำหรับปุ่ม
    $showbtn = $_SESSION['show_btn']  ?? false;
    $targetTab = $_SESSION['tragettab'] ?? '';
    $targetFilters = $_SESSION['tragetfilters'] ?? 0;
    $redirectUrl = "index.php?page=dashboard&tab=" . urlencode($targetTab) . "&show_id=" . urlencode($targetFilters);
    unset($_SESSION['show_btn']);
    unset($_SESSION['tragettab']);
    unset($_SESSION['tragetfilters']);
?>

    <div id='toast-container'
        class='fixed bottom-5 right-5 z-50 transition-all duration-500 transform translate-y-0 opacity-100 font-sans'
        onmouseenter="pauseToastTimer()"
        onmouseleave="resumeToastTimer()">

        <div class='flex items-start p-4 mb-4 <?= $baseClass ?>  <?= $statusClass ?> rounded-xl w-auto max-w-md' role='alert'>

            <div class='inline-flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full <?= $iconBg ?> <?= $iconColor ?>'>
                <?= $icon ?>
            </div>

            <div class='ml-3 pt-0.5'>
                <p class='text-sm font-medium text-gray-800 leading-snug'>
                    <?= htmlspecialchars($message) ?>
                </p>

                <?php if (isset($showbtn) && $showbtn == true): ?>
                    <div class="mt-2">
                        <a href="<?= $redirectUrl ?>"
                            class="inline-flex items-center text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">
                            ดูรายการ
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <button type='button' onclick='closeToast()' class='ml-4 -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors'>
                <svg class='w-3 h-3' fill='none' viewBox='0 0 14 14'>
                    <path stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6' />
                </svg>
            </button>
        </div>
    </div>

    <script>
        (function() {
            let toastTimer;
            const duration = 5000; // 5 วินาที

            window.closeToast = function() {
                const toast = document.getElementById('toast-container');
                if (toast) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(20px)';
                    setTimeout(() => toast.remove(), 500);
                }
                clearInterval(toastTimer);
            };

            window.pauseToastTimer = function() {
                clearInterval(toastTimer);
                // (Optional) เพิ่มเอฟเฟกต์ตอน hover ให้ดูชัดเจนขึ้น
                document.getElementById('toast-container').style.transform = 'scale(1.02)';
            };

            window.resumeToastTimer = function() {
                document.getElementById('toast-container').style.transform = 'scale(1)';
                startToastTimer();
            };

            function startToastTimer() {
                clearInterval(toastTimer);
                toastTimer = setInterval(window.closeToast, duration);
            }

            // เริ่มนับครั้งแรก
            startToastTimer();
        })();
    </script>
<?php
}
// if ($success) {
//     header("Location: index.php?status=success&toastMsg=ลบข้อมูลสำเร็จ");
// } else {
//     header("Location: index.php?status=error&toastMsg=ลบไม่สำเร็จ");
// }
// exit();
if (isset($_GET['status']) && isset($_GET['toastMsg'])) {

    $status = $_GET['status']; // success หรือ error
    $msg = htmlspecialchars($_GET['toastMsg']); // ข้อความ

    // เรียกใช้ฟังก์ชัน Toast
    showToast($status, $msg);
}
