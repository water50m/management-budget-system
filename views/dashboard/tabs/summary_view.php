<?php
// views/dashboard_summary/index.php

// 1. เรียกใช้งาน Helper (ปรับ Path ให้ถูกต้องตามโครงสร้างจริงของคุณ)
require_once __DIR__ . '/../../../src/Models/dashboard/tab_summary_logic.php';
require_once __DIR__ . '/../../../includes/db.php';

$overview = $data['overview_data'] ?? []; // รับก้อนใหญ่มาก่อน

// กระจายตัวแปรเพื่อใช้ใน HTML ด้านล่าง (ให้ชื่อเหมือนเดิม จะได้ไม่ต้องแก้ HTML เยอะ)
$year_list     = $overview['year_list'] ?? [];
$selected_year = $overview['selected_year'] ?? (date('Y') + 543);
$stats         = $overview['stats'] ?? ['received' => 0, 'spent' => 0, 'balance' => 0, 'utilization' => 0];
$res_dept      = $overview['res_dept'] ?? false;
$res_cat       = $overview['res_cat'] ?? false;
$res_top       = $overview['res_top'] ?? false;


// --- 1. เตรียมข้อมูล Department ---
$dept_labels = [];
$dept_received = [];
$dept_spent = [];

mysqli_data_seek($res_dept, 0);
while ($r = mysqli_fetch_assoc($res_dept)) {
    $dept_labels[] = $r['thai_name'];
    $dept_received[] = $r['total_received']; // ฟิลด์ที่เราเพิ่งเพิ่มใน SQL
    $dept_spent[] = $r['total_spent'];       // ฟิลด์ที่เราเพิ่งเพิ่มใน SQL
}

$show_dept = '';
$user_now = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

if ($user_now == 'high-admin') {
    $show_dept = 0;
} else {
    $show_dept = isset($_SESSION['seer']) ? $_SESSION['seer'] : '';
}

// รับค่าเริ่มต้น
$current_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : (date('Y') + 543);
$current_dept = isset($_GET['department_id']) ? $_GET['department_id'] : $show_dept;

// ดึงตัวเลือกสำหรับ Dropdown
$year_options = getFiscalYearOptions($conn);
$dept_options = getDepartments($conn);

$year_list_ = getBudgetYears();
?>


