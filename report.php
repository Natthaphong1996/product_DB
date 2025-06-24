<?php
include 'db_config.php';

// --- การตั้งค่าเริ่มต้น ---
// หากไม่มีการเลือกวันที่ ให้ใช้เป็นเดือนปัจจุบัน
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$params = [$start_date, $end_date];
$types = 'ss';

// --- ดึงข้อมูลสำหรับ Key Metrics (KPIs) ---
// 1. จำนวนสินค้าทั้งหมด
$total_products_result = $conn->query("SELECT COUNT(id) as total FROM product_list");
$total_products = $total_products_result->fetch_assoc()['total'];

// 2. ยอดรับเข้า-เบิกออกรวมในช่วงวันที่เลือก
$kpi_query = "SELECT 
    SUM(CASE WHEN type = 'D' THEN quantity ELSE 0 END) as total_deposit,
    SUM(CASE WHEN type = 'W' THEN quantity ELSE 0 END) as total_withdraw
    FROM products WHERE date BETWEEN ? AND ?";
$stmt_kpi = $conn->prepare($kpi_query);
$stmt_kpi->bind_param($types, ...$params);
$stmt_kpi->execute();
$kpi_data = $stmt_kpi->get_result()->fetch_assoc();
$total_deposit = $kpi_data['total_deposit'] ?? 0;
$total_withdraw = $kpi_data['total_withdraw'] ?? 0;
$stmt_kpi->close();


// --- ดึงข้อมูลสำหรับตารางสรุปและกราฟ ---
$report_query = "
    SELECT 
        pl.product_name,
        COALESCE(SUM(CASE WHEN p.type = 'D' AND p.date BETWEEN ? AND ? THEN p.quantity ELSE 0 END), 0) as period_deposit,
        COALESCE(SUM(CASE WHEN p.type = 'W' AND p.date BETWEEN ? AND ? THEN p.quantity ELSE 0 END), 0) as period_withdraw,
        (SELECT SUM(CASE WHEN type = 'D' THEN quantity ELSE -quantity END) FROM products WHERE product_id = pl.id) as current_stock,
        pl.low
    FROM product_list pl
    LEFT JOIN products p ON pl.id = p.product_id
    GROUP BY pl.id, pl.product_name, pl.low
    ORDER BY pl.product_name ASC
";

$stmt_report = $conn->prepare($report_query);
// ต้อง bind param 2 ครั้งสำหรับ 2 ตำแหน่งใน query
$report_params = [$start_date, $end_date, $start_date, $end_date]; 
$stmt_report->bind_param('ssss', ...$report_params);
$stmt_report->execute();
$report_data = $stmt_report->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_report->close();


// --- จัดการข้อมูลสำหรับส่งให้ Chart.js ---
$chart_labels = [];
$chart_deposit_data = [];
$chart_withdraw_data = [];
$low_stock_count = 0;
$top_withdrawn = $report_data; // copy array
$lowest_stock = $report_data; // copy array

foreach ($report_data as $item) {
    // สำหรับกราฟแท่ง
    $chart_labels[] = $item['product_name'];
    $chart_deposit_data[] = $item['period_deposit'];
    $chart_withdraw_data[] = $item['period_withdraw'];

    // สำหรับ KPI สินค้าใกล้หมด
    if ($item['current_stock'] < $item['low']) {
        $low_stock_count++;
    }
}

// --- จัดอันดับ Top 5 ---
// สินค้าเบิกออกสูงสุด
usort($top_withdrawn, fn($a, $b) => $b['period_withdraw'] <=> $a['period_withdraw']);
$top_withdrawn = array_slice($top_withdrawn, 0, 5);

// สินค้าคงเหลือต่ำสุด
usort($lowest_stock, fn($a, $b) => $a['current_stock'] <=> $b['current_stock']);
$lowest_stock = array_slice($lowest_stock, 0, 5);


