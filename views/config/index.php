<?php
// เริ่มต้นการใช้งาน Session (ต้องมีคำสั่งนี้ก่อนเรียกใช้ $_SESSION เสมอ)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามี Session 'role' หรือไม่ และค่าของ role เป็น 'high-admin' หรือเปล่า
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'high-admin') {
    // ถ้าไม่มีสิทธิ์ ให้ Redirect กลับไปที่หน้า login.php
    header("Location: index.php?page=login");
    exit(); // หยุดการทำงานของสคริปต์ทันที ป้องกันไม่ให้โค้ดด้านล่างทำงานต่อ
}
include_once __DIR__ . '/../../includes/db.php';

// ---------------------------------------------------------
// 2. ข้อมูล Configuration ที่ต้องการดำเนินการ
// ---------------------------------------------------------
$tableName = 'budget_expenses';
$columnName = 'receipt_image_path';
$dataType = 'VARCHAR(255) NULL DEFAULT NULL';
$columnComment = 'เก็บ path รูปเอกสาร';

// ---------------------------------------------------------
// 3. ฟังก์ชันที่เกี่ยวข้อง
// ---------------------------------------------------------
function checkColumnExists($conn, $tableName, $columnName) {
    $safeTable = mysqli_real_escape_string($conn, $tableName);
    $safeColumn = mysqli_real_escape_string($conn, $columnName);
    $sql = "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

function addImageColumn($conn, $tableName, $columnName, $dataType, $columnComment) {
    $safeTable = mysqli_real_escape_string($conn, $tableName);
    $safeColumn = mysqli_real_escape_string($conn, $columnName);
    $sql = "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $dataType COMMENT '$columnComment'";
    return mysqli_query($conn, $sql) ? true : mysqli_error($conn);
}

// ---------------------------------------------------------
// 4. จัดการ Logic เมื่อมีการกดปุ่ม
// ---------------------------------------------------------
$alertMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_column'])) {
    $result = addImageColumn($conn, $tableName, $columnName, $dataType, $columnComment);
    if ($result === true) {
        $alertMessage = "<div class='alert alert-success'><strong>Success:</strong> ระบบได้ทำการเพิ่มคอลัมน์ <code>{$columnName}</code> ลงในตาราง <code>{$tableName}</code> เรียบร้อยแล้ว</div>";
    } else {
        $alertMessage = "<div class='alert alert-danger'><strong>Error:</strong> ไม่สามารถอัปเดตฐานข้อมูลได้: {$result}</div>";
    }
}

// เช็คสถานะปัจจุบัน
$isColumnExist = checkColumnExists($conn, $tableName, $columnName);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Configuration</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f7f6; 
            color: #333; 
            margin: 0; 
            padding: 40px 20px; 
            display: flex;
            justify-content: center;
        }
        .container { 
            background: #ffffff; 
            width: 100%; 
            max-width: 650px; 
            padding: 30px; 
            border-radius: 6px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid #e3e6f0;
        }
        h2 { 
            color: #2c3e50; 
            border-bottom: 2px solid #f0f2f5; 
            padding-bottom: 15px; 
            margin-top: 0; 
            font-size: 20px;
        }
        .description {
            color: #555;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 14px;
        }
        .info-table th, .info-table td { 
            padding: 12px 15px; 
            border: 1px solid #e3e6f0; 
            text-align: left; 
        }
        .info-table th { 
            background-color: #f8f9fa; 
            width: 35%; 
            color: #495057; 
            font-weight: 600;
        }
        .info-table code {
            background-color: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            color: #d63384;
            font-family: Consolas, monospace;
        }
        .status-badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: bold; 
        }
        .status-exists { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .status-missing { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .action-area {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f2f5;
            text-align: right;
        }
        .btn { 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        .btn-primary { 
            background-color: #0d6efd; 
            color: white; 
        }
        .btn-primary:hover { 
            background-color: #0b5ed7; 
        }
        .btn-disabled { 
            background-color: #e9ecef; 
            color: #6c757d; 
            cursor: not-allowed; 
            border: 1px solid #ced4da; 
        }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
            font-size: 14px;
        }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

    <div class="container">
        <h2>System Database Configuration</h2>
        <p class="description">หน้าต่างนี้สำหรับตรวจสอบและอัปเดตโครงสร้างฐานข้อมูล เพื่อรองรับระบบการอัปโหลดเอกสารใบเสร็จ (Receipt Upload Feature)</p>
        
        <?= $alertMessage ?>

        <table class="info-table">
            <tbody>
                <tr>
                    <th>ฐานข้อมูลเป้าหมาย (Database)</th>
                    <td><code><?= htmlspecialchars($dbname) ?></code></td>
                </tr>
                <tr>
                    <th>ตารางเป้าหมาย (Table)</th>
                    <td><code><?= htmlspecialchars($tableName) ?></code></td>
                </tr>
                <tr>
                    <th>คอลัมน์ที่จะเพิ่ม (New Column)</th>
                    <td><code><?= htmlspecialchars($columnName) ?></code></td>
                </tr>
                <tr>
                    <th>ชนิดข้อมูล (Data Type)</th>
                    <td><?= htmlspecialchars($dataType) ?></td>
                </tr>
                <tr>
                    <th>สถานะปัจจุบัน (Status)</th>
                    <td>
                        <?php if ($isColumnExist): ?>
                            <span class="status-badge status-exists">พร้อมใช้งาน (Exists)</span>
                        <?php else: ?>
                            <span class="status-badge status-missing">ยังไม่มีคอลัมน์ (Not Found)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="action-area">
            <?php if ($isColumnExist): ?>
                <button class="btn btn-disabled" disabled>
                    โครงสร้างฐานข้อมูลอัปเดตแล้ว
                </button>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('กรุณายืนยัน: คุณต้องการรันคำสั่ง ALTER TABLE เพื่อเพิ่มคอลัมน์ใช่หรือไม่?');" style="margin: 0;">
                    <input type="hidden" name="action_add_column" value="1">
                    <button type="submit" class="btn btn-primary">
                        ดำเนินการอัปเดตฐานข้อมูล (Apply Changes)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>