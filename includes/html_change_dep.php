<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'high-admin'): ?>

    <form action="index.php?page=profile&id=<?php echo $user_info['id']; ?>" method="POST" class="inline-flex items-center justify-center gap-2 mt-2">

        <input type="hidden" name="action" value="change_department">
        <input type="hidden" name="user_id" value="<?php echo $user_info['id']; ?>">
        <input type="hidden" id="original_dept_id" value="<?php echo $user_info['department_id']; ?>">
        <input type="hidden" name="submit_page" value="<?php echo htmlspecialchars($_GET['page'] ?? 'profile'); ?>">
        <input type="hidden" name="submit_tab" value="<?php echo htmlspecialchars($_GET['tab'] ?? ''); ?>">

        <div class="relative">
            <select name="new_department_id"
                id="dept_select"
                onchange="showDeptActions()"
                class="appearance-none bg-gray-100 border border-transparent hover:border-gray-300 text-xs font-semibold text-gray-600 rounded-full py-1 pl-3 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition cursor-pointer">

                <?php foreach ($department_list as $dept): ?>
                    <?php
                    $selected = ($dept['id'] == $user_info['department_id']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo $selected; ?>>
                        <?php echo $dept['thai_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                </svg>
            </div>
        </div>

        <div id="dept_actions" class="hidden flex items-center gap-1">
            <button type="submit"
                class="w-6 h-6 rounded-full bg-green-100 text-green-600 hover:bg-green-200 flex items-center justify-center transition"
                title="บันทึกการเปลี่ยนแปลง">
                <i class="fas fa-check text-xs"></i>
            </button>

            <button type="button"
                onclick="cancelDeptChange()"
                class="w-6 h-6 rounded-full bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center transition"
                title="ยกเลิก">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

    </form>

<?php else: ?>

    <div class="mt-2 inline-block bg-gray-100 px-3 py-1 rounded-full text-xs font-semibold text-gray-600">
        <?php echo $user_info['department_name']; ?>
    </div>

<?php endif; ?>