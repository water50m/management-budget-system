<?php
// 1. เรียกใช้ mPDF
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. รับค่าจาก URL (GET Request)
// ถ้าไม่ได้ส่งปีมา ให้ใช้ปีปัจจุบัน + 543
$current_year = isset($_GET['fiscal_year']) && !empty($_GET['fiscal_year'])
    ? $_GET['fiscal_year']
    : (date('Y') + 543);

// ถ้า department_id เป็นค่าว่าง '' หรือ 0 ให้ถือว่าเป็น null (เลือกทั้งหมด)
$current_dept = isset($_GET['department_id']) && !empty($_GET['department_id'])
    ? $_GET['department_id']
    : null;

// 3. ดึงข้อมูล (ใช้ Logic เดียวกับหน้าเว็บ)
$data = getFpaSummary($conn, $current_year, $current_dept);
$department_list = getDepartments($conn);

// 🟢 เทียบหาชื่อภาควิชาจาก Array
$dept_label = "ภาพรวมทุกภาควิชา"; // ค่าเริ่มต้น

if ($current_dept) {
    foreach ($department_list as $dept) {
        // ถ้าเจอ ID ที่ตรงกัน ให้เอาชื่อไทยมาใช้ แล้วหยุดหาทันที
        if ($dept['id'] == $current_dept) {
            if ($dept['id'] == 5){
                $dept_label = $dept['thai_name'];
                break; 
            } 
            else if ($dept['id'] == 7){
                $dept_label = $dept['thai_name'];
                break; 
            } 
            else {
                $dept_label = $dept['thai_name'];
                break; 
            }
            
        }
    }
}

// 4. เตรียมตัวแปรสำหรับคำนวณยอดรวม (เพื่อเอาไปแสดงท้ายตาราง PDF)
$grand_total = 0;
$sum_travel  = 0;
$sum_book    = 0;
$sum_comp    = 0;
$sum_sci     = 0;
$pdf_rows    = []; // เก็บข้อมูลที่ผ่านการกรองแล้วไว้ loop ใน PDF

// 5. วนลูปคำนวณยอดเงินล่วงหน้า
if (!empty($data)) {
    foreach ($data as $row) {
        // คำนวณยอดรวมรายคน
        $row_total = $row['travel'] + $row['book'] + $row['comp'] + $row['sci'];

        // กรองยอดที่เป็น 0 ออก (เหมือนหน้าเว็บ)
        if ($row_total <= 0) continue;



        // เก็บข้อมูลแถวนี้ ใส่ Array ใหม่ เพื่อส่งไปวนลูปใน PDF (จะได้ไม่ต้องคำนวณซ้ำ)
        $row['total_amount'] = $row_total;
        $pdf_rows[] = $row;
    }
}
// *สำคัญ* ระบุ Path ของฟอนต์ให้ถูกต้อง (เลือกใช้ 1 ใน 2 วิธีนี้)
// วิธี 1: ถ้า fonts อยู่ระดับเดียวกับ vendor
$fontPath = __DIR__ . '/fonts';
// วิธี 2: ถ้า fonts อยู่หน้าบ้าน (Root)
// $fontPath = $_SERVER['DOCUMENT_ROOT'] . '/ReschDB/fonts'; 

// ตั้งค่า mPDF
$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L', // แนวนอน
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

// 2. HTML CSS สำหรับ PDF (เขียน CSS แบบบ้านๆ)
ob_start();
?>

