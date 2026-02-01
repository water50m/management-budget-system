<?php
// views/dashboard_summary/index.php

// 1. เรียกใช้งาน Helper (ปรับ Path ให้ถูกต้องตามโครงสร้างจริงของคุณ)
require_once __DIR__ . '/../../../src/Models/dashboard/tab_summary_logic.php';

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

// --- 2. เตรียมข้อมูล Category ---
$cat_labels = [];
$cat_values = [];

mysqli_data_seek($res_cat, 0);
while ($r = mysqli_fetch_assoc($res_cat)) {
    $cat_labels[] = $r['name_th'];
    $cat_values[] = $r['total_spent'];
}
?>


<div class="space-y-6 animate-fade-in-up overflow-y-auto">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <div>
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                📊 สรุปภาพรวมงบประมาณ
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">ปี <?php echo $selected_year; ?></span>
            </h2>
            <p class="text-sm text-gray-500 mt-1">ข้อมูลสถานะการเงินและการเบิกจ่ายประจำปี</p>
        </div>

        <form hx-get="index.php?page=dashboard&tab=overview"
            hx-target="#tab-content"
            hx-push-url="true"
            class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-200">
            <label class="text-sm font-bold text-gray-700 whitespace-nowrap">📅 เลือกปีงบประมาณ:</label>
            <select name="year" onchange="this.form.requestSubmit()"
                class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 pl-2 pr-8 cursor-pointer shadow-sm">
                <?php foreach ($year_list as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
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
                    <span class="font-bold">+<?php echo number_format($stats['carry_over'], 2); ?></span>
                </div>
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
            </h4>
            <div class="relative h-64">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
            <h4 class="font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
                <i class="fas fa-tags text-pink-500"></i> สัดส่วนตามหมวดหมู่
            </h4>
            <div class="relative h-64 flex justify-center">
                <canvas id="catChart"></canvas>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h4 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-500"></i> 5 อันดับ ผู้เบิกจ่ายสูงสุด
            </h4>
            <a href="index.php?page=dashboard&tab=expense" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition">ดูข้อมูลทั้งหมด →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 w-16 text-center">#</th>
                        <th class="px-6 py-3">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-3">ภาควิชา</th>
                        <th class="px-6 py-3 text-right">ยอดเบิกจ่าย (บาท)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $rank = 1;
                    if (mysqli_num_rows($res_top) > 0):
                        while ($user = mysqli_fetch_assoc($res_top)):
                    ?>
                            <tr class="bg-white hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-bold text-center text-gray-400"><?php echo $rank++; ?></td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">
                                            <?php echo mb_substr($user['first_name'], 0, 1); ?>
                                        </div>
                                        <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    <span class="bg-gray-100 px-2 py-1 rounded text-gray-600"><?php echo $user['dept_name'] ?: '-'; ?></span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-red-600">
                                    <?php echo number_format($user['total_spent'], 2); ?>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>

                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">ยังไม่มีข้อมูลการเบิกจ่ายในปีนี้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    {
        // --- 1. กราฟ Department ---
        <?php
        $dept_labels = [];
        $dept_values = []; // หรือ $dept_received, $dept_spent ตามที่คุณแก้ไปล่าสุด
        if (isset($res_dept) && $res_dept) {
            mysqli_data_seek($res_dept, 0);
            while ($r = mysqli_fetch_assoc($res_dept)) {
                $dept_labels[] = $r['thai_name'];
                $dept_values[] = $r['total_spent']; // *ตรวจสอบชื่อตัวแปรให้ตรงกับโค้ดล่าสุดของคุณ
            }
        }
        ?>
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
</script>