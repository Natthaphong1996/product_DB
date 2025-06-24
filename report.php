<?php
include 'db_config.php';

function getDashboardData($start_date, $end_date) {
    global $conn;

    // ดึงข้อมูลการเบิก-จ่ายตามช่วงวันที่ที่เลือก
    $sql = "
        SELECT 
            DATE(p.date) as date,
            pl.product_name AS item_name,
            SUM(CASE WHEN p.type = 'D' THEN p.quantity ELSE 0 END) AS total_in,
            SUM(CASE WHEN p.type = 'W' THEN p.quantity ELSE 0 END) AS total_out
        FROM products p
        JOIN product_list pl ON p.product_id = pl.id
        WHERE DATE(p.date) BETWEEN ? AND ?
        GROUP BY item_name, DATE(p.date)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    return $data;
}

// รับค่าจากฟอร์มที่ผู้ใช้ส่งมา
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // เริ่มต้นที่วันที่ 1 ของเดือนนี้
$end_date = $_GET['end_date'] ?? date('Y-m-t');      // สิ้นสุดที่วันสุดท้ายของเดือนนี้

$data = getDashboardData($start_date, $end_date);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .table {
            margin-top: 20px;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h1>แดชบอร์ดสินค้า</h1>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">กลับไปหน้าหลัก</button>
        </div>

        <!-- ฟอร์มสำหรับเลือกช่วงวันที่ -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>เลือกช่วงวันที่</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="report.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">สร้างรายงาน</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- แสดงข้อมูลตามช่วงวันที่เลือก -->
        <div class="card">
            <div class="card-header">
                <h4>กิจกรรมจาก <?php echo $start_date; ?> ถึง <?php echo $end_date; ?></h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>วันที่</th>
                            <th>สินค้า</th>
                            <th>ยอดรับเข้า</th>
                            <th>ยอดจ่ายออก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data) > 0): ?>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td><?php echo $row['date']; ?></td>
                                    <td><?php echo $row['item_name']; ?></td>
                                    <td><?php echo $row['total_in']; ?></td>
                                    <td><?php echo $row['total_out']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">ไม่มีข้อมูลในช่วงวันที่ที่เลือก</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS และ dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