<div class="space-y-6 animate-fade-in-up overflow-y-auto">

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

            <div class="flex items-start gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <i class="fa-solid fa-chart-pie text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        สรุปภาพรวมงบประมาณ
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ปีงบประมาณ <span id="headerYearText"><?php echo $current_year; ?></span>
                        </span>
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        ข้อมูลสถานะการเงินและการเบิกจ่ายประจำปี
                    </p>

                    <?php if (!empty($stats['spent_vs_received_warning'])): ?>
                        <p class="text-xs text-red-500 mt-1 font-medium">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            ยอดใช้จ่ายปีนี้มากกว่างบประมาณที่ได้รับ (เกินงบ)
                            <span class="ml-2">เกินงบ <?php echo number_format($stats['spent_vs_received_amount'], 2); ?> บาท</span>
                        </p>
                    <?php endif; ?>
                    
                    <?php
                    // คำนวณปีงบประมาณปัจจุบัน
                    $current_month = date('m');
                    $current_buddhist_year = date('Y') + 543;
                    $real_fiscal_year = ($current_month >= 10) ? $current_buddhist_year + 1 : $current_buddhist_year;

                    if ($current_year == $real_fiscal_year): ?>
                        <p class="text-xs text-red-400 mt-1 font-medium">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            ข้อมูลนี้เป็นยอดตั้งแต่เริ่มต้นปีงบประมาณ (ปีนี้) ถึงปัจจุบันเท่านั้น (อาจยังไม่ใช่ผลสรุปประจำปี)
                        </p>

                    

                    <?php elseif ($current_year > $real_fiscal_year): ?>
                        <p class="text-xs text-red-500 mt-1 font-medium">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            ข้อมูลนี้เป็นแผนงบประมาณล่วงหน้าสำหรับปี <?php echo $current_year; ?> (ข้อมูลอาจมีการเปลี่ยนแปลง)
                        </p>
                    <?php endif; ?>


                </div>
            </div>

            <div class="w-full md:w-auto bg-gray-50 p-1.5 rounded-xl border border-gray-200 flex flex-col sm:flex-row gap-2">

                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-xs font-bold">ปี:</span>
                    </div>
                    <select name="fiscal_year"
                        class="sync-fiscal-year appearance-none bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-8 py-2 cursor-pointer shadow-sm hover:border-gray-300 transition-colors"
                        hx-get=""
                        hx-target="#tab-content"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="[name='department_id']"
                        hx-indicator="#loadingIndicator">
                        <?php
                        // คำนวณปีงบประมาณปัจจุบันไว้รอเช็คใน loop
                        $current_fiscal = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);

                        foreach ($year_list_ as $y):
                            $is_now = ($y == $current_fiscal);
                            // ถ้าเป็นปีปัจจุบัน ให้ใช้สีเขียว และ cursor-help
                            $current_class = $is_now ? 'bg-blue-50 text-blue-800 font-bold cursor-help' : '';
                            $current_title = $is_now ? 'title="ปีงบประมาณปีปัจจุบัน"' : '';
                        ?>
                            <option value="<?php echo $y; ?>"
                                class="<?php echo $current_class; ?>"
                                <?php echo $current_title; ?>
                                <?php echo ($current_year == $y) ? 'selected' : ''; ?>>
                                <?php echo $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                </div>

                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-xs font-bold">ภาควิชา:</span>
                    </div>
                    <select name="department_id"
                        class="sync-depm appearance-none bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-16 pr-8 py-2 cursor-pointer shadow-sm hover:border-gray-300 transition-colors min-w-[200px]"
                        hx-get=""
                        hx-target="#tab-content"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="[name='fiscal_year']"
                        hx-indicator="#loadingIndicator">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($dept_options as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($d['id'] == $current_dept) ? 'selected' : ''; ?>><?php echo $d['thai_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500 border-t border-r border-b border-gray-100 relative overflow-hidden group">
            <div class="absolute right-[-10px] top-[-10px] opacity-10 transform rotate-12 group-hover:scale-110 transition">
                <i class="fas fa-hand-holding-usd text-6xl text-green-600"></i>
            </div>

            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">งบประมาณที่ได้รับ (รวมยกยอด)</p>

            <h3 class="text-2xl font-bold text-green-700 mt-1">
                <?php echo number_format($stats['total_budget'], 2); ?>
            </h3>

            <div class="mt-2 pt-2 border-t border-dashed border-green-100 text-xs flex flex-col gap-1">
                <div class="flex justify-between text-gray-600">
                    <span>• งบจัดสรรปี <?php echo $selected_year; ?>:</span>
                    <span class="font-bold"><?php echo number_format($stats['received'], 2); ?></span>
                </div>

                <div class="flex justify-between text-green-600">
                    <span>• ยกยอดจากปี <?php echo $stats['prev_year']; ?>:</span>
                    <span class="font-bold">
                        <?php echo ($stats['carry_over'] >= 0 ? '+' : ''); ?>
                        <?php echo number_format($stats['carry_over'], 2); ?>
                    </span>
                </div>
                <?php if (!empty($stats['carry_over_warning'])): ?>
                    <div class="mt-2 text-xs text-red-500 bg-red-50 rounded px-2 py-1 border border-red-200">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        ยอดจ่ายปีที่แล้วมากกว่ายอดรับ ไม่มีเงินคงเหลือยกยอด (ยอดตัดเกินยอดรับ)
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-500 border-t border-r border-b border-gray-100 relative overflow-hidden group">
            <div class="absolute right-[-10px] top-[-10px] opacity-10 transform rotate-12 group-hover:scale-110 transition">
                <i class="fas fa-file-invoice-dollar text-6xl text-red-600"></i>
            </div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">ใช้จ่ายไปแล้ว</p>
            <h3 class="text-2xl font-bold text-red-600 mt-1"><?php echo number_format($stats['spent'], 2); ?></h3>
            <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full mt-2 inline-block">บาท</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500 border-t border-r border-b border-gray-100 relative overflow-hidden group">
            <div class="absolute right-[-10px] top-[-10px] opacity-10 transform rotate-12 group-hover:scale-110 transition">
                <i class="fas fa-wallet text-6xl text-blue-600"></i>
            </div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">งบประมาณคงเหลือ</p>
            <h3 class="text-2xl font-bold text-blue-600 mt-1"><?php echo number_format($stats['balance'], 2); ?></h3>
            <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full mt-2 inline-block">บาท</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500 border-t border-r border-b border-gray-100">
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">อัตราการเบิกจ่าย</p>
            <div class="flex items-end gap-2 mt-1">
                <h3 class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['utilization'], 2); ?>%</h3>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3 overflow-hidden">
                <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-1000" style="width: <?php echo $stats['utilization']; ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
            <h4 class="font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                <i class="fas fa-building text-blue-500"></i> การเบิกจ่ายแยกตามภาควิชา
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ปีงบประมาณ <span id="headerYearText"><?php echo $current_year; ?></span>
                </span>
            </h4>
            <div class="relative h-64">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
            <h4 class="font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                <i class="fas fa-tags text-pink-500"></i> สัดส่วนตามหมวดหมู่
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ปีงบประมาณ <span id="headerYearText"><?php echo $current_year; ?></span>
                </span>
            </h4>
            <div class="relative h-64 flex justify-center">
                <canvas id="catChart"></canvas>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">

        </div>
        <?php include_once __DIR__ . '/../../../src/Helper/sumaries_FTA_year.php'; ?>
    </div>
</div>


<script>
    {

        const deptCtx = document.getElementById('deptChart');
        if (deptCtx) {
            // 🧹 CLEANUP: เช็คว่ามีกราฟเดิมอยู่ไหม ถ้ามีให้ลบทิ้งก่อนสร้างใหม่
            const existingChart = Chart.getChart(deptCtx);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(deptCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dept_labels); ?>,
                    datasets: [{
                            label: 'งบที่ได้รับ (บาท)', // แท่งที่ 1
                            data: <?php echo json_encode($dept_received); ?>,
                            backgroundColor: '#10b981', // สีเขียว (Green-500)
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'ใช้จ่ายไป (บาท)', // แท่งที่ 2
                            data: <?php echo json_encode($dept_spent); ?>,
                            backgroundColor: '#ef4444', // สีแดง (Red-500)
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    // จัดรูปแบบตัวเลขให้มีลูกน้ำ
                                    label += new Intl.NumberFormat('th-TH').format(context.raw);
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            },
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('en-US', {
                                        notation: "compact"
                                    }).format(value);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'index', // ให้ tooltip ขึ้นพร้อมกัน 2 แท่งเมื่อเอาเมาส์ชี้
                        intersect: false,
                    },
                }
            });
        }
    }

    {
        // --- 2. เตรียมข้อมูล Category ---
        <?php
        $cat_labels = [];
        $cat_values = [];
        if (isset($res_cat) && $res_cat) {
            mysqli_data_seek($res_cat, 0);
            while ($r = mysqli_fetch_assoc($res_cat)) {
                $cat_labels[] = $r['name_th'];
                $cat_values[] = $r['total_spent'];
            }
        }

        ?>

        const catCtx = document.getElementById('catChart');

        if (catCtx) {
            // 🧹 CLEANUP: ลบกราฟเก่าทิ้งก่อน
            const existingCatChart = Chart.getChart(catCtx);
            if (existingCatChart) {
                existingCatChart.destroy();
            }

            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($cat_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($cat_values); ?>,
                        backgroundColor: ['#f87171', '#fbbf24', '#34d399', '#60a5fa', '#a78bfa', '#f472b6'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                // 🔧 ส่วนนี้คือการแต่งข้อความตอนเอาเมาส์ชี้
                                label: function(context) {
                                    let label = ': ';
                                    let value = context.raw;
                                    label = new Intl.NumberFormat('th-TH').format(value) + ' บาท';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // ดักจับเหตุการณ์เมื่อมีการเปลี่ยนค่าใน Select ที่มี class "sync-fiscal-year"
    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('sync-fiscal-year')) {
            const newValue = e.target.value;

            // วนลูปหา Select ตัวอื่นๆ ที่มี class เดียวกัน แล้วจับเปลี่ยนค่าให้เหมือนกัน
            document.querySelectorAll('.sync-fiscal-year').forEach(el => {
                if (el !== e.target) {
                    el.value = newValue;
                }
            });
        }
    });

    // ดักจับเหตุการณ์เมื่อมีการเปลี่ยนค่าใน Select ที่มี class "sync-fiscal-year"
    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('sync-depm')) {
            const newValue_ = e.target.value;

            // วนลูปหา Select ตัวอื่นๆ ที่มี class เดียวกัน แล้วจับเปลี่ยนค่าให้เหมือนกัน
            document.querySelectorAll('.sync-depm').forEach(el => {
                if (el !== e.target) {
                    el.value = newValue_;
                }
            });
        }
    });

    // ดักจับของ Department ด้วย (ถ้าต้องการ) ให้ทำเหมือนกันแต่เปลี่ยนชื่อ class เป็น sync-dept
</script>