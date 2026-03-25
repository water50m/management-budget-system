<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศความเป็นส่วนตัว - FPA SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden">
        <div class="p-8 max-h-[80vh] overflow-y-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-4 text-center">ประกาศความเป็นส่วนตัว (Privacy Notice)</h1>
            <p class="text-gray-700 mb-4 text-center">สำหรับการเข้าใช้งานระบบบริหารงบประมาณ (FPA SYSTEM)</p>
            <p class="text-gray-700 mb-4">เพื่อให้การดำเนินงานของระบบ FPA SYSTEM สอดคล้องกับพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) ทางหน่วยงานมีความจำเป็นต้องแจ้งให้ท่านทราบถึงการจัดเก็บ ใช้ และประมวลผลข้อมูลส่วนบุคคลของท่าน ดังรายละเอียดต่อไปนี้</p>

            <div class="space-y-5 text-gray-700 text-sm">
                <div>
                    <h2 class="font-semibold mb-2">1. ข้อมูลส่วนบุคคลที่ระบบจัดเก็บ</h2>
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>ข้อมูลระบุตัวตน:</strong> ชื่อ และ นามสกุล</li>
                        <li><strong>ข้อมูลบัญชีผู้ใช้งาน:</strong> ชื่อผู้ใช้งาน (Username) สำหรับใช้เป็นข้อมูลอ้างอิง (หมายเหตุ: ระบบไม่มีการจัดเก็บรหัสผ่าน (Password) ของท่าน โดยการตรวจสอบสิทธิ์จะดำเนินการผ่านระบบ LDAP ขององค์กร)</li>
                        <li><strong>ข้อมูลการทำธุรกรรม:</strong> ประวัติการได้รับเงินงบประมาณ และประวัติการนำเงินไปใช้</li>
                        <li><strong>ข้อมูลทางเทคนิค:</strong> ข้อมูลบันทึกการใช้งานระบบ (Log file)</li>
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold mb-2">2. วัตถุประสงค์ในการประมวลผลข้อมูล</h2>
                    <ul class="list-disc list-inside space-y-1">
                        <li>เพื่อใช้ระบุตัวตนและตรวจสอบสิทธิ์ในการเข้าถึงระบบ FPA SYSTEM</li>
                        <li>เพื่อบันทึก ติดตาม และประมวลผลการเบิกจ่ายงบประมาณให้ถูกต้องตามระเบียบของหน่วยงาน</li>
                        <li>เพื่อรักษาความปลอดภัยของระบบสารสนเทศ และเก็บบันทึกประวัติการใช้งาน (Audit Trail) ให้สอดคล้องกับกฎหมายที่เกี่ยวข้อง</li>
                    </ul>
                </div>

                <div>
                    <h2 class="font-semibold mb-2">3. การรักษาความปลอดภัยและระยะเวลาจัดเก็บ</h2>
                    <p>ข้อมูลส่วนบุคคลของท่านจะถูกเก็บรักษาไว้อย่างปลอดภัยและจำกัดสิทธิ์การเข้าถึงเฉพาะผู้ที่เกี่ยวข้องกับการปฏิบัติงานเท่านั้น โดยจะจัดเก็บไว้ตามระยะเวลาที่ระเบียบงานสารบรรณและบัญชีกำหนด เมื่อพ้นระยะเวลาที่กำหนด ระบบจะดำเนินการทำลายหรือทำให้ข้อมูลไม่สามารถระบุตัวตนได้</p>
                </div>

                <div>
                    <h2 class="font-semibold mb-2">4. การเปิดเผยข้อมูลส่วนบุคคล</h2>
                    <p>ระบบอาจมีความจำเป็นต้องส่งต่อข้อมูลของท่านไปยังหน่วยงานที่เกี่ยวข้องกับการทำธุรกรรมทางการเงินหรือการตรวจสอบทางกฎหมาย (เช่น ธนาคาร หรือหน่วยงานตรวจสอบบัญชี) ทั้งนี้ จะดำเนินการเปิดเผยเฉพาะข้อมูลที่จำเป็นเท่านั้น</p>
                </div>

                <div>
                    <h2 class="font-semibold mb-2">5. สิทธิของเจ้าของข้อมูลและช่องทางการติดต่อ</h2>
                    <p>ท่านมีสิทธิขอเข้าถึง หรือขอแก้ไขข้อมูลส่วนบุคคลของท่านให้ถูกต้องเป็นปัจจุบัน</p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 p-6 text-center">
            <form action="index.php?page=privacy" method="POST" class="space-y-3">
                <button type="submit" class="w-full py-3 px-5 bg-[#f59e0b] hover:bg-[#d97706] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition duration-200">รับทราบและดำเนินการต่อ</button>
            </form>
        </div>
    </div>
</body>
</html>