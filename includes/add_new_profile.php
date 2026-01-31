<?php 
global $conn;
// ตรวจสอบการเพิ่มข้อมูล User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {

    // 1. รับค่าจากฟอร์ม
    $prefix = mysqli_real_escape_string($conn, $_POST['prefix']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $department_id = intval($_POST['department_id']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // กำหนดค่า Role คงที่ = 7
    $fixed_role = 7; 
    $actor_id = $_SESSION['user_id']; // คนทำรายการ

    // 2. ตรวจสอบ Username ซ้ำ
    $check_sql = "SELECT id FROM users WHERE username = '$username'";
    if (mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        $error_msg = "Username '$username' มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น";
        header("Location: index.php?page=users_manage&status=error&msg=" . urlencode($error_msg));
        exit();
    }

    // เริ่ม Transaction (เพราะต้องบันทึก 2 ตาราง)
    mysqli_begin_transaction($conn);

    try {
        // ---------------------------------------------------------
        // Step 1: Insert ลงตาราง user_profiles ก่อน
        // ---------------------------------------------------------
        $sql_profile = "INSERT INTO user_profiles (prefix, first_name, last_name, department_id, position, created_at) 
                        VALUES ('$prefix', '$first_name', '$last_name', $department_id, '$position', NOW())";
        
        if (!mysqli_query($conn, $sql_profile)) {
            throw new Exception("บันทึก Profile ไม่สำเร็จ: " . mysqli_error($conn));
        }

        // ดึง ID ล่าสุดที่เพิ่ง Insert (p.id)
        $profile_id = mysqli_insert_id($conn);

        // ---------------------------------------------------------
        // Step 2: Insert ลงตาราง users (ผูก u.upid = p.id)
        // ---------------------------------------------------------
        // หมายเหตุ: ไม่มีการเก็บ Password ตามโจทย์
        $sql_user = "INSERT INTO users (username, role, upid, created_at) 
                     VALUES ('$username', $fixed_role, $profile_id, NOW())";

        if (!mysqli_query($conn, $sql_user)) {
            throw new Exception("บันทึก User ไม่สำเร็จ: " . mysqli_error($conn));
        }

        // ✅ Commit ข้อมูลเมื่อผ่านทั้งคู่
        mysqli_commit($conn);

        // ---------------------------------------------------------
        // Step 3: บันทึก Log
        // ---------------------------------------------------------
        $fullname = "$prefix$first_name $last_name";
        logActivity($conn, $actor_id, $profile_id, 'add_user', "เพิ่มผู้ใช้งานใหม่: $fullname (User: $username)");

        // Redirect Success
        header("Location: index.php?page=users_manage&status=add&msg=" . urlencode("เพิ่มข้อมูล $fullname เรียบร้อยแล้ว"));
        exit();

    } catch (Exception $e) {
        // ❌ Rollback หากเกิดข้อผิดพลาด
        mysqli_rollback($conn);
        header("Location: index.php?page=users_manage&status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}

// --- ดึงข้อมูล Master Data สำหรับ Dropdown ---
// ดึงรายชื่อภาควิชา
$dept_sql = "SELECT id, thai_name FROM departments ORDER BY thai_name ASC";
$dept_query = mysqli_query($conn, $dept_sql);

// รายชื่อตำแหน่ง (ถ้าไม่มีตารางแยก ให้ใช้ Array แบบนี้)
$positions = ['อาจารย์', 'เจ้าหน้าที่', 'นักวิจัย', 'ผู้บริหาร', 'นิสิตช่วยงาน'];
?>
<div id="addUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-2xl border border-blue-100">
            
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> เพิ่มข้อมูลบุคลากร
                </h3>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-blue-100 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form action="index.php?page=users_manage" method="POST" class="px-6 py-6 space-y-6">
                <input type="hidden" name="action" value="add_user">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 mb-1">คำนำหน้า <span class="text-red-500">*</span></label>
                        <select name="prefix" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">เลือก</option>
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                            <option value="ดร.">ดร.</option>
                            <option value="ผศ.ดร.">ผศ.ดร.</option>
                            <option value="รศ.ดร.">รศ.ดร.</option>
                            <option value="ศ.ดร.">ศ.ดร.</option>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-gray-700 mb-1">ชื่อจริง <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="ภาษาไทย/อังกฤษ">
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-xs font-bold text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="ภาษาไทย/อังกฤษ">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">ภาควิชา/สังกัด <span class="text-red-500">*</span></label>
                        <select name="department_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">-- กรุณาเลือกภาควิชา --</option>
                            <?php while($dept = mysqli_fetch_assoc($dept_query)): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['thai_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">ตำแหน่ง <span class="text-red-500">*</span></label>
                        <select name="position" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">-- เลือกตำแหน่ง --</option>
                            <?php foreach($positions as $pos): ?>
                                <option value="<?php echo $pos; ?>"><?php echo $pos; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <h4 class="text-sm font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-lock"></i> ข้อมูลสำหรับเข้าสู่ระบบ
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" required autocomplete="off"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 font-mono text-blue-600 font-bold" 
                                   placeholder="เช่น somchai.j">
                            <p class="text-[10px] text-gray-500 mt-1">* ใช้สำหรับ Login (ไม่ใช้รหัสผ่าน)</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">สิทธิ์การใช้งาน (Role)</label>
                            <input type="text" value="User (Level 7)" disabled class="w-full bg-gray-100 border-gray-300 text-gray-500 rounded-lg text-sm py-2 cursor-not-allowed">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 pt-2 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" 
                            class="w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                        ยกเลิก
                    </button>
                    <button type="submit" 
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md font-medium transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
