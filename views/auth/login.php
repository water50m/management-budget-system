<?php ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>
        ระบบจัดการงบประมาณ
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
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-blue-600">Mali Project</h1>
            <p class="text-gray-500 text-sm">ระบบจัดการข้อมูลงานวิจัย</p>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 text-sm" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login">
    
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="mb-5 bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded shadow-sm flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <p class="font-bold text-sm">เข้าสู่ระบบไม่สำเร็จ</p>
                    <p class="text-xs">
                        <?php 
                            // แสดงข้อความตาม msg ที่ส่งมา หรือใช้ข้อความ Default
                            $msg = $_GET['msg'] ?? '';
                            if ($msg == 'invalid_credentials') {
                                echo "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                            } elseif ($msg == 'empty_fields') {
                                echo "กรุณากรอกข้อมูลให้ครบถ้วน";
                            } elseif ($msg == 'cant_server') {
                                echo "กรุณากรอกข้อมูลให้ครบถ้วน";
                            } else {
                                echo "เกิดข้อผิดพลาด กรุณาลองใหม่";
                            }
                        ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
            <input type="text" name="username" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="กรอกชื่อผู้ใช้">
        </div>
        
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="********">
        </div>
        
        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300 shadow-md">
            เข้าสู่ระบบ
        </button>
    </form>

        <div class="mt-6 text-center border-t pt-4">
            <p class="text-sm text-gray-600">ยังไม่มีบัญชี?</p>
            <a href="index.php?page=fast-login" class="text-blue-500 text-sm font-semibold hover:underline">loginความไวแสง</a>
        </div>
    </div>
</body>
</html>