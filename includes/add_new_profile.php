<?php
global $conn;
// ตรวจสอบการเพิ่มข้อมูล User

// --- ดึงข้อมูล Master Data สำหรับ Dropdown ---
// ดึงรายชื่อภาควิชา
$dept_sql = "SELECT id, thai_name FROM departments ORDER BY thai_name ASC";
$dept_query = mysqli_query($conn, $dept_sql);

$role_sql = "SELECT id, description as role_name FROM roles ORDER BY id ASC";
$role_query = mysqli_query($conn, $role_sql);
?>
<div id="addUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-2xl border border-blue-100">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> เพิ่มข้อมูลบุคลากร
                </h3>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-blue-100 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form action="index.php?page=add-profile" method="POST" class="px-6 py-6 space-y-6">
                <input type="hidden" name="action" value="add_user">
                <input type="hidden" name="current_page" value="<?php echo $_GET['page'] ?? 'dashboard'; ?>">
                <input type="hidden" name="current_tab" value="<?php echo $_GET['tab'] ?? ''; ?>">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 mb-1">คำนำหน้า <span class="text-red-500">*</span></label>
                        <select name="prefix" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border">
                            <option value="">-- เลือกคำนำหน้า (Select Prefix) --</option>

                            <optgroup label="ภาษาไทย (Thai)">
                                <option value="นาย">นาย (Mr.)</option>
                                <option value="นาง">นาง (Mrs.)</option>
                                <option value="นางสาว">นางสาว (Ms.)</option>
                                <option value="ดร.">ดร. (Dr.)</option>
                                <option value="ผศ.ดร.">ผศ.ดร. (Asst. Prof. Dr.)</option>
                                <option value="รศ.ดร.">รศ.ดร. (Assoc. Prof. Dr.)</option>
                                <option value="ศ.ดร.">ศ.ดร. (Prof. Dr.)</option>
                            </optgroup>

                            <optgroup label="English (International)">
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Asst. Prof. Dr.">Asst. Prof. Dr.</option>
                                <option value="Assoc. Prof. Dr.">Assoc. Prof. Dr.</option>
                                <option value="Prof. Dr.">Prof. Dr.</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-gray-700 mb-1">ชื่อจริง <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="ภาษาไทย/อังกฤษ">
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-xs font-bold text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="ภาษาไทย/อังกฤษ">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">ภาควิชา/สังกัด <span class="text-red-500">*</span></label>
                        <select name="department_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">-- กรุณาเลือกภาควิชา --</option>
                            <?php while ($dept = mysqli_fetch_assoc($dept_query)): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['thai_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                </div>

                <div class="border-t border-gray-100 my-2"></div>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <h4 class="text-sm font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-lock"></i> ข้อมูลสำหรับเข้าสู่ระบบ
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" required autocomplete="off"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 font-mono text-blue-600 font-bold"
                                placeholder="เช่น somchai.j">
                            <p class="text-[10px] text-gray-500 mt-1">* ใช้สำหรับ Login (ไม่ใช้รหัสผ่าน)</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">บทบาทผู้ใช้งาน</label>
                            <select name="role_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm p-2 border">
                                <?php while ($role = mysqli_fetch_assoc($role_query)): ?>
                                    <option value="<?= $role['id'] ?>"
                                        <?php echo ($role['id'] == 7) ? 'selected' : ''; ?>>
                                        <?= $role['role_name'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 pt-2 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md font-medium transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>