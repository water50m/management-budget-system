<?php
include_once __DIR__ . "/saveLogFunction.php";


// ดักจับการ Submit (ถ้ามีการส่ง form มา)
if (isset($_POST['action']) && $_POST['action'] == 'update_role') {
    submitUpdateRole($conn, $redirect_url);
}

/**
 * Component ย่อย: สำหรับแสดงผลและจัดการ Role ในตาราง
 * * @param array $u ข้อมูล User แถวนั้นๆ (ต้องมี u.id และ u.role_id หรือ u.role_name)
 * @param string $currentUserRole Role ของคนที่ Login อยู่ (เช่น 'high-admin')
 * @param object $conn Database Connection (ต้องส่งมาเพื่อ Query Role List)
 */
function renderUserRoleManageComponent($u, $currentUserRole, $conn)
{

    // 1. ดึงรายการ Role ทั้งหมดจาก DB เพื่อมาทำ Dropdown
    // เรียงตาม ID หรือตามความเหมาะสม
    $role_query = "SELECT id,role_name, description FROM roles ORDER BY id ASC";
    $role_result = mysqli_query($conn, $role_query);
    
    $roles_list = [];
    while ($row = mysqli_fetch_assoc($role_result)) {
        $roles_list[] = $row;
    }
?>
    <div class="flex items-center justify-center">
        <?php 
        // 2. เช็คสิทธิ์: คนใช้งานต้องเป็น 'high-admin' เท่านั้น ถึงจะแก้ได้
        if ($currentUserRole == 'high-admin'): 
        ?>
            
            <?php 
            // 3. ป้องกันไม่ให้แก้ Role ของคนที่เป็น high-admin ด้วยกัน (เดี๋ยวไม่มีใครคุมระบบ)
            // หรือถ้าอยากให้แก้ได้ ก็เอาเงื่อนไขนี้ออก
            if ($u['role_id'] != '1'): 
            ?>
                <form method="POST" action="index.php?page=dashboard" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">

                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

                    <select name="new_role"
                        data-original="<?php echo $u['role_id']; ?>"
                        onchange="checkRoleChange(this)"
                        class="border border-blue-300 rounded text-xs px-2 py-1 bg-white focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm text-gray-700 min-w-[120px]">
                        
                        <?php foreach ($roles_list as $role): ?>
                            <option value="<?php echo $role['id']; ?>" 
                                <?php echo ($u['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['description']); ?>
                            </option>
                        <?php endforeach; ?>

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
                    <?php 
                        // หา description ของ high-admin มาโชว์ (ถ้าหาไม่เจอโชว์ชื่อ role)
                        $key = array_search('high-admin', array_column($roles_list, 'role_name'));
                        echo ($key !== false) ? $roles_list[$key]['description'] : 'True Admin';
                    ?>
                </span>
            <?php endif; ?>

        <?php else: ?>
            <?php 
                // Map ชื่อ Role เป็น Description เพื่อแสดงผล
                $key = array_search($u['role_id'], array_column($roles_list, 'role_name'));
                $display_role = ($key !== false) ? $roles_list[$key]['description'] : ucfirst($u['role_id']);
                
                // สี Badge ตาม Role (ตัวอย่าง)
                if ($u['role_id'] == 1) {
                    // High Admin: สีแดง
                    $badge_class = 'bg-red-50 text-red-700 border-red-200';
                } elseif ($u['role_id'] >= 2 && $u['role_id'] <= 6) {
                    // Admin ภาควิชาต่างๆ (ID 2-6): สีส้ม
                    $badge_class = 'bg-orange-50 text-orange-700 border-orange-200';
                } else {
                    // User (ID 7) หรืออื่นๆ: สีเทา
                    $badge_class = 'bg-gray-50 text-gray-600 border-gray-200';
                }
            ?>
            <span class="px-2 py-1 rounded text-xs font-medium border <?php echo $badge_class; ?>">
                <?php echo $display_role; ?>
            </span>
        <?php endif; ?>
    </div>
    
    <script>
        function checkRoleChange(selectElement) {
            const originalValue = selectElement.getAttribute('data-original');
            const actionsDiv = selectElement.nextElementSibling;
            // ... (Logic เดิม) ...
            if (selectElement.value !== originalValue) {
                actionsDiv.classList.remove('hidden');
                selectElement.classList.add('border-purple-500', 'bg-purple-50');
            } else {
                actionsDiv.classList.add('hidden');
                selectElement.classList.remove('border-purple-500', 'bg-purple-50');
            }
        }
        function cancelRoleEdit(btnElement) {
            const actionsDiv = btnElement.parentElement;
            const selectElement = actionsDiv.previousElementSibling;
            selectElement.value = selectElement.getAttribute('data-original');
            actionsDiv.classList.add('hidden');
            selectElement.classList.remove('border-purple-500', 'bg-purple-50');
        }
    </script>
<?php
}

/**
 * ฟังก์ชันบันทึกการแก้ไข Role
 */
function submitUpdateRole($conn, $redirect_url = null)
{
    // ... (ส่วนตั้งค่า URL และ Helper เหมือนเดิม) ...
    $default_url = "index.php?page=dashboard&tab=users";
    $final_redirect = !empty($redirect_url) ? $redirect_url : $default_url;
    $sep = (strpos($final_redirect, '?') !== false) ? '&' : '?';

    // 1. เช็คสิทธิ์ (เหมือนเดิม)
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'high-admin') {
        header("Location: " . $final_redirect . $sep . "status=error&msg=access_denied");
        exit();
    }

    $target_upid = mysqli_real_escape_string($conn, $_POST['target_user_id']); // รับ upid
    $new_role_id = intval($_POST['new_role_id']); // ✅ รับเป็น ID (ตัวเลข) มาเลย
    $actor_id    = $_SESSION['user_id'];

    // 1. ตรวจสอบว่า Role ID ที่ส่งมา มีอยู่จริงไหม (กันคนมั่วตัวเลข)
    $check_role_sql = "SELECT description FROM roles WHERE id = $new_role_id";
    $result_role = mysqli_query($conn, $check_role_sql);

    if (mysqli_num_rows($result_role) == 0) {
        header("Location: ... msg=invalid_role_id");
        exit();
    }
    
    // ดึงชื่อมาเก็บ Log เฉยๆ
    $role_row = mysqli_fetch_assoc($result_role);
    $role_desc = $role_row['description'];

    // 2. อัปเดตลงตาราง users ได้เลย! (ไม่ต้องค้นหาแล้ว)
    // ใช้คอลัมน์ role_id (หรือชื่อคอลัมน์ที่คุณตั้งไว้เก็บ id)
    $sql_update = "UPDATE users SET role_id = $new_role_id WHERE upid = '$target_upid'";

    if (mysqli_query($conn, $sql_update)) {
        // Log
        logActivity($conn, $actor_id, $target_upid, 'update_role', "เปลี่ยนสิทธิ์เป็น $role_desc (ID: $new_role_id)");
        
        header("Location: ... msg=role_updated");
        exit();
    
    } else {
        echo "Error: " . mysqli_error($conn);
        exit();
    }
}
?>