<style>
    /* CSS จำลอง Tailwind สำหรับ mPDF */
    body {
        font-family: 'sarabun', sans-serif;
        font-size: 16pt;
        color: #333;
    }

    /* ปรับขนาด Font ให้ใหญ่ขึ้นสำหรับเอกสาร */

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
    }

    th {
        border: 1px solid #777;
        padding: 8px;
        font-size: 16pt;
        background-color: #f0f0f0;
        font-weight: bold;
    }

    td {
        border: 1px solid #ccc;
        padding: 8px;
        vertical-align: top;
        font-size: 16pt;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .text-left {
        text-align: left;
    }

    .font-bold {
        font-weight: bold;
    }

    .text-red {
        color: #d00;
    }

    .bg-red-light {
        background-color: #fff5f5;
    }

    .bg-gray {
        background-color: #eaeaea;
    }

    .header-box {
        text-align: center;
        margin-bottom: 20px;
    }

    .dept-badge {
        font-size: 14pt;
        color: #555;
    }
</style>

<body>

    <div class="header-box">
        <h2 style="font-size: 20pt; margin: 0; font-weight: bold;">
            สรุปงบประมาณ FPA ประจำปีงบประมาณ <?php echo $current_year; ?>
        </h2>
        <div style="margin-top: 5px;">
            <?=  $dept_label ?>
        </div>
        <div style="font-size: 12pt; color: #888; margin-top: 5px;">
            พิมพ์เมื่อ: <?php echo dateToThai(date('d/m/Y H:i')); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="8%" class="bg-gray">ลำดับ</th>
                <th rowspan="2" class="bg-gray text-left">ชื่อ - นามสกุล</th>
                <th colspan="5" class="bg-red-light text-red">
                    ความต้องการใช้เงิน FPA (ในปี <?php echo substr($current_year, -2); ?>)
                </th>
            </tr>
            <tr class="bg-red-light text-red">
                <th width="12%">ไปราชการ</th>
                <th width="12%">วัสดุหนังสือ</th>
                <th width="12%">วัสดุคอมฯ</th>
                <th width="12%">วัสดุวิทย์</th>
                <th width="12%">รวม</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            if (!empty($data)) {
                foreach ($data as $row):
                    $row_total = $row['travel'] + $row['book'] + $row['comp'] + $row['sci'];
                    if ($row_total <= 0) continue;

                    $grand_total += $row_total;
                    $sum_travel += $row['travel'];
                    $sum_book += $row['book'];
                    $sum_comp += $row['comp'];
                    $sum_sci += $row['sci'];
            ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td class="text-left font-bold"><?php echo $row['name']; ?></td>

                        <td class="text-right text-red">
                            <?php echo $row['travel'] > 0 ? number_format($row['travel'], 2) : '-'; ?>
                        </td>
                        <td class="text-right text-red">
                            <?php echo $row['book'] > 0 ? number_format($row['book'], 2) : '-'; ?>
                        </td>
                        <td class="text-right text-red">
                            <?php echo $row['comp'] > 0 ? number_format($row['comp'], 2) : '-'; ?>
                        </td>
                        <td class="text-right text-red">
                            <?php echo $row['sci'] > 0 ? number_format($row['sci'], 2) : '-'; ?>
                        </td>

                        <td class="text-right font-bold bg-gray">
                            <?php echo number_format($row_total, 2); ?>
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
                <td class="text-right" style="border-bottom: 3px double black;">
                    <?php echo number_format($grand_total, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

</body>

</html>

<?php
// จบการทำงาน HTML
$html = ob_get_clean();

// ---------------------------------------------------------
// ✅ ส่วนตั้งค่า mPDF + Custom Font (ตามที่คุณขอมา)
// ---------------------------------------------------------

// *สำคัญ* ระบุ Path ของฟอนต์ให้ถูกต้อง
$fontPath = __DIR__ . '/fonts'; // ตรวจสอบว่าโฟลเดอร์นี้มีไฟล์ฟอนต์อยู่จริง

// ดึงค่า Config เดิมของ mPDF มาก่อน
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

// สร้าง object mPDF พร้อมตั้งค่าฟอนต์ใหม่
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L', // แนวนอน (Landscape) เหมาะกับตารางกว้างๆ
    'fontDir' => array_merge($fontDirs, [$fontPath]), // เพิ่ม path font ของเราเข้าไป
    'fontdata' => $fontData + [
        'sarabun' => [
            'R'  => 'THSarabunNew.ttf',
            'B'  => 'THSarabunNew Bold.ttf',
            'I'  => 'THSarabunNew Italic.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf'
        ]
    ],
    'default_font' => 'sarabun' // บังคับใช้ฟอนต์นี้เป็นค่าเริ่มต้น
]);

// เขียน HTML ลง PDF
$mpdf->WriteHTML($html);

// Output
$filename = "สรุปงบประมาณ_FPA_{$current_year}.pdf";
if (isset($_GET['action']) && $_GET['action'] == 'download') {
    $mpdf->Output($filename, 'D');
} else {
    $mpdf->Output($filename, 'I');
}
?>