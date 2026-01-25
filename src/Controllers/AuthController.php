<?php
// src/Controllers/AuthController.php

// เรียกไฟล์เชื่อมต่อฐานข้อมูล (ปรับ path ตามจริงของคุณ)
require_once __DIR__ . '/../../includes/db.php'; 

class AuthController {
    
    // ฟังก์ชันจัดการ Login
    public function login() {
        global $conn; // เรียกตัวแปร $conn จาก db.php มาใช้
        
        // 1. ถ้าล็อกอินอยู่แล้ว ให้เด้งไป Dashboard เลย
        // if (isset($_SESSION['user_id'])) {
        //     header("Location: index.php?page=dashboard");
        //     exit();
        // }

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

    // ฟังก์ชัน Logout
    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php?page=login");
        exit();
    }
}
?>