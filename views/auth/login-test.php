<?php

// 2. ตรวจสอบ Cookie (ถ้ายังไม่ได้ Login Session)
if (isset($_GET['status']) && !empty($_SESSION['error']) && $_GET['status'] == 'error') {
    $msg = isset($_GET['msg']) ? $_GET['msg'] : '';
    if ($msg == 'invalid_credentials') {
        $trasnlate_msg = 'username หรือ password ไม่ถูกต้อง';
    } else if ($msg == 'unknow_username') {
        $trasnlate_msg = 'ยังไม่มีข้อมูลของท่านในระบบ กรุณาติดต่อแอดมิน';
    } else if ($msg == 'unknow_username') {
        $trasnlate_msg = 'ยังไม่มีข้อมูลของท่านในระบบ กรุณาติดต่อแอดมิน';
    } else if ($msg == 'empty_fields') {
        $trasnlate_msg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $trasnlate_msg = 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ กรุณาลองใหม่อีกครั้ง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบบริหารงบประมาณ FPA</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        // เปลี่ยนสีหลักเป็นสีเหลือง/ส้ม (Amber)
                        primary: '#f59e0b', // Amber 500
                        primaryDark: '#d97706', // Amber 600 (สำหรับ Hover)
                        secondary: '#546e7a',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-amber-50/50 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden max-w-5xl w-full flex flex-col md:flex-row min-h-[650px] transition-all duration-300 hover:shadow-[0_25px_50px_-12px_rgba(251,191,36,0.2)]">

        <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white relative">

            <div class="mb-8 text-center md:text-left">
                <div class="inline-block px-4 py-1.5 rounded-full bg-amber-100 text-primaryDark text-xs font-bold tracking-wider mb-4 border border-amber-200">
                    FPA SYSTEM
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2 leading-tight">
                    ระบบบริหารงบประมาณ
                </h1>
                <p class="text-gray-500 font-light text-sm md:text-base">
                    เพื่อการพัฒนาวิชาการและการวิจัย
                </p>
            </div>

            <form action="index.php?page=ldap-test" method="POST" class="space-y-5">

                <?php if (isset($trasnlate_msg) && !empty($trasnlate_msg)): ?>
                <?php endif; ?>
                <?php if (isset($remembered_user) && false): ?>

                    <div class="text-center space-y-4 py-4">
                        <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto border-4 border-blue-100 text-primary text-4xl shadow-sm">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>
                            <p class="text-gray-500 text-sm">ยินดีต้อนรับ,</p>
                            <h3 class="text-xl font-bold text-gray-800 mt-1">
                                <?php echo htmlspecialchars($remembered_user['prefix'] . ' ' . $remembered_user['first_name'] . ' ' . $remembered_user['last_name']); ?>
                            </h3>
                        </div>

                        <input type="hidden" name="login_via_remember" value="<?php echo $remembered_user['user_id']; ?>">
                        <div class="space-y-3 pt-2">
                            <button type="submit" 
                                class="w-full bg-primary hover:bg-primaryDark text-white font-bold py-3.5 rounded-xl shadow-lg shadow-amber-500/20 transform active:scale-[0.98] transition-all duration-200 text-lg flex items-center justify-center gap-2 group">
                                <span>ดำเนินการต่อ</span>
                                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>

                            <a href="index.php?page=login&action=switch_account"
                                class="block w-full bg-white border border-gray-200 text-gray-500 font-medium py-3.5 rounded-xl hover:bg-gray-50 hover:text-gray-700 transition-colors text-center">
                                ไม่ใช่ฉัน? เปลี่ยนบัญชีผู้ใช้
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 block">ชื่อผู้ใช้งาน</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-primary transition-colors">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <input type="text" name="username" class="w-full py-3.5 pl-11 pr-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200 placeholder-gray-400" placeholder="ระบุ Username" required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 block">รหัสผ่าน</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-primary transition-colors">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <input type="password" name="password" class="w-full py-3.5 pl-11 pr-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200 placeholder-gray-400" placeholder="ระบุ Password" required>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-primary border-gray-300 focus:ring-primary/30 accent-amber-500">
                            <span class="text-gray-500">จดจำฉันไว้ในระบบ</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primaryDark text-white font-bold py-4 rounded-xl shadow-lg shadow-amber-500/20 transform active:scale-[0.98] transition-all duration-200 text-lg flex items-center justify-center gap-2 group">
                        <span>เข้าสู่ระบบ</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                <?php endif; ?>
            </form>

            <div class="mt-8 pt-6 border-t border-dashed border-gray-200">
                <p class="text-center text-xs text-gray-400 mb-4 uppercase tracking-wider font-semibold">
                    --- Developer Access (Temporary) ---
                </p>
                <div class="flex gap-3">
                    <button type="button" onclick="devLogin('user')"
                        class="flex-1 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-tag text-gray-500"></i> Mock User
                    </button>
                    <button type="button" onclick="devLogin('admin')"
                        class="flex-1 py-2.5 bg-gray-800 text-gray-100 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-shield text-amber-400"></i> Mock Admin
                    </button>
                </div>
            </div>
                        <!-- <div class="mt-8 pt-6 border-t border-dashed border-gray-200 text-center">
    <p class="text-xs text-gray-400 mb-3 uppercase tracking-wider font-semibold">
        ต้องการความช่วยเหลือ?
    </p>
    <a href="#" class="inline-flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-primary transition-colors">
        <i class="fa-solid fa-headset text-gray-400"></i>
        <span>ติดต่อฝ่าย IT Support / ลืมรหัสผ่าน</span>
    </a>
</div> -->


            <div class="mt-8 text-center md:text-left text-xs text-gray-400">
                &copy; 2026 Faculty of Medical Sciences.
            </div>
        </div>

        <div class="hidden md:block w-1/2 relative bg-gray-600 overflow-hidden">
            <img src="assets/images/bg-medsci-sunset.png"
                class="absolute inset-0 w-full h-full object-cover object-center scale-105 hover:scale-110 transition-transform duration-1000 ease-out opacity-80"
                alt="Building Background">

            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-amber-400/40 to-gray-900/30 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-transparent opacity-60 mix-blend-overlay"></div>

            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[85%] z-10 text-white">
                <div class="backdrop-blur-md bg-white/10 p-8 rounded-3xl border border-white/20 shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] text-center">
                    <div class="mb-4 text-primaryDark bg-amber-100/80 w-12 h-12 rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                        <i class="fa-solid fa-building-columns text-xl"></i>
                    </div>
                    <h3 class="font-bold text-2xl mb-3 text-white text-shadow-sm">ยินดีต้อนรับสู่ระบบ FPA</h3>
                    <p class="text-sm text-amber-50 font-light leading-relaxed opacity-95">
                        ระบบสารสนเทศเพื่อการบริหารจัดการงบประมาณอย่างมีประสิทธิภาพ เพื่อยกระดับการพัฒนาวิชาการ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function devLogin(role) {
            alert(`[DEV MODE]\nกำลังเข้าสู่ระบบในฐานะ: ${role.toUpperCase()}\n(Mock Login Success)`);

            // ส่งค่า role ไปตรงๆ เลย เช่น index.php?...&mock=admin
            window.location.href = `index.php?page=fast-login&mock=${role}`;
        }
    </script>

</body>

</html>