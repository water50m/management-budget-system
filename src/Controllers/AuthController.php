<?php
// src/Controllers/AuthController.php

// เรียกไฟล์เชื่อมต่อฐานข้อมูล (ปรับ path ตามจริงของคุณ)
require_once __DIR__ . '/../../includes/db.php';

class AuthController
{

    public function LDAP_login_4()
    {
        global $conn;

        // Require privacy acceptance first
        if (empty($_SESSION['privacy_accepted'])) {
            header('Location: index.php?page=privacy');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($_POST['username']) && !empty($_POST['password'])) {

                $user = mysqli_real_escape_string($conn, stripslashes($_POST["username"]));
                $psw = mysqli_real_escape_string($conn, stripslashes($_POST["password"]));

                $server = 'ldaps://ldaps.nu.local:636';
                $local = "@nu.local";

                $ad = ldap_connect($server);
                ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ad, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

                if (!$ad) {
                    header("Location: index.php?page=login&status=error&msg=cant_server");
                    exit();
                }

                // พยายาม Bind
                $b = @ldap_bind($ad, $user . $local, $psw);

                // ดึง Error เชิงลึก
                $extended_error = "";
                if (!$b) {
                    @ldap_get_option($ad, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                    header("Location: index.php?page=login&status=error&msg=unknow_error");
                exit();
                }

                // เรียกใช้ฟังก์ชันแสดงผล (ส่งค่าที่จำเป็นไป)
                $this->handle_login_success($conn, $user);

            } else {
                header("Location: index.php?page=login&status=error&msg=empty_fields");
                exit();
            }
        }
        require_once __DIR__ . '/../../views/auth/login4.php';
    }

    public function LDAP_login_4_test()
    {
        global $conn;

        // Require privacy acceptance first
        if (empty($_SESSION['privacy_accepted'])) {
            header('Location: index.php?page=privacy');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $user = 'Jeerawanth';
            // เรียกใช้ฟังก์ชันแสดงผล (ส่งค่าที่จำเป็นไป)
            $this->handle_login_success($conn, 'Jeerawanth');
        }
        require_once __DIR__ . '/../../views/auth/login4.php';
    }

