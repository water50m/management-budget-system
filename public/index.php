<?php
ob_start();
session_start();

// เรียกใช้ Controller ที่จำเป็น
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';
require_once __DIR__ . '/../src/Controllers/ProfileController.php';
// ... require controller อื่นๆ ...

$page = $_GET['page'] ?? 'dashboard'; 

switch ($page) {
    // --- ส่วนจัดการ Login/Logout ---
    case 'login':
        $controller = new AuthController();
        $controller->LDAP_login();
        break;

    case 'fast-login':
        $controller = new AuthController();
        $controller->fast_login();
        break;

    case 'ldap-test':
        $controller = new AuthController();
        $controller->LDAP_login_test_2();
        break;

    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    // --- ส่วน Dashboard ---
    case 'dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'profile':  
        $controller = new ProfileController();
        $controller->index();
        break;
    
    case 'add-profile':
        $controller = new ProfileController();
        $controller->addProfile($conn);
        break;
    case 'show-pdf':
        $controller = new DashboardController();
        $controller->showPDF();
        break;

    // --- ส่วน Register (ถ้าแยก Controller ก็ใส่ตรงนี้) ---
    // case 'register': ...

    default:
        echo "404 Not Found";
        break;
}
// ล้าง Buffer และส่ง output ทั้งหมดออกไป
ob_end_flush();
?>