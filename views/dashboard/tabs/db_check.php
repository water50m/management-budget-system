<?php
include_once __DIR__ . '/../../../includes/db.php';
?>


    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .table-card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #1a56db; }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
        th { background-color: #f8f9fa; color: #555; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .badge { background: #e1effe; color: #1e429f; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>

<div class="overflow-y-auto" >

    <h1>🔍 รายชื่อตารางในฐานข้อมูล: <?php echo $dbname; ?></h1>

    <?php
    // 2. คำสั่งดึงรายชื่อตารางทั้งหมด
    $sql = "SHOW TABLES";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_array()) {
            $tableName = $row[0];
            ?>
            
            <div class="table-card">
                <h2>ตาราง: <code><?php echo $tableName; ?></code></h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Field (ชื่อฟิลด์)</th>
                            <th>Type (ประเภท)</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 3. คำสั่งดึงรายละเอียดของแต่ละตาราง
                        $sql_cols = "SHOW COLUMNS FROM `$tableName`";
                        $res_cols = $conn->query($sql_cols);
                        
                        if ($res_cols) {
                            while($col = $res_cols->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><b>" . $col['Field'] . "</b></td>";
                                echo "<td>" . $col['Type'] . "</td>";
                                echo "<td>" . $col['Null'] . "</td>";
                                echo "<td>" . ($col['Key'] == 'PRI' ? '<span class="badge">PK</span>' : $col['Key']) . "</td>";
                                echo "<td>" . $col['Default'] . "</td>";
                                echo "<td>" . $col['Extra'] . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php
        }
    } else {
        echo "<p>❌ ไม่พบตารางใดๆ ในฐานข้อมูลนี้</p>";
    }
    
    $conn->close();
    ?>

</body>
</html>