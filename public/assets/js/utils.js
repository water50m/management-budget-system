

    // ==========================================
    // 1. จัดการ Dropdown เปลี่ยน Role (High-Admin)
    // ==========================================

    function checkRoleChange(selectElement) {
        // หาค่าเดิมที่เก็บไว้
        const originalValue = selectElement.getAttribute('data-original');
        // หา div ที่เก็บปุ่ม Save/Cancel (อยู่ถัดไปจาก select)
        const actionsDiv = selectElement.nextElementSibling;

        if (selectElement.value !== originalValue) {
            // ถ้าค่าเปลี่ยน -> เอา class 'hidden' ออก (โชว์ปุ่ม)
            actionsDiv.classList.remove('hidden');
            selectElement.classList.add('border-purple-500', 'bg-purple-50');
        } else {
            // ถ้าค่าเหมือนเดิม -> ซ่อนปุ่ม
            actionsDiv.classList.add('hidden');
            selectElement.classList.remove('border-purple-500', 'bg-purple-50');
        }
    }

    function cancelRoleEdit(btnElement) {
        // หา div พ่อ (role-actions)
        const actionsDiv = btnElement.parentElement;
        // หา select ที่เป็นพี่น้อง (อยู่ก่อนหน้า)
        const selectElement = actionsDiv.previousElementSibling;
        
        // คืนค่าเดิม
        selectElement.value = selectElement.getAttribute('data-original');
        
        // ซ่อนปุ่ม และคืนสีปกติ
        actionsDiv.classList.add('hidden');
        selectElement.classList.remove('border-purple-500', 'bg-purple-50');
    }


    // 1. ฟังก์ชันจัดฟอร์แมตเงิน
    function formatCurrency(input, hiddenElementId) {
        let rawValue = input.value.replace(/[^0-9.]/g, '');
        
        if(hiddenElementId) {
             document.getElementById(hiddenElementId).value = rawValue;
        }

        let parts = rawValue.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        
        if (parts.length > 2) {
            input.value = parts[0] + '.' + parts.slice(1).join('');
        } else {
            input.value = parts.join('.');
        }
        //  การใช้งาน
        // <input type="hidden" name="min_amount" id="min_amount_hidden" 
        //        value="<?php echo $data['filters']['min_amount']; ?>">
                            
        //  <input type="text" inputmode="decimal" placeholder="Min" 
        //         value="<?php echo ($data['filters']['min_amount'] !== '') ? number_format((float)$data['filters']['min_amount']) : ''; ?>" 
        //         class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent" 
        //         oninput="AppHelper.formatCurrency(this, 'min_amount_hidden')"></input>
    }


    // 3. แจ้งเตือน (ตัวอย่าง)
    function showAlert(message) {
        alert("แจ้งเตือน: " + message);
    }

