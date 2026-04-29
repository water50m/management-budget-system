<?php
// 1. เรียกใช้ mPDF
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. รับค่าจาก URL
$current_year = isset($_GET['fiscal_year']) && !empty($_GET['fiscal_year']) ? $_GET['fiscal_year'] : (date('Y') + 543);
$current_dept = isset($_GET['department_id']) && !empty($_GET['department_id']) ? $_GET['department_id'] : null;

// 3. ดึงข้อมูล
$data = getFpaSummary($conn, $current_year, $current_dept);
$department_list = getDepartments($conn);

// หาชื่อภาควิชา
$dept_label = "ภาพรวมทุกภาควิชา";
if ($current_dept) {
    foreach ($department_list as $dept) {
        if ($dept['id'] == $current_dept) {
            $dept_label = $dept['thai_name'];
            break;
        }
    }
}

// 4. เตรียมตัวแปรคำนวณยอดรวม และ กรองข้อมูล
$grand_total = 0;
$sum_travel  = 0;
$sum_book    = 0;
$sum_comp    = 0;
$sum_sci     = 0;
$pdf_rows    = []; // เก็บข้อมูลที่ผ่านการกรองแล้ว

if (!empty($data)) {
    foreach ($data as $row) {
        // คำนวณยอดรวมรายคน
        $row_total = $row['travel'] + $row['book'] + $row['comp'] + $row['sci'];

        // กรองยอดที่เป็น 0 ออก
        if ($row_total <= 0) continue;

        // บวกยอดรวมสะสม (Sum) ไว้เลย จะได้ไม่ต้องไปบวกใน HTML
        $sum_travel += $row['travel'];
        $sum_book   += $row['book'];
        $sum_comp   += $row['comp'];
        $sum_sci    += $row['sci'];
        $grand_total += $row_total;

        // เก็บข้อมูลไว้แสดงผล
        $row['total_amount'] = $row_total;
        $pdf_rows[] = $row;
    }
}

// 5. เริ่มสร้าง HTML (CSS & Table)
ob_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'sarabun', sans-serif; font-size: 16pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { 
            border: 1px solid #000000; 
            padding: 8px; 
            vertical-align: middle;
            color: #000000;
        }
        th { background-color: #f0f0f0; } /* เพิ่มสีหัวตารางนิดหน่อย */
        .text-red-custom { color: #d00000; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f0f0f0; }
    </style>
</head>
<body>

    <div class="text-center">
        <h2 style="margin: 0;">สรุปงบประมาณ FPA ของคณะวิทยาศาสตร์การแพทย์</h2>
        <h2 style="margin: 0;">ประจำปีงบประมาณ <?php echo $current_year; ?></h2>
        <div style="margin-top: 5px; font-weight: bold;"><?= $dept_label ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="8%">ลำดับ</th>
                <th rowspan="2">ชื่อ - นามสกุล</th>
                <th colspan="5" class="text-red-custom">
                    ความต้องการใช้เงิน FPA ของคณะฯ (ในปี <?php echo substr($current_year, -2); ?>)
                </th>
            </tr>
            <tr>
                <th class="text-red-custom">ไปราชการ</th>
                <th class="text-red-custom">วัสดุ<br>หนังสือ/ตำรา</th>
                <th class="text-red-custom">วัสดุ<br>คอมพิวเตอร์</th>
                <th class="text-red-custom">วัสดุ<br>วิทยาศาสตร์<br>หรือการแพทย์</th>
                <th class="text-red-custom">รวม</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            if (!empty($pdf_rows)) {
                foreach ($pdf_rows as $row):
            ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td class="text-left font-bold"><?php echo $row['name']; ?></td>

                    <td class="text-right text-red-custom">
                        <?php echo $row['travel'] > 0 ? number_format($row['travel'], 2) : '-'; ?>
                    </td>
                    <td class="text-right text-red-custom">
                        <?php echo $row['book'] > 0 ? number_format($row['book'], 2) : '-'; ?>
                    </td>
                    <td class="text-right text-red-custom">
                        <?php echo $row['comp'] > 0 ? number_format($row['comp'], 2) : '-'; ?>
                    </td>
                    <td class="text-right text-red-custom">
                        <?php echo $row['sci'] > 0 ? number_format($row['sci'], 2) : '-'; ?>
                    </td>
                    <td class="text-right font-bold bg-gray text-red-custom">
                        <?php echo number_format($row['total_amount'], 2); ?>
                    </td>
                </tr>
            <?php
                endforeach;
            } else {
                echo '<tr><td colspan="7" class="text-center">ไม่พบข้อมูล</td></tr>';
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="bg-gray font-bold">
                <td colspan="2" class="text-center">ยอดรวมทั้งสิ้น</td>
                <td class="text-right"><?php echo number_format($sum_travel, 2); ?></td>
                <td class="text-right"><?php echo number_format($sum_book, 2); ?></td>
                <td class="text-right"><?php echo number_format($sum_comp, 2); ?></td>
                <td class="text-right"><?php echo number_format($sum_sci, 2); ?></td>
                <td class="text-right"><?php echo number_format($grand_total, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="text-right" style="font-size: 12pt; color: #888; margin-top: 10px;">
        พิมพ์เมื่อ: <?php echo date('d/m/Y H:i'); // ปรับ dateToThai ตาม function ที่คุณมี ?>
    </div>

</body>
</html>

<?php
// จบการเก็บ HTML
$html = ob_get_clean();

// ---------------------------------------------------------
// 6. ตั้งค่า mPDF (ประกาศครั้งเดียวตรงนี้)
// ---------------------------------------------------------

$fontPath = __DIR__ . '/fonts'; // ⚠️ ตรวจสอบ Path นี้ให้ดีว่ามีอยู่จริง

$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'fontDir' => array_merge($fontDirs, [$fontPath]),
        'fontdata' => $fontData + [
            'sarabun' => [
                'R'  => 'THSarabunNew.ttf',
                'B'  => 'THSarabunNew Bold.ttf',
                'I'  => 'THSarabunNew Italic.ttf',
                'BI' => 'THSarabunNew BoldItalic.ttf'
            ]
        ],
        'default_font' => 'sarabun'
    ]);

    $mpdf->WriteHTML($html);

    $filename = "สรุปงบประมาณ_FPA_{$current_year}.pdf";
    // แสดงผลบน Browser (I) หรือ ดาวน์โหลด (D)
    $mpdf->Output($filename, 'I');

} catch (\Mpdf\MpdfException $e) {
    echo "เกิดข้อผิดพลาด PDF: " . $e->getMessage();
}
?>