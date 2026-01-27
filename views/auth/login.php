<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบบริหารงานวิจัย</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"  placeholder="กรอกชื่อผู้ใช้">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"  placeholder="********">
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