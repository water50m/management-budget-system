<?php
// 1. เริ่มต้นการใช้งาน Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามี Session 'role' หรือไม่ และค่าของ role เป็น 'high-admin' หรือเปล่า
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'high-admin') {
    header("Location: index.php?page=login");
    exit(); 
}
include_once __DIR__ . '/../../includes/db.php';

$dbNameDisplay = isset($dbname) ? $dbname : (isset($dbName) ? $dbName : 'Your Database');

// ---------------------------------------------------------
// 2. ข้อมูล Configuration ที่ต้องการดำเนินการ
// ---------------------------------------------------------
$tableName = 'budget_expenses';

$col1 = 'receipt_image_path';
$col1Type = 'VARCHAR(255) NULL DEFAULT NULL';
$col1Comment = 'เก็บ path รูปเอกสาร';

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

function getBudgetUsageRepairStats($conn) {
    $stats = [
        'misallocated_logs' => 0,
        'misallocated_amount' => 0,
        'affected_users' => 0,
    ];

    $sqlMisallocated = "SELECT COUNT(DISTINCT bul.id) as cnt,
                               COALESCE(SUM(bul.amount_used), 0) as total_amount,
                               COUNT(DISTINCT e.user_id) as affected_users
                        FROM budget_usage_logs bul
                        JOIN budget_expenses e
                            ON e.id = bul.expense_id
                            AND e.deleted_at IS NULL
                        JOIN budget_received used_br
                            ON used_br.id = bul.approval_id
                            AND used_br.deleted_at IS NULL
                        WHERE bul.deleted_at IS NULL
                        AND EXISTS (
                            SELECT 1
                            FROM budget_received old_br
                            WHERE old_br.user_id = e.user_id
                            AND old_br.deleted_at IS NULL
                            AND (
                                old_br.approved_date < used_br.approved_date
                                OR (old_br.approved_date = used_br.approved_date AND old_br.id < used_br.id)
                            )
                            AND GREATEST(
                                old_br.amount - COALESCE((
                                    SELECT SUM(old_bul.amount_used)
                                    FROM budget_usage_logs old_bul
                                    WHERE old_bul.approval_id = old_br.id
                                    AND old_bul.deleted_at IS NULL
                                ), 0),
                                0
                            ) > 0
                            AND (old_br.expire_date IS NULL OR old_br.expire_date >= DATE(bul.created_at))
                        )";
    $resMisallocated = mysqli_query($conn, $sqlMisallocated);
    if ($resMisallocated) {
        $row = mysqli_fetch_assoc($resMisallocated);
        $stats['misallocated_logs'] = (int)$row['cnt'];
        $stats['misallocated_amount'] = (float)$row['total_amount'];
        $stats['affected_users'] = (int)$row['affected_users'];
    }

    return $stats;
}

