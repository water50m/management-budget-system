




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

