// 1. ฟังก์ชันจัดฟอร์แมตเงิน
function formatCurrency(input, hiddenElementId) {
    let raw = input.value.replace(/[^0-9.]/g, '');

    // กันจุดทศนิยมเกิน 1 จุด
    const parts = raw.split('.');
    if (parts.length > 2) {
        raw = parts[0] + '.' + parts.slice(1).join('');
    }

    // sync hidden ทุกครั้ง
    if (hiddenElementId) {
        document.getElementById(hiddenElementId).value = raw;
    }
    if (raw === '') {
        input.value = '';
        return;
    }

    const [intPart, decPart] = raw.split('.');

    input.value =
        Number(intPart).toLocaleString('en-US') +
        (decPart !== undefined ? '.' + decPart : '');
    
}
    //  การใช้งาน
    // <input type="hidden" name="min_amount" id="min_amount_hidden" 
    //        value="<?php echo $data['filters']['min_amount']; ?>">

    //  <input type="text" inputmode="decimal" placeholder="Min" 
    //         value="<?php echo ($data['filters']['min_amount'] !== '') ? number_format((float)$data['filters']['min_amount']) : ''; ?>" 
    //         class="w-1/2 border-none text-xs text-gray-600 py-2 px-1 text-center focus:ring-0 bg-transparent" 
    //         oninput="AppHelper.formatCurrency(this, 'min_amount_hidden')"></input>



// 3. แจ้งเตือน (ตัวอย่าง)
function showAlert(message) {
    alert("แจ้งเตือน: " + message);
}