$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานและภาพรวม</title>
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .kpi-card { text-align: center; }
        .kpi-card .card-body { font-size: 1.2rem; }
        .kpi-card .card-title { font-size: 2.5rem; font-weight: bold; }
        .kpi-icon { font-size: 3rem; opacity: 0.3; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header & Date Filter -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-chart-pie"></i> รายงานและภาพรวมข้อมูล</h3>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="report.php" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">สร้างรายงาน</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 1: Key Metrics (KPIs) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card kpi-card bg-primary text-white h-100">
                    <div class="card-body">
                        <i class="fas fa-boxes-stacked kpi-icon"></i>
                        <h5 class="card-subtitle mb-2">สินค้าทั้งหมด</h5>
                        <p class="card-title"><?= $total_products ?></p>
                    </div>
                </div>
            </div>
             <div class="col-md-3">
                <div class="card kpi-card bg-danger text-white h-100">
                    <div class="card-body">
                         <i class="fas fa-exclamation-triangle kpi-icon"></i>
                        <h5 class="card-subtitle mb-2">สินค้าใกล้หมดสต็อก</h5>
                        <p class="card-title"><?= $low_stock_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card bg-success text-white h-100">
                    <div class="card-body">
                         <i class="fas fa-arrow-down kpi-icon"></i>
                        <h5 class="card-subtitle mb-2">ยอดรับเข้ารวม</h5>
                        <p class="card-title"><?= number_format($total_deposit) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card bg-warning text-dark h-100">
                    <div class="card-body">
                         <i class="fas fa-arrow-up kpi-icon"></i>
                        <h5 class="card-subtitle mb-2">ยอดเบิกออกรวม</h5>
                        <p class="card-title"><?= number_format($total_withdraw) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">สรุปการเคลื่อนไหวสต็อก (ช่วงวันที่ <?= htmlspecialchars($start_date) ?> ถึง <?= htmlspecialchars($end_date) ?>)</h5>
            </div>
            <div class="card-body">
                <canvas id="movementChart"></canvas>
            </div>
        </div>

        <!-- Section 3: Top Lists -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">5 อันดับสินค้าที่เบิกออกสูงสุด</h5></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($top_withdrawn as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                    <span class="badge bg-warning rounded-pill"><?= number_format($item['period_withdraw']) ?> ชิ้น</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
             <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">5 อันดับสินค้าคงเหลือน้อยสุด</h5></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                           <?php foreach ($lowest_stock as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                    <span class="badge bg-danger rounded-pill"><?= number_format($item['current_stock']) ?> ชิ้น</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Detailed Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">ตารางสรุปข้อมูลรายสินค้า</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ชื่อสินค้า</th>
                                <th>ยอดรับเข้า (ช่วงที่เลือก)</th>
                                <th>ยอดเบิกออก (ช่วงที่เลือก)</th>
                                <th>เปลี่ยนแปลงสุทธิ</th>
                                <th>ยอดคงเหลือปัจจุบัน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($report_data as $item): 
                                $net_change = $item['period_deposit'] - $item['period_withdraw'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="text-success">+<?= number_format($item['period_deposit']) ?></td>
                                <td class="text-warning">-<?= number_format($item['period_withdraw']) ?></td>
                                <td>
                                    <span class="fw-bold <?= $net_change >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $net_change >= 0 ? '+' : '' ?><?= number_format($net_change) ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= number_format($item['current_stock']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('movementChart').getContext('2d');
            
            // ข้อมูลจาก PHP
            const labels = <?= json_encode($chart_labels) ?>;
            const depositData = <?= json_encode($chart_deposit_data) ?>;
            const withdrawData = <?= json_encode($chart_withdraw_data) ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'ยอดรับเข้า',
                            data: depositData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'ยอดเบิกออก',
                            data: withdrawData,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false,
                            text: 'สรุปการเคลื่อนไหวสต็อก'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
