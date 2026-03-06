<?php
// เริ่มต้นการใช้งาน Session (ต้องมีคำสั่งนี้ก่อนเรียกใช้ $_SESSION เสมอ)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามี Session 'role' หรือไม่ และค่าของ role เป็น 'high-admin' หรือเปล่า
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'high-admin') {
    // ถ้าไม่มีสิทธิ์ ให้ Redirect กลับไปที่หน้า login.php
    header("Location: index.php?page=login");
    exit(); 
}
include_once __DIR__ . '/../../includes/db.php';

$dbNameDisplay = isset($dbname) ? $dbname : (isset($dbName) ? $dbName : 'Your Database');

// ---------------------------------------------------------
// 2. ข้อมูล Configuration ที่ต้องการดำเนินการ
// ---------------------------------------------------------
$tableName = 'budget_expenses';

// คอลัมน์ที่ 1 (พรีวิว / รูปภาพ)
$col1 = 'receipt_image_path';
$col1Type = 'VARCHAR(255) NULL DEFAULT NULL';
$col1Comment = 'เก็บ path รูปเอกสาร';

// คอลัมน์ที่ 2 (ไฟล์ต้นฉบับ)
$col2 = 'receipt_original_path';
$col2Type = 'VARCHAR(255) NULL DEFAULT NULL';
$col2Comment = 'เก็บ path ไฟล์ดิบ (Word/Excel)';

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

function addImageColumn($conn, $tableName, $columnName, $dataType, $columnComment, $afterColumn = '') {
    $safeTable = mysqli_real_escape_string($conn, $tableName);
    $safeColumn = mysqli_real_escape_string($conn, $columnName);
    $sql = "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $dataType COMMENT '$columnComment'";
    
    if ($afterColumn !== '') {
        $safeAfter = mysqli_real_escape_string($conn, $afterColumn);
        $sql .= " AFTER `$safeAfter`";
    }
    
    return mysqli_query($conn, $sql) ? true : mysqli_error($conn);
}

// ---------------------------------------------------------
// 4. จัดการ Logic เมื่อมีการกดปุ่ม (แยก 2 ปุ่ม)
// ---------------------------------------------------------
$alertMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // กรณีกดปุ่มเพิ่มคอลัมน์ที่ 1
    if (isset($_POST['action_add_col1'])) {
        if (!checkColumnExists($conn, $tableName, $col1)) {
            $res = addImageColumn($conn, $tableName, $col1, $col1Type, $col1Comment);
            if ($res === true) {
                $alertMessage = "<div class='alert alert-success'>✅ <strong>สำเร็จ:</strong> เพิ่มคอลัมน์ <code>{$col1}</code> เรียบร้อยแล้ว</div>";
            } else {
                $alertMessage = "<div class='alert alert-danger'>❌ <strong>ข้อผิดพลาด:</strong> ไม่สามารถเพิ่ม {$col1} ได้: {$res}</div>";
            }
        }
    }

    // กรณีกดปุ่มเพิ่มคอลัมน์ที่ 2
    if (isset($_POST['action_add_col2'])) {
        if (!checkColumnExists($conn, $tableName, $col2)) {
            // เช็คว่ามี col1 ไหม ถ้ามีให้วางต่อจาก col1 แต่ถ้าไม่มีก็ไม่ต้องใส่ AFTER
            $after = checkColumnExists($conn, $tableName, $col1) ? $col1 : '';
            
            $res = addImageColumn($conn, $tableName, $col2, $col2Type, $col2Comment, $after);
            if ($res === true) {
                $alertMessage = "<div class='alert alert-success'>✅ <strong>สำเร็จ:</strong> เพิ่มคอลัมน์ <code>{$col2}</code> เรียบร้อยแล้ว</div>";
            } else {
                $alertMessage = "<div class='alert alert-danger'>❌ <strong>ข้อผิดพลาด:</strong> ไม่สามารถเพิ่ม {$col2} ได้: {$res}</div>";
            }
        }
    }
}

// เช็คสถานะปัจจุบันของทั้ง 2 คอลัมน์ เพื่อแสดงผลใน UI
$isCol1Exist = checkColumnExists($conn, $tableName, $col1);
$isCol2Exist = checkColumnExists($conn, $tableName, $col2);
$isFullyUpdated = ($isCol1Exist && $isCol2Exist);
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
            max-width: 800px; 
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
            vertical-align: middle;
        }
        .info-table th { 
            background-color: #f8f9fa; 
            width: 20%; 
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
        
        .btn { 
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        .btn-primary { background-color: #0d6efd; color: white; }
        .btn-primary:hover { background-color: #0b5ed7; }
        
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
            font-size: 14px;
        }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .mt-2 { margin-top: 10px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>⚙️ System Database Configuration</h2>
        <p class="description">หน้าต่างนี้สำหรับตรวจสอบและอัปเดตโครงสร้างฐานข้อมูล เพื่อรองรับระบบการอัปโหลดเอกสารใบเสร็จ (แยกเพิ่มทีละคอลัมน์ได้)</p>
        
        <?= $alertMessage ?>

        <table class="info-table">
            <tbody>
                <tr>
                    <th>ตารางเป้าหมาย</th>
                    <td colspan="2"><code><?= htmlspecialchars($tableName) ?></code> (Database: <code><?= htmlspecialchars($dbNameDisplay) ?></code>)</td>
                </tr>
                
                <tr>
                    <th>โครงสร้างที่ 1</th>
                    <td>
                        <b>ชื่อคอลัมน์:</b> <code><?= htmlspecialchars($col1) ?></code><br>
                        <b>คำอธิบาย:</b> <span style="color:#666;"><?= htmlspecialchars($col1Comment) ?></span>
                    </td>
                    <td class="text-center" style="width: 25%;">
                        <?php if ($isCol1Exist): ?>
                            <span class="status-badge status-exists">✅ พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="status-badge status-missing">❌ ยังไม่มีคอลัมน์</span>
                            <form method="POST" onsubmit="return confirm('ยืนยันการเพิ่มคอลัมน์ <?= $col1 ?> ?');" class="mt-2" style="margin-bottom:0;">
                                <input type="hidden" name="action_add_col1" value="1">
                                <button type="submit" class="btn btn-primary">➕ เพิ่มคอลัมน์นี้</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th>โครงสร้างที่ 2</th>
                    <td>
                        <b>ชื่อคอลัมน์:</b> <code><?= htmlspecialchars($col2) ?></code><br>
                        <b>คำอธิบาย:</b> <span style="color:#666;"><?= htmlspecialchars($col2Comment) ?></span>
                    </td>
                    <td class="text-center" style="width: 25%;">
                        <?php if ($isCol2Exist): ?>
                            <span class="status-badge status-exists">✅ พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="status-badge status-missing">❌ ยังไม่มีคอลัมน์</span>
                            <form method="POST" onsubmit="return confirm('ยืนยันการเพิ่มคอลัมน์ <?= $col2 ?> ?');" class="mt-2" style="margin-bottom:0;">
                                <input type="hidden" name="action_add_col2" value="1">
                                <button type="submit" class="btn btn-primary">➕ เพิ่มคอลัมน์นี้</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ($isFullyUpdated): ?>
            <div style="text-align: center; margin-top: 30px; padding: 15px; background: #e8f5e9; color: #2e7d32; border-radius: 6px; font-weight: bold;">
                🎉 โครงสร้างฐานข้อมูลอัปเดตครบถ้วนพร้อมใช้งานแล้ว!
            </div>
        <?php endif; ?>
    </div>

</body>
</html>