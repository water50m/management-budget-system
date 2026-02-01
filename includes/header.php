<?php
// header.php


// 1. รับค่า Tab จาก URL ถ้าไม่มีให้เป็นค่าเริ่มต้น 'received'
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'received';
$page = $_GET['page'] ?? 'dashboard';

// ✅ ถ้าหน้าปัจจุบันไม่ใช่ dashboard ไม่ต้องให้ tab ไหนเด่นทั้งนั้น
if ($page !== 'dashboard') {
    $current_tab = 'none';
}
// Helper function: ใช้สำหรับ Initial Load (ตอนเข้าเว็บครั้งแรก)
function getTabClass($tabName, $current_tab)
{
    $baseClass = "nav-tab px-4 py-2 rounded-md text-sm font-bold transition flex items-center gap-2 cursor-pointer"; // เพิ่ม class 'nav-tab' ไว้ใช้อ้างอิงใน JS

    // กำหนดสีตอน Active (เก็บไว้ในตัวแปรเพื่อใช้ใน JS ด้วย)
    $activeColors = [
        'summary' => "bg-indigo-100 text-indigo-700 shadow-sm ring-1 ring-indigo-200",
        'received' => "bg-green-100 text-green-700 shadow-sm ring-1 ring-green-200",
        'expense'  => "bg-purple-100 text-purple-700 shadow-sm ring-1 ring-purple-200",
        'users'    => "bg-blue-100 text-blue-700 shadow-sm ring-1 ring-blue-200",
        'logs'     => "bg-orange-100 text-orange-700 shadow-sm ring-1 ring-orange-200",
    ];

    $inactiveClass = "text-gray-500 hover:bg-gray-50 hover:text-gray-700";

    if ($tabName === $current_tab) {
        return "$baseClass " . $activeColors[$tabName];
    }
    return "$baseClass $inactiveClass";
}
$inactive_style = "text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-transparent";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ระบบบริหารงานวิจัย</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/htmx.org@1.9.10"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sarabun: ['Sarabun', 'sans-serif']
                    },
                    colors: {
                        neon: {
                            pink: '#ec4899',
                            cyan: '#22d3ee'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 h-screen lg:overflow-hidden flex flex-col font-sarabun">

    <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-3 sticky top-0 z-40">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">

            <div class="flex items-center gap-3 min-w-fit">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-200">
                    <i class="fas fa-seedling text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800 leading-tight">ระบบบริหารงานวิจัย</h1>
                    <p class="text-[10px] font-semibold text-blue-600 uppercase tracking-wider">Mali Project</p>
                </div>
            </div>
            <?php if ($_SESSION['role'] != 'user'): ?>
                <div class="flex bg-gray-100/80 p-1.5 rounded-lg border border-gray-200 overflow-x-auto max-w-full" id="nav-container">

                    <a href="javascript:void(0)"
                        hx-get="index.php?page=dashboard&tab=summary"
                        hx-target="#app-container"
                        hx-push-url="true"
                        data-tab-name="summary"
                        data-active-class="bg-indigo-100 text-indigo-700 shadow-sm ring-1 ring-indigo-200"
                        data-default-class="<?php echo $inactive_style; ?>"
                        class="<?php echo getTabClass('summary', $current_tab); ?>">
                        <i class="fas fa-chart-pie"></i> <span class="whitespace-nowrap ml-1">ภาพรวม (Overview)</span>
                    </a>

                    <a href="javascript:void(0)"
                        hx-get="index.php?page=dashboard&tab=received"
                        hx-target="#app-container"
                        hx-push-url="true"
                        data-tab-name="received"
                        data-active-class="bg-green-100 text-green-700 shadow-sm ring-1 ring-green-200"
                        data-default-class="<?php echo $inactive_style; ?>"
                        class="<?php echo getTabClass('received', $current_tab); ?>">
                        <i class="fas fa-check-circle"></i> <span class="whitespace-nowrap ml-1">ยอดที่รับ (Received)</span>
                    </a>

                    <a href="javascript:void(0)"
                        hx-get="index.php?page=dashboard&tab=expense"
                        hx-target="#app-container"
                        hx-push-url="true"
                        data-tab-name="expense"
                        data-active-class="bg-purple-100 text-purple-700 shadow-sm ring-1 ring-purple-200"
                        data-default-class="<?php echo $inactive_style; ?>"
                        class="<?php echo getTabClass('expense', $current_tab); ?>">
                        <i class="fas fa-file-invoice-dollar"></i> <span class="whitespace-nowrap ml-1">ยอดที่ตัด (Expense)</span>
                    </a>

                    <a href="javascript:void(0)"
                        hx-get="index.php?page=dashboard&tab=users"
                        hx-target="#app-container"
                        hx-push-url="true"
                        data-tab-name="users"
                        data-active-class="bg-blue-100 text-blue-700 shadow-sm ring-1 ring-blue-200"
                        data-default-class="<?php echo $inactive_style; ?>"
                        class="<?php echo getTabClass('users', $current_tab); ?>">
                        <i class="fas fa-users"></i> <span class="whitespace-nowrap ml-1">ผู้ใช้งาน (Users)</span>
                    </a>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>
                        <a href="javascript:void(0)"
                            hx-get="index.php?page=dashboard&tab=logs"
                            hx-target="#app-container"
                            hx-push-url="true"
                            data-tab-name="logs"
                            data-active-class="bg-orange-100 text-orange-700 shadow-sm ring-1 ring-orange-200"
                            data-default-class="<?php echo $inactive_style; ?>"
                            class="<?php echo getTabClass('logs', $current_tab); ?>">
                            <i class="fas fa-history"></i> <span class="whitespace-nowrap ml-1">ประวัติ (Logs)</span>
                        </a>
                    <?php endif; ?>

                </div>
                <div class="flex items-center gap-4 min-w-fit">

                    <button onclick="openAddUserModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg shadow-md flex items-center gap-2 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-user-plus"></i> <span class="hidden sm:inline">เพิ่มบุคลากร</span>
                    </button>
                    <div class="flex items-center gap-2">

                        <div class="h-8 w-px bg-gray-200 mx-1"></div>

                        <a href="javascript:void(0)"
                            hx-get="index.php?page=profile&id=<?php echo $_SESSION['user_id']; ?>"
                            hx-target="#app-container"
                            hx-swap="innerHTML"
                            hx-push-url="true"
                            class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-gray-50 transition group border border-transparent hover:border-gray-200">

                            <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition duration-200">
                                <i class="fas fa-user-circle text-xl"></i>
                            </div>

                            <div class="text-left hidden sm:block">
                                <div class="text-sm font-bold text-gray-700 group-hover:text-indigo-700 transition">
                                    <?php echo $_SESSION['fullname']; ?>
                                </div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider leading-tight">
                                    <?php echo $_SESSION['role']; ?>
                                </div>
                            </div>

                        </a>

                    </div>

                    <a href="index.php?page=logout" class="text-gray-400 hover:text-red-500 transition p-2 rounded-full hover:bg-red-50">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </nav>
    <div id="app-container" class="flex-1 flex flex-col min-h-0 bg-gray-50">

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.nav-tab');
                const inactiveClass = "text-gray-500 hover:bg-gray-50 hover:text-gray-700";

                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        // 1. Reset ทุก Tab ให้เป็นสีจาง
                        tabs.forEach(t => {
                            const activeColor = t.getAttribute('data-active-class');
                            t.className = t.className.replace(activeColor, '').trim(); // ลบสีเข้มออก

                            if (!t.className.includes(inactiveClass)) {
                                t.className += " " + inactiveClass; // เติมสีจางกลับไป
                            }
                        });

                        // 2. Set Tab ที่ถูกคลิกให้เป็นสีเข้ม
                        const myActiveColor = this.getAttribute('data-active-class');
                        this.className = this.className.replace(inactiveClass, '').trim(); // ลบสีจางออก
                        this.className += " " + myActiveColor; // เติมสีเข้มเข้าไป
                    });
                });
            });

            // ฟังก์ชันเปิด Modal (กันเหนียว เผื่อไม่มี)
            function openAddUserModal() {
                const modal = document.getElementById('addUserModal');
                if (modal) modal.classList.remove('hidden');
            }
        </script>