    public function privacy_notice()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['privacy_accepted'] = true;
            $_SESSION['privacy_timestamp'] = time();
            header('Location: index.php?page=login');
            exit();
        }
        require_once __DIR__ . '/../../views/auth/privacy_notice.php';
    }


    private function handle_login_success($conn, $user)
    {
        // 1. ดึงข้อมูลจากฐานข้อมูล
        $sql = "SELECT p.user_id, u.username, u.role_id, r.role_name, p.prefix, p.first_name, p.last_name 
            FROM users u
            LEFT JOIN user_profiles p ON u.upid = p.user_id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.username = '$user'";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            // 2. ตั้งค่า Session
            $user_id = $row['user_id']; // แก้จาก $row['id'] เป็น user_id ตาม SQL Select
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $row['role_name'];
            $_SESSION['fullname'] = $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];

            // Logic พิเศษสำหรับค่า seer
            $_SESSION['seer'] = ($row['role_id'] == 7) ? 7 : ($row['role_id'] - 1);

            // 3. จัดการ Remember Me
            if (isset($_POST['remember'])) {
                $this->rememberAuth($conn, $user_id);
            }

            // 4. Redirect ตาม Role
            if ($row['role_id'] != 7) {
                header("Location: index.php?page=dashboard&tab=summary");
            } else {
                header("Location: index.php?page=profile&id=$user_id");
            }
        } else {
            // กรณี Bind LDAP ผ่าน แต่ไม่มีชื่อ User นี้ในฐานข้อมูลระบบ
            header("Location: index.php?page=login&status=error&msg=user_not_found_in_db");
        }
        exit();
    }

    private function render_debug_view($is_success, $ad, $user, $local, $extended_error)
    {
        // เตรียมข้อมูลสำหรับการแสดงผล
        $statusColor = $is_success ? '#dcfce7' : '#fee2e2';
        $borderColor = $is_success ? '#22c55e' : '#ef4444';
        $textColor = $is_success ? '#166534' : '#991b1b';
        $statusTitle = $is_success ? '✅ LOGIN SUCCESS' : '❌ LOGIN FAILED';

        $testRoles = [
            'ad_anatomy' => ['name' => 'กายวิภาคศาสตร์', 'color' => '#8b5cf6'],
            'ad_biochemistr' => ['name' => 'ชีวเคมี', 'color' => '#ec4899'],
            'ad_mic_par' => ['name' => 'จุลชีววิทยาฯ', 'color' => '#10b981'],
            'ad_physiology' => ['name' => 'สรีรวิทยา', 'color' => '#f59e0b'],
            'ad_office' => ['name' => 'สำนักงานเลขานุการ', 'color' => '#3b82f6'],
            'high_admin' => ['name' => 'High Admin (Default)', 'color' => '#ef4444'],
        ];
        ?>
        <div style="font-family: 'Sarabun', sans-serif; max-width: 800px; margin: 30px auto; border: 2px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
            <div style="background-color: <?php echo $statusColor; ?>; padding: 20px; border-bottom: 2px solid <?php echo $borderColor; ?>; text-align: center;">
                <h2 style="margin: 0; color: <?php echo $textColor; ?>; font-weight: 800;"><?php echo $statusTitle; ?></h2>
                <p style="margin: 5px 0 0; color: <?php echo $textColor; ?>;">
                    <?php echo $is_success ? 'ยืนยันตัวตนถูกต้อง ระบบพร้อมทำงาน' : 'กรุณาตรวจสอบ Username, Password หรือการตั้งค่า Server'; ?>
                </p>
            </div>

            <div style="padding: 25px; background-color: #ffffff;">
                <h3 style="border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; margin-top: 0;">🔍 ข้อมูลตัวแปร (Debug Info)</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; font-weight: bold; width: 30%;">User ที่ส่งไป:</td>
                        <td style="padding: 10px; font-family: monospace; color: #2563eb;"><?php echo htmlspecialchars($user . $local); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; font-weight: bold;">Bind Result ($b):</td>
                        <td style="padding: 10px;">
                            <?php echo $is_success
                                ? "<span style='background:#22c55e; color:white; padding:2px 8px; border-radius:4px;'>TRUE</span>"
                                : "<span style='background:#ef4444; color:white; padding:2px 8px; border-radius:4px;'>FALSE</span>"; ?>
                        </td>
                    </tr>
                </table>

                <?php if (!$is_success): ?>
                    <div style="background-color: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #991b1b;">⚠️ รายละเอียดข้อผิดพลาด</h4>
                        <p><strong>LDAP Error Msg:</strong> <span style="color: red;"><?php echo ldap_error($ad); ?></span></p>
                        <?php if (!empty($extended_error)): ?>
                            <p><strong>Diagnostic:</strong> <code style="background: #eee; padding: 2px 5px;"><?php echo $extended_error; ?></code></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="background-color: #f9fafb; padding: 20px; text-align: center;">
                <h4 style="color: #4b5563;">🧪 ทดสอบระบบด้วยสิทธิ์ต่างๆ</h4>
                <form action="index.php?page=fast-login" method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                        <?php foreach ($testRoles as $key => $info): ?>
                            <button type="submit" name="test-role-test" value="<?php echo $key; ?>"
                                style="border: 1px solid <?php echo $info['color']; ?>; color: <?php echo $info['color']; ?>; padding: 8px; border-radius: 6px; background: white; cursor: pointer; font-weight: bold;">
                                <?php echo $key ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>
                <div style="margin-top: 15px;"><a href="javascript:history.back()">กลับไปลองใหม่</a></div>
            </div>
        </div>
<?php
    }

    public function fast_login()
    {
        global $conn;

        $_SESSION['user_id'] = '4';
        $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';

        $role = $_GET['mock'] ?? '';

        if ($role == 'ad_anatomy') {
            $_SESSION['role'] = 'admin-anatomy';
            $_SESSION['seer'] = 1;
        } else if ($role == 'ad_biochemistr') {
            $_SESSION['role'] = 'admin-biochemistry';
            $_SESSION['seer'] = 2;
        } else if ($role == 'ad_mic_par') {
            $_SESSION['role'] = 'admin-mic-par';
            $_SESSION['seer'] = 3;
        } else if ($role == 'ad_physiology') {
            $_SESSION['role'] = 'admin-physiology ';
            $_SESSION['seer'] = 4;
        } else if ($role == 'ad_office') {
            $_SESSION['role'] = 'admin-office';
            $_SESSION['seer'] = 6;
        } else if ($role == 'user') {
            $_SESSION['user_id'] = '4';
            $_SESSION['role'] = 'user';
            $_SESSION['seer'] = 7;
        } else if ($role == 'admin') {

            $_SESSION['role'] = 'high-admin';
            $_SESSION['seer'] = 0;
        }
        // header("Location: index.php?page=dashboard&tab=users");
        echo $_SESSION['user_id'];
        echo $_SESSION['fullname'];
        echo $_SESSION['role'];
        echo $_SESSION['seer'];
        $this->rememberAuth($conn, $_SESSION['user_id']);
        header("Location: index.php?page=dashboard&tab=summary");
        exit;
    }
    public function new_login()
    {
        require_once __DIR__ . '/../../views/auth/new_login.php';
    }
    // ฟังก์ชัน Logout
    public function logout()
    {
        session_start();
        session_destroy();
        header("Location: index.php?page=login");
        exit();
    }

    private function rememberAuth($conn, $user_id)
    {
        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32)); // ตัวนี้คือ Password ลับสำหรับ Cookie
        $token_cookie = $selector . ':' . $validator;

        // Hash ตัว Validator ก่อนเก็บลง DB (เหมือนเก็บ Password)
        $hashed_validator = hash('sha256', $validator);
        $expiry = date('Y-m-d H:i:s', time() + (86400 * 7)); // 7 วัน

        // บันทึกลง DB
        $stmt = $conn->prepare("UPDATE users SET remember_selector = ?, remember_validator = ?, remember_expiry = ? WHERE id = ?");
        $stmt->bind_param("sssi", $selector, $hashed_validator, $expiry, $user_id);
        $stmt->execute();

        // ส่ง Cookie (HttpOnly = True, Secure = True)
        setcookie('remember_me', $token_cookie, time() + (86400 * 7), "/", "", true, true);
    }

    private function deleteRememberedAuth($conn)
    {
        // ลบ Cookie
        setcookie('remember_me', '', time() - 3600, "/", "", true, true);

        // ✅ ลบ Token ใน DB ทิ้งด้วย เพื่อความปลอดภัย
        if (isset($_SESSION['user_id'])) {
            $clear_stmt = $conn->prepare("UPDATE users SET remember_selector = NULL, remember_validator = NULL, remember_expiry = NULL WHERE id = ?");
            $clear_stmt->bind_param("i", $_SESSION['user_id']);
            $clear_stmt->execute();
        }

        session_destroy(); // ล้าง Session เดิม
        header("Location: index.php?page=login");
        exit;
    }

    private function checkRememberedAuth($conn)
    {

        // แยก selector กับ validator ออกจากกัน
        list($selector, $validator) = explode(':', $_COOKIE['remember_me']);

        // Query หา selector ใน DB

        $stmt = $conn->prepare("SELECT * FROM users WHERE remember_selector = ? AND remember_expiry > NOW()");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_row = $result->fetch_assoc();
        if ($token_row) {
            // ตรวจสอบ validator ว่าตรงกับ Hash ใน DB ไหม
            if (hash_equals($token_row['remember_validator'], hash('sha256', $validator))) {
                // ✅ Token ถูกต้อง! ดึงข้อมูล User มาแสดง
                $u_stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
                $u_stmt->bind_param("i", $token_row['upid']);
                $u_stmt->execute();
                $u_res = $u_stmt->get_result();
                $remembered_user = $u_res->fetch_assoc();
                return $remembered_user;
                // (Optional) ตรงนี้ถ้าจะให้ Login เลยก็ได้ แต่โจทย์บอกให้แสดงปุ่ม Login
            }
        }
    }

    private function autometicLogin($conn, $user_id)
    {
        if ($this->checkRememberedAuth($conn)) {

            $stmt = $conn->prepare("SELECT p.user_id, u.role_id, r.role_name, p.prefix, p.first_name, p.last_name 
                                FROM users u
                                LEFT JOIN user_profiles p ON u.upid = p.user_id
                                LEFT JOIN roles r ON u.role_id = r.id
                                WHERE u.upid = ? AND u.remember_expiry > NOW()");

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $token_row = $result->fetch_assoc();

            $user_id = $token_row['user_id'];
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $token_row['role_name'];
            $_SESSION['fullname'] = $token_row['prefix'] . ' ' . $token_row['first_name'] . ' ' . $token_row['last_name'];
            $_SESSION['seer'] = $token_row['role_id'] == 7 ? 7 : $token_row['role_id']  - 1;
            if ($token_row['role_id'] != 7) {
                header("Location: index.php?page=dashboard&tab=summary");
            } else {
                header("Location: index.php?page=profile&id=$user_id");
            }
            exit;
        }
        $this->deleteRememberedAuth($conn);
        header("Location: index.php?page=login");
        exit;
    }
}
