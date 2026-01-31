<?php
// ไฟล์ function ต่างๆ include ที่นี่ครั้งเดียวพอ
include_once __DIR__ . "/modal_add_budget.php";
include_once __DIR__ . "/../../includes/userRoleManageFunction.php";
include_once __DIR__ . "/../../includes/saveLogFunction.php";
?>

<div id="tab-content" class="w-full px-4 p-4 md:px-8 flex-1 flex flex-col overflow-hidden animate-fade-in">
    <?php
    // Logic สำหรับการโหลดครั้งแรก (First Load)
    // เช็คว่า Tab ปัจจุบันคืออะไร แล้วเรียกไฟล์ View ย่อยมาแสดง
    
    // แตกตัวแปร array $data ออกมาเป็นตัวแปรย่อย ($filters, $approvals, etc.)
    if(isset($data)) extract($data);

    // Default Tab = approval
    $tab = $current_tab ?? 'approval';

    switch ($tab) {
        case 'expense':
            include __DIR__ . '/tabs/expense_view.php';
            break;
        case 'users':
            include __DIR__ . '/tabs/users_view.php';
            break;
        case 'logs':
            include __DIR__ . '/tabs/logs_view.php';
            break;
        default:
            include __DIR__ . '/tabs/received_view.php';
            break;
    }
    ?>
</div>

<script>
function confirmRestore(logId, actionType, relatedId) {
    // relatedId คือ data_id หรือ target_id แล้วแต่กรณี
    
    if(!confirm('คุณต้องการกู้คืนข้อมูลจากรายการนี้ใช่หรือไม่?')) return;

    // สร้าง Form จำลองเพื่อส่งค่าแบบ POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?page=dashboard'; // ส่งไปหน้าที่รวม Logic ข้างบนไว้

    // สร้าง Input: action
    const inputAction = document.createElement('input');
    inputAction.type = 'hidden';
    inputAction.name = 'action';
    inputAction.value = 'restore_data';
    form.appendChild(inputAction);

    // สร้าง Input: action_type
    const inputType = document.createElement('input');
    inputType.type = 'hidden';
    inputType.name = 'action_type';
    inputType.value = actionType;
    form.appendChild(inputType);

    const getLogId = document.createElement('input');
    getLogId.type = 'hidden';
    getLogId.name = 'logId';
    getLogId.value = logId;
    form.appendChild(getLogId)

    // ตรวจสอบว่าจะส่ง data_id หรือ target_id
    if (actionType === 'delete_user') {
        const inputTarget = document.createElement('input');
        inputTarget.type = 'hidden';
        inputTarget.name = 'target_id'; // ตามโจทย์
        inputTarget.value = relatedId;
        form.appendChild(inputTarget);
    } else {
        const inputData = document.createElement('input');
        inputData.type = 'hidden';
        inputData.name = 'data_id';
        inputData.value = relatedId;
        form.appendChild(inputData);
    }


    // ส่ง Form
    document.body.appendChild(form);
    form.submit();
}

</script>

