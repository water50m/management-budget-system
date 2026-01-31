<?php
include_once __DIR__ . '/confirm_delete.php';
include_once __DIR__ . '/text_box_alert.php';
include_once __DIR__ . '/db.php';
include_once __DIR__ . '/add_new_profile.php';

// 1. รับค่า Tab จาก URL ถ้าไม่มีให้เป็นค่าเริ่มต้น 'approval'
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'approval';

// Helper function เพื่อลดความรกของ Class (Optional)
function getTabClass($tabName, $current_tab) {
    $baseClass = "px-4 py-2 rounded-md text-sm font-bold transition flex items-center gap-2";
    if ($tabName === $current_tab) {
        // สีตามแต่ละ Tab
        switch($tabName) {
            case 'approval': return "$baseClass bg-green-100 text-green-700 shadow-sm ring-1 ring-green-200";
            case 'expense':  return "$baseClass bg-purple-100 text-purple-700 shadow-sm ring-1 ring-purple-200";
            case 'users':    return "$baseClass bg-blue-100 text-blue-700 shadow-sm ring-1 ring-blue-200";
            case 'logs':     return "$baseClass bg-orange-100 text-orange-700 shadow-sm ring-1 ring-orange-200";
            default: return $baseClass;
        }
    }
    return "$baseClass text-gray-500 hover:bg-gray-50 hover:text-gray-700";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>
        <?php 
            if (!empty($data['title'])){
                echo $data['title'];
            } else if (!empty($title)){
                echo $title;
            } else {
                echo 'ระบบจัดการงบประมาณการวิจัย';
            }

?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>ระบบบริหารงานวิจัย (Neon Admin)</title> -->

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'fit': '1467px',
                    },
                    fontFamily: {
                        sarabun: ['Sarabun', 'sans-serif'],
                    },
                    colors: {
                        neon: {
                            pink: '#ec4899',
                            cyan: '#22d3ee',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden flex flex-col">
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

        <div class="flex bg-gray-100/80 p-1.5 rounded-lg border border-gray-200 overflow-x-auto max-w-full">
            <a href="index.php?page=dashboard&tab=approval" class="<?php echo getTabClass('approval', $current_tab); ?>">
                <i class="fas fa-check-circle"></i> <span class="whitespace-nowrap">อนุมัติ (Approved)</span>
            </a>

            <a href="index.php?page=dashboard&tab=expense" class="<?php echo getTabClass('expense', $current_tab); ?>">
                <i class="fas fa-file-invoice-dollar"></i> <span class="whitespace-nowrap">ยอดที่ขอ (Request)</span>
            </a>

            <a href="index.php?page=dashboard&tab=users" class="<?php echo getTabClass('users', $current_tab); ?>">
                <i class="fas fa-users"></i> <span class="whitespace-nowrap">ผู้ใช้งาน (Users)</span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>
                <a href="index.php?page=dashboard&tab=logs" class="<?php echo getTabClass('logs', $current_tab); ?>">
                    <i class="fas fa-history"></i> <span class="whitespace-nowrap">ประวัติ (Logs)</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4 min-w-fit">
            
            <?php if (true): ?>
                <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg shadow-md shadow-blue-100 flex items-center gap-2 transition transform hover:-translate-y-0.5">
                    <i class="fas fa-user-plus"></i> <span class="hidden sm:inline">เพิ่มบุคลากร</span>
                </button>
                <div class="h-8 w-px bg-gray-200"></div>
            <?php endif; ?>

            <a href="index.php?page=profile&tab=expense&id=<?php echo $_SESSION['user_id']; ?>" 
               class="flex items-center gap-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-lg text-sm font-bold transition shadow-sm"
               title="ดูข้อมูลส่วนตัวและรายการเบิกจ่าย">
                <i class="fas fa-user-circle"></i> 
                <span class="hidden xl:inline">ข้อมูลส่วนตัว</span>
            </a>

            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-gray-800"><?php echo $_SESSION['fullname']; ?></div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?php echo $_SESSION['role']; ?></div>
            </div>

            <a href="index.php?page=logout" class="text-gray-400 hover:text-red-500 transition p-2 rounded-full hover:bg-red-50" title="ออกจากระบบ">
                <i class="fas fa-sign-out-alt text-lg"></i>
            </a>
        </div>

    </div>
</nav>
