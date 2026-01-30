<?php 
function showToast($type, $message) {
    $bgColor = 'bg-slate-900'; 
    $borderColor = ($type == 'success') ? 'border-cyan-400' : 'border-pink-500';
    $textColor = ($type == 'success') ? 'text-cyan-400' : 'text-pink-500';
    $neonShadow = ($type == 'success') ? 'neon-shadow-success' : 'neon-shadow-error';
    $icon = ($type == 'success') ? 
        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : 
        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
?>
    <div id='toast-container' 
         class='fixed bottom-5 right-5 z-50 transition-all duration-500 transform translate-y-0 opacity-100'
         onmouseenter="pauseToastTimer()" 
         onmouseleave="resumeToastTimer()">
        
        <div class='flex items-center p-4 mb-4 <?= $bgColor ?> border <?= $borderColor ?> <?= $textColor ?> rounded-lg <?= $neonShadow ?>' role='alert'>
            <div class='inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg'>
                <?= $icon ?>
            </div>
            <div class='ml-3 text-sm font-bold tracking-wide uppercase'>
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
