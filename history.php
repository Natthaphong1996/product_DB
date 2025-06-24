<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติสินค้าคงคลัง</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .nav-tabs .nav-link.active {
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
            <h1>ประวัติสินค้าคงคลัง</h1>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">กลับไปหน้าหลัก</button>
        </div>

        <!-- ฟอร์มสำหรับค้นหาข้อมูล -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>ค้นหาประวัติสินค้า</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="history.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" placeholder="วันที่เริ่มต้น" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" placeholder="วันที่สิ้นสุด" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="product_name" class="form-label">ชื่อสินค้า:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="ชื่อสินค้า" value="<?php echo $_GET['product_name'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="product_id" class="form-label">รหัสสินค้า:</label>
                        <input type="text" class="form-control" id="product_id" name="product_id" placeholder="รหัสสินค้า" value="<?php echo $_GET['product_id'] ?? ''; ?>">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        include 'db_config.php';

        $itemsPerPage = 10;

        // รับค่าการค้นหาจากฟอร์ม
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $product_name = $_GET['product_name'] ?? '';
        $product_id = $_GET['product_id'] ?? '';

        // สร้างเงื่อนไขการค้นหา
        $searchConditions = [];
        if ($start_date) {
            $searchConditions[] = "p.date >= '" . $conn->real_escape_string($start_date) . "'";
        }
        if ($end_date) {
            $searchConditions[] = "p.date <= '" . $conn->real_escape_string($end_date) . "'";
        }
        if ($product_name) {
            $searchConditions[] = "pl.product_name LIKE '%" . $conn->real_escape_string($product_name) . "%'";
        }
        if ($product_id) {
            $searchConditions[] = "p.product_id LIKE '%" . $conn->real_escape_string($product_id) . "%'";
        }

        // รวมเงื่อนไขการค้นหา
        $searchQuery = '';
        if (count($searchConditions) > 0) {
            $searchQuery = 'WHERE ' . implode(' AND ', $searchConditions);
        }

        // ดึงจำนวนรายการทั้งหมดตามเงื่อนไขการค้นหา
        $sql_count = "SELECT COUNT(*) as total FROM products p INNER JOIN product_list pl ON p.product_id = pl.id $searchQuery";
        $result_count = $conn->query($sql_count);
        $row_count = $result_count->fetch_assoc();
        $totalItems = $row_count['total'];
        $totalPages = ceil($totalItems / $itemsPerPage);

        // ดึงข้อมูลสำหรับแต่ละหน้า
        echo '<ul class="nav nav-tabs" id="myTab" role="tablist">';
        for ($page = 1; $page <= $totalPages; $page++) {
            $activeClass = $page === 1 ? 'active' : '';
            echo "<li class='nav-item' role='presentation'>
                    <button class='nav-link $activeClass' id='page-$page-tab' data-bs-toggle='tab' data-bs-target='#page-$page' type='button' role='tab' aria-controls='page-$page' aria-selected='true'>หน้า $page</button>
                  </li>";
        }
        echo '</ul>';

        echo '<div class="tab-content" id="myTabContent">';
        for ($page = 1; $page <= $totalPages; $page++) {
            $activeClass = $page === 1 ? 'show active' : '';
            $offset = ($page - 1) * $itemsPerPage;
            $sql = "
                SELECT p.id, p.date, p.type, p.product_id, pl.product_name as product, p.quantity, p.note 
                FROM products p 
                INNER JOIN product_list pl ON p.product_id = pl.id 
                $searchQuery 
                ORDER BY p.id DESC 
                LIMIT $itemsPerPage OFFSET $offset
            ";

            $result = $conn->query($sql);

            echo "<div class='tab-pane fade $activeClass' id='page-$page' role='tabpanel' aria-labelledby='page-$page-tab'>";
            echo '<table class="table table-bordered table-hover mt-3">';
            echo '<thead class="table-dark"><tr><th>ID</th><th>วันที่</th><th>ประเภท</th><th>รหัสสินค้า</th><th>ชื่อสินค้า</th><th>จำนวน</th><th>ข้อความ</th><th>หมายเหตุ</th></tr></thead>';
            echo '<tbody>';
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row["id"]) . "</td>
                            <td>" . htmlspecialchars($row["date"]) . "</td>
                            <td>" . htmlspecialchars($row["type"]) . "</td>
                            <td>" . htmlspecialchars($row["product_id"]) . "</td>
                            <td>" . htmlspecialchars($row["product"]) . "</td>
                            <td>" . htmlspecialchars($row["quantity"]) . "</td>
                            <td>" . htmlspecialchars($row["note"]) . " </td>
                            <td><button class='btn btn-sm btn-info' onclick='openNoteModal(" . $row["id"] . ")'>หมายเหตุ</button></td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>ไม่มีข้อมูล</td></tr>";
            }
            echo '</tbody></table>';
            echo '</div>';
        }
        echo '</div>';

        // ปิดการเชื่อมต่อ
        $conn->close();
        ?>

        <!-- Modal สำหรับกรอกหมายเหตุ -->
        <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="noteModalLabel">หมายเหตุ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="noteForm">
                            <input type="hidden" id="noteProductId" name="product_id">
                            <div class="mb-3">
                                <label for="noteText" class="form-label">หมายเหตุ:</label>
                                <textarea class="form-control" id="noteText" name="note" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function openNoteModal(productId) {
            document.getElementById('noteProductId').value = productId; // กำหนดรหัสสินค้าในฟอร์ม

            // ดึงข้อมูลหมายเหตุจากฐานข้อมูล
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_note.php?product_id=' + productId, true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('noteText').value = response.note; // แสดงข้อความใน note
                    } else {
                        document.getElementById('noteText').value = ''; // กรณีไม่มีข้อความ
                    }

                    // แสดง modal หลังจากดึงข้อมูลเรียบร้อย
                    var noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
                    noteModal.show();
                }
            };

            xhr.send();
        }

        // เมื่อกดบันทึก จะส่งข้อมูลไปยัง update_note.php เพื่ออัปเดตหมายเหตุ
        document.getElementById('noteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var productId = document.getElementById('noteProductId').value;
            var note = document.getElementById('noteText').value;

            // ส่งข้อมูลด้วย Ajax
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_note.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        window.location.reload(); // รีเฟรชหน้าเว็บ
                        alert('อัปเดตหมายเหตุเรียบร้อย');
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }

                    // ปิด modal หลังจากบันทึกสำเร็จ
                    var noteModal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
                    noteModal.hide();
                }
            };

            // ส่งข้อมูลไปยัง update_note.php
            xhr.send('product_id=' + productId + '&note=' + encodeURIComponent(note));
        });
    </script>
</body>

</html>
