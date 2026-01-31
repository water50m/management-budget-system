<?php
// ตรวจสอบว่ามีการส่ง Form Action 'delete_user' มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_user') {

    // 1. รับค่า ID
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $actor_id = $_SESSION['user_id'];

    if ($target_user_id > 0) {

        // ---------------------------------------------------------
        // ✅ Step 1: ดึงข้อมูลเก่ามาตรวจสอบก่อน
        // ---------------------------------------------------------
        $sql_check = "SELECT prefix, first_name, last_name FROM user_profiles WHERE user_id = '$target_user_id'";
        $result_check = mysqli_query($conn, $sql_check);
        $old_data = mysqli_fetch_assoc($result_check);

        // ถ้าไม่พบข้อมูล
        if (!$old_data) {
            header("Location: index.php?page=users_manage&status=error&msg=" . urlencode("ไม่พบข้อมูลผู้ใช้งาน"));
            exit();
        }

        // 🚨 CRITICAL CHECK: ห้ามลบ Admin 🚨
        if (trim($old_data['first_name']) === 'Admin') {
            // ส่งกลับไปหน้าเดิมพร้อม Error
            $error_msg = "ไม่สามารถลบผู้ใช้งานระบบ (Admin) ได้";
            header("Location: index.php?page=users_manage&status=error&msg=" . urlencode($error_msg));
            exit();
        }

        // เตรียมชื่อสำหรับ Log
        $deleted_name = $old_data['prefix'] . $old_data['first_name'] . ' ' . $old_data['last_name'];

        // ---------------------------------------------------------
        // ✅ Step 2: ทำ Soft Delete (UPDATE deleted_at)
        // ---------------------------------------------------------
        $sql_delete = "UPDATE user_profiles SET deleted_at = NOW() WHERE user_id = '$target_user_id'";

        if (mysqli_query($conn, $sql_delete)) {

            // ---------------------------------------------------------
            // ✅ Step 3: บันทึก Log
            // ---------------------------------------------------------
            $log_message = "ลบข้อมูลบุคลากร: " . $deleted_name;
            logActivity($conn, $actor_id, $target_user_id, 'delete_user', $log_message);

            // ---------------------------------------------------------
            // ✅ Step 4: Redirect สำเร็จ
            // ---------------------------------------------------------
            $msg = "ลบข้อมูลของ $deleted_name เรียบร้อยแล้ว";
            header("Location: index.php?page=users_manage&status=delete&msg=" . urlencode($msg));
            exit();
        } else {
            $error_msg = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
            header("Location: index.php?page=users_manage&status=error&msg=" . urlencode($error_msg));
            exit();
        }
    } else {
        header("Location: index.php?page=users_manage&status=error&msg=" . urlencode("ไม่พบรหัสผู้ใช้งาน"));
        exit();
    }
}
?>
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-red-100">

            <form action="index.php?page=users_manage" method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="modalDeleteUserId">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">ยืนยันการลบผู้ใช้งาน</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    คุณกำลังจะลบผู้ใช้งาน: <span id="modalDeleteUserName" class="font-bold text-red-600 text-base"></span>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    การกระทำนี้ไม่สามารถย้อนกลับได้ ข้อมูลทั้งหมดที่เกี่ยวข้องจะถูกลบหรือระงับการใช้งาน
                                </p>

                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        พิมพ์คำว่า <span class="font-bold select-all bg-gray-100 px-1 rounded border border-gray-300">ลบข้อมูลบุคลากรนี้</span> เพื่อยืนยัน
                                    </label>
                                    <input type="text" id="confirmDeleteInput"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm border p-2"
                                        placeholder="ลบข้อมูลบุคลากรนี้"
                                        oninput="checkDeleteConfirmation()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" id="btnConfirmDelete" disabled
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        ยืนยันลบข้อมูล
                    </button>
                    <button type="button" onclick="closeDeleteUserModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // ฟังก์ชันเปิด Modal รับค่า ID และ ชื่อ
    function openDeleteUserModal(userId, fullName) {
        // 1. ใส่ข้อมูลลงใน Modal
        document.getElementById('modalDeleteUserId').value = userId;
        document.getElementById('modalDeleteUserName').innerText = fullName;

        // 2. Reset ช่องกรอกและปุ่ม
        const inputField = document.getElementById('confirmDeleteInput');
        const btn = document.getElementById('btnConfirmDelete');

        inputField.value = ''; // เคลียร์ค่าเก่า
        btn.disabled = true; // ปิดปุ่มไว้ก่อน
        btn.classList.add('opacity-50', 'cursor-not-allowed'); // ใส่ class ให้ดูจางๆ

        // 3. แสดง Modal
        document.getElementById('deleteUserModal').classList.remove('hidden');

        // 4. Focus ที่ช่องกรอกทันที (เพื่อความสะดวก)
        setTimeout(() => {
            inputField.focus();
        }, 100);
    }

    // ฟังก์ชันตรวจสอบคำที่พิมพ์ (เรียกใช้ตอน oninput)
    function checkDeleteConfirmation() {
        const inputVal = document.getElementById('confirmDeleteInput').value;
        const btn = document.getElementById('btnConfirmDelete');
        const confirmText = "ลบข้อมูลบุคลากรนี้"; // คำที่ต้องการให้พิมพ์

        if (inputVal === confirmText) {
            // ถ้าพิมพ์ถูก -> เปิดปุ่ม
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            // ถ้าพิมพ์ผิด -> ปิดปุ่ม
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // ฟังก์ชันปิด Modal
    function closeDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.add('hidden');
    }

    // (Optional) ทำให้กดปุ่ม Esc แล้วปิด Modal ได้
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeDeleteUserModal();
        }
    });
</script>