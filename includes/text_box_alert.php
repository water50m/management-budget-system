<div id="global_alert_box" 
     class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-300 ease-out opacity-0 translate-y-full pointer-events-none">
    <div class="bg-gray-800 text-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 border border-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span id="global_alert_text" class="font-medium text-sm tracking-wide">
            ข้อความแจ้งเตือนจะขึ้นตรงนี้
        </span>
    </div>
</div>

<script>
    {
    const alertBox = document.getElementById('global_alert_box');
    const alertText = document.getElementById('global_alert_text');

    function showGlobalAlert(message) {
        // 1. ใส่ข้อความ
        alertText.textContent = message;
        
        // 2. แสดงกล่อง (เลื่อนขึ้นมาและชัดขึ้น)
        alertBox.classList.remove('opacity-0', 'translate-y-full');
        alertBox.classList.add('opacity-100', 'translate-y-0');
    }

    function hideGlobalAlert() {
        // ซ่อนกล่อง (เลื่อนลงไปและจางหาย)
        alertBox.classList.remove('opacity-100', 'translate-y-0');
        alertBox.classList.add('opacity-0', 'translate-y-full');
    }
}
</script>

<!--------------------------------------------------การนำไปใช้ ----------------------------------------------------->

<!-- 
        <div class="inline-block"
             onmouseenter="showGlobalAlert('⚠️ ไม่สามารถลบได้: งบประมาณบางส่วนถูกใช้ไปแล้ว')"
             onmouseleave="hideGlobalAlert()">
             
            <button type="button" class="text-gray-300 cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </div>
-->