<?php 
function showToast($type, $message) {
    // กำหนดค่า Default (สีพื้นหลัง)
    $bgColor = 'bg-slate-900'; 
    
    // ตั้งค่าสีและไอคอนตาม Type
    switch ($type) {
        case 'deleted':
            // สีแดง/ชมพู สำหรับการลบ
            $borderColor = 'border-pink-500';
            $iconColor = 'text-pink-500';
            $textColor = 'text-white';
            $neonShadow = 'neon-shadow-error'; // ใช้ Shadow สีแดงเดิม
            $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'; // ไอคอนถังขยะ
            break;

        case 'restore':
            // สีฟ้า/น้ำเงิน สำหรับการกู้คืน
            $borderColor = 'border-blue-400';
            $iconColor = 'text-blue-400';
            $textColor = 'text-white';
            $neonShadow = 'shadow-[0_0_10px_rgba(96,165,250,0.5)]'; // Shadow สีฟ้า (Custom Tailwind)
            $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>'; // ไอคอน Refresh/Restore
            break;

        case 'error':
            // สีส้ม/แดง สำหรับ Error จริงๆ
            $borderColor = 'border-red-600';
            $iconColor = 'text-red-600';
            $textColor = 'text-red-600';
            $neonShadow = 'neon-shadow-error';
            $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'; // ไอคอนตกใจ
            break;

        case 'success':
        case 'add':
        default:
            // สีเขียว/Cyan สำหรับเพิ่มข้อมูล หรือ Success ปกติ
            $borderColor = 'border-cyan-400';
            $iconColor = 'text-cyan-400';
            $textColor = 'text-white';
            $neonShadow = 'neon-shadow-success';
            $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'; // ไอคอนถูก
            break;
    }
?>
    <div id='toast-container' 
         class='fixed bottom-5 right-5 z-50 transition-all duration-500 transform translate-y-0 opacity-100'
         onmouseenter="pauseToastTimer()" 
         onmouseleave="resumeToastTimer()">
        
        <div class='flex items-center p-4 mb-4 <?= $bgColor ?> border <?= $borderColor ?> <?= $iconColor ?> rounded-lg <?= $neonShadow ?> shadow-lg' role='alert'>
            <div class='inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg'>
                <?= $icon ?>
            </div>
            <div class='ml-3 text-sm font-bold tracking-wide uppercase <?= $textColor ?> whitespace-pre-line'>
                <?= htmlspecialchars($message) ?>
            </div>
            <button type='button' onclick='closeToast()' class='ml-auto -mx-1.5 -my-1.5 p-1.5 inline-flex items-center justify-center h-8 w-8 <?= $textColor ?> hover:opacity-70 transition-opacity'>
                <svg class='w-3 h-3' fill='none' viewBox='0 0 14 14'>
                    <path stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6'/>
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
