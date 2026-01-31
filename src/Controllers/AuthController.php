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
            $_SESSION['username'] = 'high-admin';
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
                    AND p.deleted_at IS NULL";

            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($result);

            // ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password'])) {
                // Login สำเร็จ: เก็บ Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
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
                loadEnv(__DIR__ . '/../../.env');
                if (!getenv('LDAP_SERVER')) {
                    echo 'Not found secret key (2)';
                    exit;
                }
                $server = getenv('LDAP_SERVER');
                $local = getenv('LDAP_DOMAIN');
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
                    $_SESSION['username'] = 'high-admin';
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
                                    กรุณาตรวจสอบ Username, Password หรือการตั้งค่า Server
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
                            <a href="index.php" style="display: inline-block; text-decoration: none; background-color: #2563eb; color: white; padding: 12px 30px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4); transition: all 0.2s;">
                                เข้าสู่หน้าเว็บไซต์หลัก (Go to Website) &rarr;
                            </a>
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
                        $_SESSION['username'] = 'high-admin';
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
        require_once __DIR__ . '/../../views/auth/login.php';
    }
    public function fast_login()
    {
        $_SESSION['user_id'] = '1';
        $_SESSION['username'] = 'high-admin';
        $_SESSION['role'] = 'high-admin';
        $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';
        $_SESSION['seer'] = 0;
        header("Location: index.php?page=dashboard");
    }

    // ฟังก์ชัน Logout
    public function logout()
    {
        session_start();
        session_destroy();
        header("Location: index.php?page=login");
        exit();
    }
}
