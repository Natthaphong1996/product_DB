<?php
include 'db_config.php';
include 'functions.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการข้อมูลสินค้า</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .quantity-high {
            background-color: #d4edda;
        }
        .quantity-medium {
            background-color: #fff3cd;
        }
        .quantity-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .navbar {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .card {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <a class="navbar-brand" href="#">การจัดการสินค้า</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i> เพิ่มสินค้าใหม่
                        </button>
                    </li>
                    <li class="nav-item ms-2">
                        <button class="btn btn-info" onclick="window.location.href='history.php'">
                            <i class="fas fa-history"></i> ประวัติ
                        </button>
                    </li>
                    <li class="nav-item ms-2">
                        <button class="btn btn-secondary" onclick="window.location.href='report.php'">
                            <i class="fas fa-chart-line"></i> รายงาน
                        </button>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Alert Message -->
        <?php echo getAlertMessage(); ?>

        <!-- Search Bar -->
        <div class="card">
            <div class="card-header">
                <h4>ค้นหาสินค้า</h4>
            </div>
            <div class="card-body">
                <div class="input-group mb-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา...">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Product Quantity by Type -->
        <div class="card">
            <div class="card-header">
                <h4>ปริมาณสินค้าคงเหลือ</h4>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover" id="productTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>จำนวนต่ำสุด</th>
                            <th>จำนวนที่ควรโฟกัส</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo getProductQuantityList(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal for Product Data -->
        <div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dataModalLabel">กรอกข้อมูลสินค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="dataForm" action="save.php" method="post">
                            <div class="form-group">
                                <label for="date">วันที่:</label>
                                <input type="date" id="date" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="product">สินค้า:</label>
                                <input type="text" id="product" name="product" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="quantity">จำนวน:</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" required>
                            </div>
                            <input type="hidden" id="type" name="type">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-primary" onclick="submitForm()">บันทึก</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Adding New Product -->
        <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductModalLabel">เพิ่มสินค้าใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addProductForm" action="add_product.php" method="post">
                            <div class="form-group">
                                <label for="newProductName">ชื่อสินค้า:</label>
                                <input type="text" id="newProductName" name="newProductName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="low">จำนวนต่ำสุด:</label>
                                <input type="number" id="low" name="low" class="form-control" required>
                                <small class="form-text text-muted">จำนวนต่ำสุด คือจำนวนที่กำหนดไว้เพื่อแจ้งเตือนเมื่อสินค้าใกล้หมด</small>
                            </div>
                            <div class="form-group">
                                <label for="focus">จำนวนที่ควรโฟกัส:</label>
                                <input type="number" id="focus" name="focus" class="form-control" required>
                                <small class="form-text text-muted">จำนวนที่ควรโฟกัส ใช้เพื่อคำนวณเปอร์เซ็นต์ของจำนวนคงเหลือ</small>
                            </div>
                            <div class="form-group">
                                <label for="date">วันที่:</label>
                                <input type="date" id="newProductDate" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="quantity">จำนวน:</label>
                                <input type="number" id="newProductQuantity" name="quantity" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-primary" onclick="submitAddProductForm()">เพิ่มสินค้า</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Deleting Product -->
        <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteProductModalLabel">ลบสินค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="deleteProductForm" action="delete_product.php" method="post">
                            <div class="form-group">
                                <label for="deleteProductName">ชื่อสินค้า:</label>
                                <input type="text" id="deleteProductName" name="deleteProductName" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="confirmDelete">พิมพ์คำว่า "ลบ" เพื่อยืนยัน:</label>
                                <input type="text" id="confirmDelete" name="confirmDelete" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-danger" onclick="submitDeleteProductForm()">ลบสินค้า</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Editing Product -->
        <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProductModalLabel">แก้ไขข้อมูลสินค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProductForm" action="edit_product.php" method="post">
                            <div class="form-group">
                                <label for="editProductName">ชื่อสินค้า:</label>
                                <input type="text" id="editProductName" name="editProductName" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="editLowQuantity">จำนวนต่ำสุด:</label>
                                <input type="number" id="editLowQuantity" name="editLowQuantity" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="editFocusQuantity">จำนวนที่ควรโฟกัส:</label>
                                <input type="number" id="editFocusQuantity" name="editFocusQuantity" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmEdit">พิมพ์คำว่า "แก้ไข" เพื่อยืนยัน:</label>
                                <input type="text" id="confirmEdit" name="confirmEdit" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="button" class="btn btn-primary" onclick="submitEditProductForm()">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <!-- FontAwesome JS for Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
        $(document).ready(function() {
            // ตั้งค่าวันที่เริ่มต้นเป็นวันนี้
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;
            document.getElementById('newProductDate').value = today;

            // กรองตารางตามค่าที่ค้นหา
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#productTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        function setTypeAndShowModal(type, product) {
            document.getElementById('type').value = type;
            document.getElementById('product').value = product;
            $('#dataModal').modal('show');
        }

        function setDeleteAndShowModal(product) {
            document.getElementById('deleteProductName').value = product;
            $('#deleteProductModal').modal('show');
        }

        function setEditAndShowModal(product, low, focus) {
            document.getElementById('editProductName').value = product;
            document.getElementById('editLowQuantity').value = low;
            document.getElementById('editFocusQuantity').value = focus;
            $('#editProductModal').modal('show');
        }

        function submitForm() {
            document.getElementById('dataForm').submit();
        }

        function submitAddProductForm() {
            document.getElementById('addProductForm').submit();
        }

        function submitDeleteProductForm() {
            var confirmText = document.getElementById('confirmDelete').value;
            if (confirmText.toLowerCase() === 'ลบ') {
                document.getElementById('deleteProductForm').submit();
            } else {
                alert('กรุณาพิมพ์คำว่า "ลบ" เพื่อยืนยันการลบ');
            }
        }

        function submitEditProductForm() {
            var confirmText = document.getElementById('confirmEdit').value;
            if (confirmText.toLowerCase() === 'แก้ไข') {
                document.getElementById('editProductForm').submit();
            } else {
                alert('กรุณาพิมพ์คำว่า "แก้ไข" เพื่อยืนยันการแก้ไข');
            }
        }

        // ปิด Alert Message อัตโนมัติหลังจาก 7 วินาที
        $(document).ready(function() {
            setTimeout(function() {
                $("#alertMessage").alert('close');
            }, 7000);
        });
    </script>
</body>
</html>
