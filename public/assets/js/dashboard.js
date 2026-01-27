// public/assets/js/dashboard.js

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


// ==========================================
// 2. จัดการ Modal เพิ่มรายการ (Add Expense)
// ==========================================
let currentOriginalBalance = 0;

function openExpenseModal(userId, userName, balance) {
    // 1. ใส่ค่าลง Form
    const idInput = document.getElementById('modalUserId');
    const nameSpan = document.getElementById('modalUserName');
    
    // [ใหม่] จุดแสดงยอดเงิน
    const balanceDisplay = document.getElementById('modalBalanceDisplay');
    currentOriginalBalance = balance;

    if (idInput) idInput.value = userId;
    if (nameSpan) nameSpan.innerText = '👤 สำหรับ: ' + userName;
    
    // [ใหม่] อัปเดตตัวเลขเงินคงเหลือ (จัด Format มีลูกน้ำ)
    if (balanceDisplay) {
        // แปลงเลขเป็น format เงิน (เช่น 10,000.00)
        let formattedBalance = new Intl.NumberFormat('th-TH', { 
            style: 'decimal', 
            minimumFractionDigits: 2 
        }).format(balance);
        
        balanceDisplay.innerText = formattedBalance + ' บาท';
    }
    
    // 2. แสดง Modal
    const modal = document.getElementById('expenseModal');
    if (modal) modal.classList.remove('hidden');
}

function handleAmountInput(input) {
    // 1. ล้างค่าที่ไม่ใช่ตัวเลขและจุดทศนิยมออก (ป้องกันคนพิมพ์ตัวอักษร)
    let value = input.value.replace(/[^0-9.]/g, '');
    
    // ป้องกันจุดทศนิยมซ้ำ
    const parts = value.split('.');
    if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');

    // 2. เก็บค่าตัวเลขจริงลงใน Hidden Input เพื่อส่งไป PHP
    const realValue = parseFloat(value) || 0;
    document.getElementById('inputAmountReal').value = realValue;

    // 3. ทำ Format สำหรับแสดงผลในช่อง Input (เฉพาะตอนที่ไม่ได้พิมพ์จุดค้างไว้)
    if (value !== "" && !value.endsWith('.')) {
        input.value = new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 0, // ไม่บังคับ .00 ขณะพิมพ์เพื่อให้พิมพ์ถนัด
            maximumFractionDigits: 2
        }).format(realValue);
        
        // ถ้ามีทศนิยม ให้เติมทศนิยมกลับเข้าไป (เพราะ Intl.NumberFormat อาจจะตัดออกขณะกำลังพิมพ์)
        if (value.includes('.')) {
            const decimalPart = value.split('.')[1];
            if (decimalPart !== undefined) {
                // ถ้ามีเลขหลังจุดแล้ว ไม่ต้องทำอะไรเพิ่ม แต่ถ้าเพิ่งพิมพ์จุด Intl จะจัดการให้
            }
        }
    } else {
        input.value = value; // แสดงค่าดิบถ้ากำลังพิมพ์จุด หรือค่าว่าง
    }

    // 4. คำนวณยอดคงเหลือใหม่
    calculateRealtimeBalance(realValue);
}


// ฟังก์ชันคำนวณสด (Real-time)
function calculateRealtimeBalance(realValue) {
    const inputVal = realValue;
    const amountToCut = parseFloat(inputVal) || 0;
    
        
    // คำนวณยอดใหม่ โดยใช้ตัวแปรกลางที่จำค่า 18,000 ไว้
    const newBalance = currentOriginalBalance - amountToCut;

    // ส่งค่าไปอัปเดต โดยฝั่งซ้ายต้องเป็น currentOriginalBalance เสมอ!
    updateBalanceUI(currentOriginalBalance, newBalance);
}

// ฟังก์ชันช่วยจัดการตัวเลขและสีสัน
function updateBalanceUI(originalBal, nextBal) {
    const formatter = new Intl.NumberFormat('th-TH', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });

    const oldDisplay = document.getElementById('modalBalanceDisplay');
    const newDisplay = document.getElementById('modalNewBalanceDisplay');

    // จุดสำคัญ: ฝั่งซ้ายต้องโชว์ยอดเดิม (originalBal) ห้ามเปลี่ยนเป็น 0
    if (oldDisplay) oldDisplay.innerText = formatter.format(originalBal) + ' บาท';
    
    if (newDisplay) {
        newDisplay.innerText = formatter.format(nextBal) + ' บาท';
        
        // เช็คสีเตือน
        if (nextBal < 0) {
            newDisplay.classList.replace('text-blue-700', 'text-red-600');
        } else {
            newDisplay.classList.replace('text-red-600', 'text-blue-700');
        }
    }
}

function closeExpenseModal() {
    const modal = document.getElementById('expenseModal');
    if (modal) modal.classList.add('hidden');
}

// ปิด Modal เมื่อกดพื้นที่สีดำข้างนอก
window.onclick = function(event) {
    const modal = document.getElementById('expenseModal');
    if (event.target == modal) {
        closeExpenseModal();
    }
}


//  ฟังก์ชันตั้งค่า Checkbox ให้ใส่วันที่ปัจจุบันอัตโนมัติ
// ฟังก์ชัน 1: ใส่ที่ Checkbox (กดปุ๊บ วันที่มาปั๊บ)
function toggleTodayDate(checkbox, dateInputId) {
    const dateInput = document.getElementById(dateInputId);
    
    // หาวันที่ปัจจุบัน
    const getToday = () => {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (checkbox.checked) {
        dateInput.value = getToday();
    } else {
        dateInput.value = ''; // ถ้าอยากให้ลบออกเมื่อติ๊กออก
    }
}

// ฟังก์ชัน 2: ใส่ที่ Input วันที่ (ถ้าแก้เอง ติ๊กต้องหลุด)
function checkManualDate(dateInput, checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    
    // หาวันที่ปัจจุบันเพื่อเทียบ
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayStr = `${year}-${month}-${day}`;

    if (dateInput.value !== todayStr) {
        checkbox.checked = false;
    } else {
        checkbox.checked = true;
    }
}