<?php
// src/Controllers/AuthController.php

// เรียกไฟล์เชื่อมต่อฐานข้อมูล (ปรับ path ตามจริงของคุณ)
require_once __DIR__ . '/../../includes/db.php';

class AuthController
{

    // ฟังก์ชันจัดการ Login
    public function login()
    {
        global $conn; // เรียกตัวแปร $conn จาก db.php มาใช้
        $error = null;

        // 2. ถ้ามีการกด Submit (POST)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_SESSION['user_id'] = '1';
            $_SESSION['role'] = 'high-admin';
            $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';
            $_SESSION['seer'] = 0;
            header("Location: index.php?page=dashboard");
            exit();
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $password = $_POST['password'];

            // Query หา User (Join เพื่อเอาข้อมูล Profile มาด้วยเลย)
            $sql = "SELECT u.id, u.username, u.password, u.role, p.first_name, p.last_name 
                    FROM users u 
                    LEFT JOIN user_profiles p ON u.id = p.user_id 
                    WHERE u.username = '$username'
                    AND deleted_at IS NULL";

            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($result);

            // ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password'])) {
                // Login สำเร็จ: เก็บ Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['seer'] = 0;
                // ส่งไปหน้า Dashboard
                header("Location: index.php?page=dashboard");
                exit();
            } else {
                $error = "Username หรือรหัสผ่านไม่ถูกต้อง";
            }
        }

        // 3. เรียกหน้า View มาแสดง (ส่งตัวแปร $error ไปด้วย)
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function LDAP_login()
    {
        global $conn;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($_POST['username']) && !empty($_POST['password'])) {

                $user = $_POST["username"];
                $psw = $_POST["password"];

                $user = stripslashes($user);
                $psw = stripslashes($psw);
                $user = mysqli_real_escape_string($conn, $user);
                $psw = mysqli_real_escape_string($conn, $psw);

                include_once __DIR__ . '/../../inc/func.php';
                $server = 'ldaps://ldaps.nu.local:636';
                $local = "@nu.local";
                $ad = ldap_connect($server);
                ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ad, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                if (!$ad) {
                    header("Location: index.php?page=login&status=error&msg=cant_server");
                    exit();
                } else {
                    $b = @ldap_bind($ad, $user . $local, $psw);

                    @ldap_get_option($ad, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                    if (!$b) {
                        header("Location: index.php?page=login&status=error&msg=invalid_credentials");
                        exit();
                    } else {


                        // 2. เขียน SQL (สังเกตที่ '$username' ต้องมีขีดเดียวครอบ)
                        $sql = "SELECT p.user_id, u.username, u.role_id, r.role_name, p.prefix, p.first_name, p.last_name 
                                FROM users u
                                LEFT JOIN user_profiles p ON u.id = p.user_id
                                LEFT JOIN roles r ON u.role_id = r.id
                                WHERE u.username = '$user'";

                        $result = mysqli_query($conn, $sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            // ดึงข้อมูลออกมาใส่ตัวแปร $row
                            $row = mysqli_fetch_assoc($result);

                            // เรียกใช้ได้เลย
                            $user_id = $row['id'];
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['role'] = $row['role_name'];
                            $_SESSION['fullname'] = $row['prefix'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
                            $_SESSION['seer'] = $row['role_id'] == 7 ? 7 : $row['role_id']  - 1;
                            if (isset($_POST['remember'])) {
                                $this->rememberAuth($conn, $user_id);
                            }
                            if ($row['role_id'] != 7) {
                                header("Location: index.php?page=dashboard&tab=summary");
                            } else {
                                header("Location: index.php?page=profile&id=$user_id");
                            }
                        } else {
                            header("Location: index.php?page=login&status=error&msg=unknow_username");
                        }
                        exit;
                    }
                }
            } else if (!empty($_POST['login_via_remember'])){
                $user_id = intval($_POST['login_via_remember']);
                if(!$this->autometicLogin($conn, $user_id)){
                    header("Location: index.php?page=login&status=error&msg=empty_fields");
                    exit();
                }
                $this->deleteRememberedAuth($conn);
            }
            else if (empty($_POST['username']) || empty($_POST['password'])) {
                header("Location: index.php?page=login&status=error&msg=empty_fields");
                exit();
            }
        }

        $remembered_user = null;
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
            $remembered_user = $this->checkRememberedAuth($conn);
            
        }

        // ส่วน Logout / เปลี่ยนบัญชี
        if (isset($_GET['action']) && $_GET['action'] == 'switch_account') {
            $this->deleteRememberedAuth($conn);
        }
        require_once __DIR__ . '/../../views/auth/login.php';
    }



    public function LDAP_login_test()
    {
        global $conn;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($_POST['username']) && !empty($_POST['password'])) {

                $user = $_POST["username"];
                $psw = $_POST["password"];
                $user = stripslashes($user);
                $psw = stripslashes($psw);
                $user = mysqli_real_escape_string($conn, $user);
                $psw = mysqli_real_escape_string($conn, $psw);

                include_once __DIR__ . '/../../inc/func.php';
                // loadEnv(__DIR__ . '/../../.env');
                // if (!getenv('LDAP_SERVER')) {
                //     echo 'Not found secret key (2)';
                //     exit;
                // }
                $server = 'ldaps://ldaps.nu.local:636';
                $local = "@nu.local";
                $ad = ldap_connect($server);
                ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ad, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                if (!$ad) {
                    header("Location: index.php?page=login&status=error&msg=cant_server");
                    exit();
                } else {
                    $b = @ldap_bind($ad, $user . $local, $psw);

                    // -----------------------------------------------------------
                    // ส่วนแสดงผล Debug แบบละเอียด (Copy ไปวางต่อท้ายได้เลย)
                    // -----------------------------------------------------------

                    // 1. ดึงข้อมูล Error เชิงลึก (Diagnostic Message)
                    // ตัวนี้สำคัญมาก! มันจะบอกได้ละเอียดกว่า "Invalid credentials"
                    // เช่น บอกว่า data 52e (รหัสผิด), data 532 (รหัสหมดอายุ), data 773 (ต้องเปลี่ยนรหัส)
                    $extended_error = "";
                    if (!$b) {
                        @ldap_get_option($ad, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                    }

                    // กำหนดสีและข้อความสถานะ
                    $statusColor = $b ? '#dcfce7' : '#fee2e2'; // เขียวอ่อน / แดงอ่อน
                    $borderColor = $b ? '#22c55e' : '#ef4444'; // เขียวเข้ม / แดงเข้ม
                    $textColor = $b ? '#166534' : '#991b1b'; // เขียวตัวหนังสือ / แดงตัวหนังสือ
                    $statusTitle = $b ? '✅ LOGIN SUCCESS (เชื่อมต่อสำเร็จ)' : '❌ LOGIN FAILED (เชื่อมต่อล้มเหลว)';
                    $_SESSION['user_id'] = '1';
                    $_SESSION['role'] = 'high-admin';
                    $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';
                    $_SESSION['seer'] = 0;


?>

                    <div style="font-family: 'Sarabun', sans-serif; max-width: 800px; margin: 30px auto; border: 2px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">

                        <div style="background-color: <?php echo $statusColor; ?>; padding: 20px; border-bottom: 2px solid <?php echo $borderColor; ?>; text-align: center;">
                            <h2 style="margin: 0; color: <?php echo $textColor; ?>; font-weight: 800;">
                                <?php echo $statusTitle; ?>
                            </h2>
                            <?php if ($b): ?>
                                <p style="margin: 5px 0 0; color: <?php echo $textColor; ?>;">
                                    ยืนยันตัวตนถูกต้อง ระบบพร้อมทำงาน
                                </p>
                            <?php else: ?>
                                <p style="margin: 5px 0 0; color: <?php echo $textColor; ?>;">
                                    ฆ กรุณาตรวจสอบ Username, Password หรือการตั้งค่า Server
                                </p>
                            <?php endif; ?>
                        </div>

                        <div style="padding: 25px; background-color: #ffffff;">

                            <h3 style="border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; margin-top: 0;">🔍 ข้อมูลตัวแปร (Debug Info)</h3>

                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold; width: 30%;">User ที่ส่งไป:</td>
                                    <td style="padding: 10px; font-family: monospace; color: #2563eb;">
                                        <?php echo htmlspecialchars($user . $local); ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold;">Connection Object ($ad):</td>
                                    <td style="padding: 10px;">
                                        <?php
                                        // เช็คว่าเป็น Object หรือ Resource หรือ false
                                        if (is_object($ad)) {
                                            echo "<span style='color:green; font-weight:bold;'>Object (Connected)</span>";
                                        } elseif (is_resource($ad)) {
                                            echo "<span style='color:green; font-weight:bold;'>Resource (Connected)</span>";
                                        } else {
                                            echo "<span style='color:red; font-weight:bold;'>FALSE (Not Connected)</span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold;">Bind Result ($b):</td>
                                    <td style="padding: 10px;">
                                        <?php
                                        if ($b) {
                                            echo "<span style='background:#22c55e; color:white; padding:2px 8px; border-radius:4px;'>TRUE</span>";
                                        } else {
                                            echo "<span style='background:#ef4444; color:white; padding:2px 8px; border-radius:4px;'>FALSE</span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>

                            <?php if (!$b): ?>
                                <div style="background-color: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px;">
                                    <h4 style="margin: 0 0 10px 0; color: #991b1b;">⚠️ รายละเอียดข้อผิดพลาด (Error Log)</h4>

                                    <p style="margin: 5px 0;"><strong>LDAP Error No:</strong> <?php echo ldap_errno($ad); ?></p>
                                    <p style="margin: 5px 0;"><strong>LDAP Error Msg:</strong> <span style="color: red;"><?php echo ldap_error($ad); ?></span></p>

                                    <?php if (!empty($extended_error)): ?>
                                        <hr style="border: 0; border-top: 1px dashed #fca5a5; margin: 10px 0;">
                                        <p style="margin: 5px 0;">
                                            <strong>Diagnostic Message (Server Reply):</strong><br>
                                            <code style="background: #eee; padding: 2px 5px; border-radius: 4px; color: #d946ef;"><?php echo $extended_error; ?></code>
                                        </p>
                                        <small style="color: #666;">*ลองเอา code ใน Diagnostic ไปค้น Google ดูครับ (เช่น data 52e)</small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <div style="margin-top: 25px; border-top: 2px dashed #f3f4f6; padding-top: 20px;">
                                <h4 style="margin: 0 0 15px 0; color: #4b5563; font-size: 0.9em; text-transform: uppercase; letter-spacing: 0.05em;">
                                    🧪 ทดสอบระบบด้วยสิทธิ์ต่างๆ (Test Roles - POST Method)
                                </h4>

                                <form action="index.php?page=fast-login" method="POST">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">

                                        <?php
                                        $testRoles = [
                                            'ad_anatomy' => ['name' => 'กายวิภาคศาสตร์', 'color' => '#8b5cf6'],
                                            'ad_biochemistr' => ['name' => 'ชีวเคมี', 'color' => '#ec4899'],
                                            'ad_mic_par' => ['name' => 'จุลชีววิทยาฯ', 'color' => '#10b981'],
                                            'ad_physiology' => ['name' => 'สรีรวิทยา', 'color' => '#f59e0b'],
                                            'ad_office' => ['name' => 'สำนักงานเลขานุการ', 'color' => '#3b82f6'],
                                            'high_admin' => ['name' => 'High Admin (Default)', 'color' => '#ef4444'],
                                        ];

                                        foreach ($testRoles as $key => $info):

                                        ?>
                                            <button type="submit" name="test-role-test" value="<?php echo $key; ?>"
                                                style="display: block; width: 100%; text-align: center; background-color: white; border: 1px solid <?php echo $info['color']; ?>; color: <?php echo $info['color']; ?>; padding: 8px 12px; border-radius: 6px; font-size: 0.85em; font-weight: bold; cursor: pointer; transition: all 0.2s;"
                                                onmouseover="this.style.backgroundColor='<?php echo $info['color']; ?>'; this.style.color='white';"
                                                onmouseout="this.style.backgroundColor='white'; this.style.color='<?php echo $info['color']; ?>';">
                                                <?php echo $key ?>
                                            </button>
                                        <?php endforeach; ?>

                                    </div>
                                </form>
                            </div>
                            <br>
                            <div style="margin-top: 10px;">
                                <a href="javascript:history.back()" style="color: #6b7280; text-decoration: none; font-size: 0.9em;">&larr; กลับไปลองใหม่</a>
                            </div>
                        </div>

                    </div>
<?php
                    die();
                    if (!$b) {
                        header("Location: index.php?page=login&status=error&msg=invalid_credentials");
                        exit();
                    } else {
                        $_SESSION['user_id'] = '1';
                        $_SESSION['role'] = 'high-admin';
                        $_SESSION['fullname'] = 'login' . ' ' . 'success';
                        $_SESSION['seer'] = 0;
                        header("Location: index.php?page=dashboard");
                    }
                }
            } else if (empty($_POST['username']) || empty($_POST['password'])) {
                header("Location: index.php?page=login&status=error&msg=empty_fields");
                exit();
            }
        }
        require_once __DIR__ . '/../../views/auth/login-test.php';
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
                                LEFT JOIN user_profiles p ON u.id = p.user_id
                                LEFT JOIN roles r ON u.role_id = r.id
                                WHERE u.id = ? AND u.remember_expiry > NOW()");
            
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
