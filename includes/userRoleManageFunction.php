<?php

include_once __DIR__ . "/saveLogFunction.php";
if (isset($_POST['action']) && $_POST['action'] == 'update_role' && $role == 'high-admin') {
    submitUpdateRole($conn, $redirect_url);
}
/**
 * Component ย่อย: สำหรับแสดงผลและจัดการ Role ในตาราง
 * แยกออกมาเพื่อให้โค้ดหลักอ่านง่ายขึ้น
 */
function renderUserRoleManageComponent($u, $currentUserRole)
{

?>
    <div class="flex items-center justify-center">
        <?php if ($currentUserRole == 'high-admin'): ?>
            <?php if ($u['role'] != 'high-admin'): ?>
                <form method="POST" action="index.php?page=dashboard" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">

                    <select name="new_role"
                        data-original="<?php echo $u['role']; ?>"
                        onchange="checkRoleChange(this)"
                        class="border border-blue-300 rounded text-xs px-2 py-1 bg-white focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm text-gray-700">
                        <option value="user" <?php echo $u['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>

                    <div class="role-actions hidden flex items-center gap-1">
                        <button type="submit" class="text-green-600 hover:bg-green-100 p-1 rounded transition" title="บันทึก">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button type="button" onclick="cancelRoleEdit(this)" class="text-red-500 hover:bg-red-100 p-1 rounded transition" title="ยกเลิก">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <span class="bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    High Admin
                </span>
            <?php endif; ?>
        <?php else: ?>
            <span class="px-2 py-1 rounded text-xs font-medium border
                <?php echo ($u['role'] == 'admin') ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-gray-50 text-gray-600 border-gray-200'; ?>">
                <?php echo ucfirst($u['role']); ?>
            </span>
        <?php endif; ?>
    </div>
    <script>
        // ==========================================
        // 1. จัดการ Dropdown เปลี่ยน Role (High-Admin)
        // ==========================================

        function checkRoleChange(selectElement) {
            // หาค่าเดิมที่เก็บไว้
            const originalValue = selectElement.getAttribute('data-original');
            // หา div ที่เก็บปุ่ม Save/Cancel (อยู่ถัดไปจาก select)
            const actionsDiv = selectElement.nextElementSibling;

            if (selectElement.value !== originalValue) {
                // ถ้าค่าเปลี่ยน -> เอา class 'hidden' ออก (โชว์ปุ่ม)
                actionsDiv.classList.remove('hidden');
                selectElement.classList.add('border-purple-500', 'bg-purple-50');
            } else {
                // ถ้าค่าเหมือนเดิม -> ซ่อนปุ่ม
                actionsDiv.classList.add('hidden');
                selectElement.classList.remove('border-purple-500', 'bg-purple-50');
            }
        }

        function cancelRoleEdit(btnElement) {
            // หา div พ่อ (role-actions)
            const actionsDiv = btnElement.parentElement;
            // หา select ที่เป็นพี่น้อง (อยู่ก่อนหน้า)
            const selectElement = actionsDiv.previousElementSibling;

            // คืนค่าเดิม
            selectElement.value = selectElement.getAttribute('data-original');

            // ซ่อนปุ่ม และคืนสีปกติ
            actionsDiv.classList.add('hidden');
            selectElement.classList.remove('border-purple-500', 'bg-purple-50');
        }
    </script>
<?php
}



function submitUpdateRole($conn, $redirect_url = null)
{

    // กำหนด Default URL (ค่าเดิมที่ใช้อยู่)
    // ถ้า $redirect_url เป็นค่าว่าง หรือ null ให้ใช้ค่า default นี้แทน
    $default_url = "index.php?page=dashboard&tab=users";

    // ตัวแปรสำหรับเก็บ URL ที่จะใช้จริง (ยังไม่รวม params status/msg)
    if (!empty($redirect_url)) {
        $final_redirect = $redirect_url;
    } else {
        $final_redirect = $default_url;
    }

    // 1. เช็คสิทธิ์: ต้องเป็น High Admin เท่านั้น
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'high-admin') {
        // ต่อ String เพื่อส่งค่า status
        // ใช้ strpos เช็คว่าใน URL มีเครื่องหมาย ? หรือยัง เพื่อเชื่อมด้วย & หรือ ? ให้ถูกต้อง
        $separator = (strpos($final_redirect, '?') !== false) ? '&' : '?';
        header("Location: " . $final_redirect . $separator . "status=error&msg=access_denied");
        exit();
    }

    $target_uid = intval($_POST['target_user_id']);
    $new_role   = mysqli_real_escape_string($conn, $_POST['new_role']);
    $actor_id   = $_SESSION['user_id'];

    // 2. ป้องกันการเปลี่ยนสิทธิ์ตัวเอง
    if ($target_uid == $actor_id) {
        $separator = (strpos($final_redirect, '?') !== false) ? '&' : '?';
        header("Location: " . $final_redirect . $separator . "status=error&msg=cannot_change_own_role");
        exit();
    }

    // 3. ตรวจสอบค่า Role (Whitelist)
    $allowed_roles = ['user', 'admin'];
    if (!in_array($new_role, $allowed_roles)) {
        $separator = (strpos($final_redirect, '?') !== false) ? '&' : '?';
        header("Location: " . $final_redirect . $separator . "status=error&msg=invalid_role_value");
        exit();
    }

    // 4. อัปเดตลง DB
    $sql_update = "UPDATE users SET role = '$new_role' WHERE id = $target_uid";

    if (mysqli_query($conn, $sql_update)) {
        // 5. บันทึก Log
        // (ต้องแน่ใจว่าฟังก์ชัน logActivity ถูก include เข้ามาแล้ว)
        if (function_exists('logActivity')) {
            logActivity($conn, $actor_id, $target_uid, 'update_role', "เปลี่ยนสิทธิ์เป็น $new_role");
        }

        // 6. Redirect กลับตามที่กำหนด
        $separator = (strpos($final_redirect, '?') !== false) ? '&' : '?';
        header("Location: " . $final_redirect . $separator . "status=success&msg=role_updated");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
        exit();
    }
}

// how to use
// <input type="hidden" name="current_page" value="<?php //echo htmlspecialchars($_SERVER['REQUEST_URI']); ?_"> 
//  renderUserRoleManageComponent($u, $currentUserRole); 