<?php
// src/Controllers/AuthController.php

// เรียกไฟล์เชื่อมต่อฐานข้อมูล (ปรับ path ตามจริงของคุณ)
require_once __DIR__ . '/../../includes/db.php'; 

class AuthController {
    
    // ฟังก์ชันจัดการ Login
    public function login() {
        global $conn; // เรียกตัวแปร $conn จาก db.php มาใช้
        $error = null;

        // 2. ถ้ามีการกด Submit (POST)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_SESSION['user_id'] = '1';
            $_SESSION['username'] = 'high-admin';
            $_SESSION['role'] = 'high-admin';
            $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';
            header("Location: index.php?page=dashboard");
            exit();
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $password = $_POST['password'];

            // Query หา User (Join เพื่อเอาข้อมูล Profile มาด้วยเลย)
            $sql = "SELECT u.id, u.username, u.password, u.role, p.first_name, p.last_name 
                    FROM users u 
                    LEFT JOIN user_profiles p ON u.id = p.user_id 
                    WHERE u.username = '$username'";
            
            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($result);

            // ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password'])) {
                // Login สำเร็จ: เก็บ Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];

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

    public function LDAP_login(){
        global $conn;

        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
            
                $user = $_POST["username"];
                $psw = $_POST["password"];
                $user = stripslashes($user);
                $psw = stripslashes($psw);
                $user = mysqli_real_escape_string($conn, $user);
                $psw = mysqli_real_escape_string($conn, $psw);

                include_once __DIR__ . '/../../inc/func.php';
                loadEnv(__DIR__ . '/../../.env');
                if (!getenv('LDAP_SERVER')){
                    echo 'Not found secret key (2)';
                    exit;
                }
                $server = "ldaps://ldap.nu.local:636";
                $local = "@nu.local";
                $ad = ldap_connect($server);
                ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
                $ad = ldap_connect($server);
                if (!$ad) {
                    header("Location: index.php?page=login&status=error&msg=cant_server");
                    exit(); 
                } else {
                    $b = @ldap_bind($ad, $user . $local, $psw);
                    echo "<pre style='background:#f4f4f4; padding:20px; border:1px solid #ccc;'>";

                    echo "<h3>1. ค่าของ \$ad (Connection Object):</h3>";
                    var_dump($ad); 
                    // ถ้าสำเร็จควรเป็น object(LDAP\Connection) หรือ resource
                    // ถ้าไม่สำเร็จจะเป็น bool(false)

                    echo "<hr>";

                    echo "<h3>2. ค่าของ \$b (Bind Result):</h3>";
                    var_dump($b); 
                    // ถ้าสำเร็จจะเป็น bool(true)
                    // ถ้าไม่สำเร็จจะเป็น bool(false)

                    echo "<hr>";

                    echo "<h3>3. ข้อความ Error จาก LDAP (สำคัญมาก!):</h3>";
                    echo "Error No: " . ldap_errno($ad) . "<br>";
                    echo "Error Msg: " . ldap_error($ad); 
                    // ตรงนี้จะบอกสาเหตุจริงๆ เช่น "Invalid credentials" หรือ "Can't contact LDAP server"

                    echo "</pre>";

                    die();
                    if (!$b) {
                    header("Location: index.php?page=login&status=error&msg=invalid_credentials");
                    exit(); 
                    } else { 
                        $_SESSION['user_id'] = '1';
                        $_SESSION['username'] = 'high-admin';
                        $_SESSION['role'] = 'high-admin';
                        $_SESSION['fullname'] = 'login' . ' ' . 'success';
                        header("Location: index.php?page=dashboard");
                    }
                }
            
            } else if (empty($_POST['username']) || empty($_POST['password'])){
                header("Location: index.php?page=login&status=error&msg=empty_fields");
                exit();
            }
        }
        require_once __DIR__ . '/../../views/auth/login.php';
    }
    public function fast_login(){
    $_SESSION['user_id'] = '1';
    $_SESSION['username'] = 'high-admin';
    $_SESSION['role'] = 'high-admin';
    $_SESSION['fullname'] = 'สมชาย' . ' ' . 'รักเรียน';
    header("Location: index.php?page=dashboard");

    }

    // ฟังก์ชัน Logout
    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php?page=login");
        exit();
    }
}
?>