function rebuildBudgetUsageLogsFifo($conn) {
    $result = [
        'old_logs_soft_deleted' => 0,
        'new_logs_created' => 0,
        'allocated_amount' => 0,
        'unallocated_expenses' => 0,
        'unallocated_amount' => 0,
    ];

    mysqli_begin_transaction($conn);

    try {
        $sqlDeleteLogs = "UPDATE budget_usage_logs bul
                          JOIN budget_expenses e ON bul.expense_id = e.id
                          SET bul.deleted_at = NOW()
                          WHERE bul.deleted_at IS NULL
                          AND e.deleted_at IS NULL";
        if (!mysqli_query($conn, $sqlDeleteLogs)) {
            throw new Exception("ล้าง usage logs เดิมไม่สำเร็จ: " . mysqli_error($conn));
        }
        $result['old_logs_soft_deleted'] = mysqli_affected_rows($conn);

        $sqlUsers = "SELECT user_id FROM budget_received WHERE deleted_at IS NULL
                     UNION
                     SELECT user_id FROM budget_expenses WHERE deleted_at IS NULL";
        $resUsers = mysqli_query($conn, $sqlUsers);
        if (!$resUsers) {
            throw new Exception("โหลดรายชื่อผู้ใช้ไม่สำเร็จ: " . mysqli_error($conn));
        }

        while ($user = mysqli_fetch_assoc($resUsers)) {
            $userId = (int)$user['user_id'];
            $receipts = [];

            $resReceipts = mysqli_query($conn, "SELECT id, amount, approved_date, expire_date
                                                FROM budget_received
                                                WHERE user_id = $userId
                                                AND deleted_at IS NULL
                                                ORDER BY approved_date ASC, id ASC");
            if (!$resReceipts) {
                throw new Exception("โหลดงบรับไม่สำเร็จ: " . mysqli_error($conn));
            }

            while ($receipt = mysqli_fetch_assoc($resReceipts)) {
                $receipt['remaining'] = (float)$receipt['amount'];
                $receipts[] = $receipt;
            }

            $resExpenses = mysqli_query($conn, "SELECT id, amount, approved_date
                                                FROM budget_expenses
                                                WHERE user_id = $userId
                                                AND deleted_at IS NULL
                                                ORDER BY approved_date ASC, id ASC");
            if (!$resExpenses) {
                throw new Exception("โหลดรายการตัดยอดไม่สำเร็จ: " . mysqli_error($conn));
            }

            while ($expense = mysqli_fetch_assoc($resExpenses)) {
                $moneyToCut = (float)$expense['amount'];
                $expenseDateTs = strtotime($expense['approved_date']);

                foreach ($receipts as &$receipt) {
                    if ($moneyToCut <= 0.0001) break;
                    if ($receipt['remaining'] <= 0.0001) continue;

                    $receiptDateTs = strtotime($receipt['approved_date']);
                    $usageDateTs = max($expenseDateTs, $receiptDateTs);
                    $usageDate = date('Y-m-d', $usageDateTs);

                    if (!empty($receipt['expire_date']) && $usageDate > $receipt['expire_date']) {
                        continue;
                    }

                    $cutAmount = min($moneyToCut, $receipt['remaining']);
                    $expenseId = (int)$expense['id'];
                    $approvalId = (int)$receipt['id'];
                    $safeUsageDate = mysqli_real_escape_string($conn, $usageDate);

                    $sqlInsertLog = "INSERT INTO budget_usage_logs (expense_id, approval_id, amount_used, created_at)
                                     VALUES ($expenseId, $approvalId, '$cutAmount', '$safeUsageDate')";
                    if (!mysqli_query($conn, $sqlInsertLog)) {
                        throw new Exception("สร้าง usage log ใหม่ไม่สำเร็จ: " . mysqli_error($conn));
                    }

                    $receipt['remaining'] -= $cutAmount;
                    $moneyToCut -= $cutAmount;
                    $result['new_logs_created']++;
                    $result['allocated_amount'] += $cutAmount;
                }
                unset($receipt);

                if ($moneyToCut > 0.0001) {
                    $result['unallocated_expenses']++;
                    $result['unallocated_amount'] += $moneyToCut;
                }
            }
        }

        mysqli_commit($conn);
        return $result;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

// ---------------------------------------------------------
// 4. จัดการ Logic เมื่อมีการกดปุ่ม POST
// ---------------------------------------------------------
$alertMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 4.1 เพิ่มคอลัมน์ที่ 1 ---
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

    // --- 4.2 เพิ่มคอลัมน์ที่ 2 ---
    if (isset($_POST['action_add_col2'])) {
        if (!checkColumnExists($conn, $tableName, $col2)) {
            $after = checkColumnExists($conn, $tableName, $col1) ? $col1 : '';
            $res = addImageColumn($conn, $tableName, $col2, $col2Type, $col2Comment, $after);
            if ($res === true) {
                $alertMessage = "<div class='alert alert-success'>✅ <strong>สำเร็จ:</strong> เพิ่มคอลัมน์ <code>{$col2}</code> เรียบร้อยแล้ว</div>";
            } else {
                $alertMessage = "<div class='alert alert-danger'>❌ <strong>ข้อผิดพลาด:</strong> ไม่สามารถเพิ่ม {$col2} ได้: {$res}</div>";
            }
        }
    }

    // --- 4.3 ลบข้อมูลที่ถูก soft delete (เฉพาะ high-admin) ---
    if (isset($_POST['action_purge_soft_deleted'])) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'high-admin') {
            $purgeTables = ['budget_expenses', 'budget_received', 'budget_usage_logs'];
            $purgeResults = [];
            foreach ($purgeTables as $purgeTable) {
                $safeTable = mysqli_real_escape_string($conn, $purgeTable);
                $sql = "DELETE FROM `$safeTable` WHERE deleted_at IS NOT NULL";
                $res = mysqli_query($conn, $sql);
                if ($res !== false) {
                    $count = mysqli_affected_rows($conn);
                    $purgeResults[] = "<li>ลบข้อมูล <b>$purgeTable</b> ถาวร: <b>$count</b> แถว</li>";
                } else {
                    $purgeResults[] = "<li style='color:red;'>เกิดข้อผิดพลาดกับ <b>$purgeTable</b>: ".mysqli_error($conn)."</li>";
                }
            }
            $alertMessage = "<div class='alert alert-success'><b>✅ ผลการลบข้อมูลถาวร:</b><ul>".implode('', $purgeResults)."</ul></div>";
        } else {
            $alertMessage = "<div class='alert alert-danger'>❌ คุณไม่มีสิทธิ์ดำเนินการนี้</div>";
        }
    }

    // --- 4.4 ลบ activity_logs ที่เก่ากว่า 3 เดือน (เฉพาะ high-admin) ---
    if (isset($_POST['action_purge_old_logs'])) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'high-admin') {
            $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            $res = mysqli_query($conn, $sql);
            if ($res) {
                $count = mysqli_affected_rows($conn);
                $alertMessage = "<div class='alert alert-success'>✅ ลบ Activity Logs ที่เก่ากว่า 3 เดือนสำเร็จ จำนวน <b>$count</b> แถว</div>";
            } else {
                $alertMessage = "<div class='alert alert-danger'>❌ ลบ log ไม่สำเร็จ: " . mysqli_error($conn) . "</div>";
            }
        } else {
            $alertMessage = "<div class='alert alert-danger'>❌ คุณไม่มีสิทธิ์ดำเนินการนี้</div>";
        }
    }

    // --- 4.5 จัดเรียง budget_usage_logs ใหม่ตาม FIFO ---
    if (isset($_POST['action_rebuild_usage_fifo'])) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'high-admin') {
            try {
                $repair = rebuildBudgetUsageLogsFifo($conn);
                $alertMessage = "<div class='alert alert-success'><b>✅ จัดเรียงการตัดเงินใหม่สำเร็จ</b><ul>"
                    . "<li>soft delete usage logs เดิม: <b>" . number_format($repair['old_logs_soft_deleted']) . "</b> แถว</li>"
                    . "<li>สร้าง usage logs ใหม่: <b>" . number_format($repair['new_logs_created']) . "</b> แถว</li>"
                    . "<li>ยอดที่ผูกกับงบรับแล้ว: <b>" . number_format($repair['allocated_amount'], 2) . "</b> บาท</li>"
                    . "</ul></div>";
            } catch (Exception $e) {
                $alertMessage = "<div class='alert alert-danger'>❌ จัดเรียงการตัดเงินไม่สำเร็จ: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $alertMessage = "<div class='alert alert-danger'>❌ คุณไม่มีสิทธิ์ดำเนินการนี้</div>";
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
            max-width: 850px; 
            padding: 30px; 
            border-radius: 6px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid #e3e6f0;
        }
        h2, h3 { 
            color: #2c3e50; 
            border-bottom: 2px solid #f0f2f5; 
            padding-bottom: 15px; 
            margin-top: 0; 
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
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-danger:hover { background-color: #bb2d3b; }
        .btn:disabled { background-color: #cccccc; color: #666666; cursor: not-allowed; }

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
                    <th style="width: 20%;">ตารางเป้าหมาย</th>
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

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'high-admin'): ?>
        <?php
            // 1. นับข้อมูลที่ถูกลบ (soft delete)
            $cnt_expenses = 0; $cnt_received = 0; $cnt_usage_logs = 0;
            $res1 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM budget_expenses WHERE deleted_at IS NOT NULL");
            if ($res1) { $cnt_expenses = mysqli_fetch_assoc($res1)['cnt']; }
            $res2 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM budget_received WHERE deleted_at IS NOT NULL");
            if ($res2) { $cnt_received = mysqli_fetch_assoc($res2)['cnt']; }
            $res_usage_logs = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM budget_usage_logs WHERE deleted_at IS NOT NULL");
            if ($res_usage_logs) { $cnt_usage_logs = mysqli_fetch_assoc($res_usage_logs)['cnt']; }

            // 2. นับข้อมูล Logs ที่เก่ากว่า 3 เดือน
            $cnt_logs = 0;
            $res_logs = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
            if ($res_logs) { $cnt_logs = mysqli_fetch_assoc($res_logs)['cnt']; }

            // 3. สถิติการผูกยอดตัดกับงบรับ
            $usageRepairStats = getBudgetUsageRepairStats($conn);
        ?>
        
        <div style="margin-top: 50px;">
            <h3 style="font-size:18px;">🧹 จัดการข้อมูลขยะ (System Cleanup)</h3>
            <table class="info-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Action (รายการ)</th>
                        <th>Description (รายละเอียด)</th>
                        <th style="width: 20%; text-align: center;">Do Action (คำสั่ง)</th>
                    </tr>
                </thead>
                <tbody>
                    

                    <tr>
                        <td><b>ลบข้อมูลที่ถูกลบแล้วแบบถาวร </b></td>
                        <td>
                            พบข้อมูลที่ถูกลบชั่วคราวอยู่ในระบบ:<br>
                            - <code>budget_expenses</code>: <span style="color:#d63384; font-weight:bold;"><?= $cnt_expenses ?></span> แถว<br>
                            - <code>budget_received</code>: <span style="color:#d63384; font-weight:bold;"><?= $cnt_received ?></span> แถว<br>
                            - <code>budget_usage_logs</code>: <span style="color:#d63384; font-weight:bold;"><?= $cnt_usage_logs ?></span> แถว
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('ยืนยันการลบข้อมูล Soft Delete ทั้งหมด?\n\n**ข้อมูลนี้จะหายถาวร!**');" style="margin:0;">
                                <input type="hidden" name="action_purge_soft_deleted" value="1">
                                <button type="submit" class="btn btn-danger" <?= ($cnt_expenses + $cnt_received + $cnt_usage_logs == 0) ? 'disabled' : '' ?>>
                                    🗑️ ลบถาวร
                                </button>
                            </form>
                        </td>
                    </tr>

                    <?php
                    // --- OneClick Fix Expire Date ---
                    require_once __DIR__ . '/../../src/Models/dashboard/tab_received_logic.php';
                    $fix_count = 0;
                    $fix_rows = [];
                    $res = mysqli_query($conn, "SELECT id, approved_date, expire_date FROM budget_received WHERE deleted_at IS NULL");
                    if ($res) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $correct_expire = calFiscalExpireDate($row['approved_date']);
                            if ($row['expire_date'] !== $correct_expire) {
                                $fix_count++;
                                $fix_rows[] = $row['id'];
                            }
                        }
                    }
                    $fix_enabled = $fix_count > 0;
                    ?>
                    <tr>
                        <td><b>แปลงวันหมดอายุ ทั้งหมดเป็นวันสุดท้ายปีงบประมาณถัดไป(2ปี)</b></td>
                        <td> 
                            พบข้อมูลที่ใช้วันที่หมดอายุไม่ถูกต้อง (ไม่ตรงกับวันสุดท้ายของปีงบประมาณถัดไปที่ควรจะเป็น)<br>
                               <code> จำนวน</code> <span style="color:#d63384; font-weight:bold;"><?= $fix_count ?></span> แถว  
                            <?php if ($fix_count > 0): ?>
                                <br><span style="font-size:12px; color:#888;">ID: <?= implode(', ', array_slice($fix_rows, 0, 5)) ?><?= $fix_count > 5 ? ' ...' : '' ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('ยืนยันแก้ไข expire_date ทั้งหมดที่ไม่ถูกต้อง?');" style="margin:0;">
                                <input type="hidden" name="action_fix_expire_date" value="1">
                                <button type="submit" class="btn btn-primary" <?= $fix_enabled ? '' : 'disabled' ?>>
                                    แปลงทั้งหมด
                                </button>
                            </form>
                        </td>
                    </tr>
<?php
// --- ดำเนินการ OneClick Fix Expire Date ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_fix_expire_date'])) {
    require_once __DIR__ . '/../../src/Models/dashboard/tab_received_logic.php';
    $result = oneClickFixExpireDate($conn);
    echo "<div class='alert alert-success' style='margin-top:20px;'>" . htmlspecialchars($result['msg']) . "</div>";
}
?>

                    <tr>
                        <td><b>จัดเรียงการตัดเงินใหม่ตาม FIFO</b></td>
                        <td>
                            ใช้สำหรับแก้ข้อมูลเก่าที่เคยตัดเงินจากงบรับใบใหม่กว่า ก่อนแก้ logic FIFO<br>
                            ระบบจะ soft delete <code>budget_usage_logs</code> เดิมของรายการที่ยังไม่ถูกลบ แล้วสร้างใหม่โดยเรียง:
                            รายจ่ายเก่าก่อน และงบรับเก่าก่อน<br>
                            - พบการตัดยอดที่ใช้เงินรับใบใหม่กว่า ทั้งที่ยังมีเงินรับใบเก่ากว่าของคนเดียวกันเหลืออยู่:
                            <span style="color:#d63384; font-weight:bold;"><?= number_format($usageRepairStats['misallocated_logs']) ?></span> แถว /
                            <span style="color:#d63384; font-weight:bold;"><?= number_format($usageRepairStats['misallocated_amount'], 2) ?></span> บาท
                            จาก <span style="color:#d63384; font-weight:bold;"><?= number_format($usageRepairStats['affected_users']) ?></span> คน
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('ยืนยันจัดเรียง budget_usage_logs ใหม่ตาม FIFO หรือไม่?\n\nระบบจะ soft delete usage logs เดิม แล้วสร้าง footprint ใหม่จาก budget_expenses และ budget_received ที่ยังไม่ถูกลบ');" style="margin:0;">
                                <input type="hidden" name="action_rebuild_usage_fifo" value="1">
                                <button type="submit" class="btn btn-primary">
                                    จัดเรียงใหม่
                                </button>
                            </form>
                        </td>
                    </tr>

                    <tr>
                        <td><b>ลบประวัตการใช้งานระบบ</b></td>
                        <td>
                            พบประวัติการใช้งานที่สร้างมา<b>มากกว่า 3 เดือน</b>:<br>
                            - <code>activity_logs</code>: <span style="color:#d63384; font-weight:bold;"><?= $cnt_logs ?></span> แถว
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('ยืนยันการลบ Activity Logs ที่เก่ากว่า 3 เดือนทิ้งทั้งหมดหรือไม่?');" style="margin:0;">
                                <input type="hidden" name="action_purge_old_logs" value="1">
                                <button type="submit" class="btn btn-danger" <?= ($cnt_logs == 0) ? 'disabled' : '' ?>>
                                    🗑️ ลบ Logs
                                </button>
                            </form>
                        </td>
                    </tr>
                        <td><b>ลบข้อมูลที่ถูกลบแล้วแบบถาวร </b></td>
                    <tr>

                    </tr>

                </tbody>
            </table>
            <div style="color:#842029; font-size:13px; margin-top:8px;">* เฉพาะสิทธิ์ admin เท่านั้น | ข้อมูลจะถูกลบถาวร ไม่สามารถกู้คืนได้</div>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
