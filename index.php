<?php
include 'db_config.php';

// ฟังก์ชันสำหรับดึงข้อมูลสินค้าทั้งหมดพร้อมจำนวนคงเหลือ
function getProductData($conn) {
    $sql = "
        SELECT 
            pl.id, 
            pl.product_name, 
            pl.low, 
            pl.focus,
            (SELECT SUM(CASE WHEN p.type = 'D' THEN p.quantity ELSE -p.quantity END) 
             FROM products p WHERE p.product_id = pl.id) AS remaining_quantity
        FROM product_list pl
        ORDER BY pl.product_name ASC
    ";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

$products = getProductData($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสต็อกสินค้า</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Toastify CSS for Notifications -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #343a40; color: white; }
        .editable-cell { cursor: pointer; }
        .editable-cell:hover { background-color: #e9ecef; }
        .action-icon { cursor: pointer; margin: 0 5px; }
        .table-responsive { max-height: 70vh; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fa-solid fa-boxes-stacked"></i> ระบบจัดการสต็อกสินค้า</h3>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus"></i> เพิ่มสินค้าใหม่</button>
                <a href="history.php" class="btn btn-info"><i class="fas fa-history"></i> ดูประวัติ</a>
                <a href="report.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> ดูรายงาน</a>
            </div>
        </div>

        <!-- Search and Table -->
        <div class="card">
            <div class="card-header">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาด้วยชื่อสินค้า หรือ รหัสสินค้า...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="productTable">
                        <thead class="table-dark" style="position: sticky; top: 0;">
                            <tr>
                                <th>#</th>
                                <th>รหัสสินค้า</th>
                                <th>ชื่อสินค้า</th>
                                <th>คงเหลือ</th>
                                <th>เกณฑ์แจ้งเตือน (Low)</th>
                                <th>จำนวนอ้างอิง (Focus)</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="7" class="text-center p-5">ยังไม่มีสินค้าในระบบ</td></tr>
                            <?php else: ?>
                                <?php foreach ($products as $index => $p): 
                                    $remaining = $p['remaining_quantity'] ?? 0;
                                    $focus = $p['focus'] ?? 0;
                                    $percentage = ($focus > 0) ? ($remaining / $focus) * 100 : 0;
                                    
                                    $bgColor = 'white';
                                    if ($remaining < $p['low']) {
                                        $bgColor = 'rgba(248, 215, 218, 0.7)'; // Low quantity
                                    } else if ($focus > 0) {
                                        $green = min(255, 150 + (105 * ($percentage / 100)));
                                        $red = 255;
                                        if ($percentage > 50) {
                                            $red = 255 - (200 * (($percentage - 50) / 50));
                                        }
                                        $bgColor = "rgba($red, $green, 150, 0.5)";
                                    }
                                ?>
                                <tr style="background-color: <?= $bgColor ?>;">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($p['id']) ?></td>
                                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><strong><?= $remaining ?></strong></td>
                                    <td class="editable-cell" data-field="low" data-id="<?= $p['id'] ?>"><?= $p['low'] ?></td>
                                    <td class="editable-cell" data-field="focus" data-id="<?= $p['id'] ?>"><?= $p['focus'] ?></td>
                                    <td class="text-center">
                                        <i class="fas fa-plus-circle text-success action-icon" title="รับเข้า" onclick="showTransactionModal('D', '<?= $p['id'] ?>', '<?= htmlspecialchars($p['product_name']) ?>')"></i>
                                        <i class="fas fa-minus-circle text-warning action-icon" title="เบิกออก" onclick="showTransactionModal('W', '<?= $p['id'] ?>', '<?= htmlspecialchars($p['product_name']) ?>')"></i>
                                        <i class="fas fa-trash-alt text-danger action-icon" title="ลบสินค้า" onclick="deleteProduct('<?= $p['id'] ?>', '<?= htmlspecialchars($p['product_name']) ?>')"></i>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">เพิ่มสินค้าใหม่</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="addProductForm">
              <div class="mb-3">
                <label class="form-label">ชื่อสินค้า <span class="text-danger">*</span></label>
                <input type="text" name="productName" class="form-control" required>
              </div>
              <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">จำนวนเริ่มต้น <span class="text-danger">*</span></label>
                    <input type="number" name="initialQuantity" class="form-control" min="1" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                  </div>
              </div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">เกณฑ์แจ้งเตือน (Low)</label>
                    <input type="number" name="lowQuantity" class="form-control" value="0" min="0">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">จำนวนอ้างอิง (Focus)</label>
                    <input type="number" name="focusQuantity" class="form-control" value="0" min="0">
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="submitAddProduct()">บันทึก</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Transaction Modal (Deposit/Withdraw) -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="transactionModalTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="transactionForm">
                <input type="hidden" name="productId">
                <input type="hidden" name="type">
                <p><strong>สินค้า:</strong> <span id="transactionProductName"></span></p>
                <div class="mb-3">
                    <label class="form-label">จำนวน <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                 <div class="mb-3">
                    <label class="form-label">วันที่ <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="submitTransaction()">ยืนยัน</button>
          </div>
        </div>
      </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Utility for API calls
        async function apiCall(action, formData) {
            formData.append('action', action);
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('API Call Error:', error);
                return { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
            }
        }

        // Utility for notifications
        function showToast(message, isSuccess = true) {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: isSuccess ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ff5f6d, #ffc371)",
            }).showToast();
        }

        // Add New Product
        async function submitAddProduct() {
            const form = document.getElementById('addProductForm');
            const formData = new FormData(form);
            const result = await apiCall('addProduct', formData);
            if (result.success) {
                showToast(result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message, false);
            }
        }

        // Transaction Modal Logic
        const transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));
        function showTransactionModal(type, id, name) {
            const form = document.getElementById('transactionForm');
            form.reset();
            form.querySelector('[name="productId"]').value = id;
            form.querySelector('[name="type"]').value = type;
            document.getElementById('transactionProductName').textContent = name;
            const title = document.getElementById('transactionModalTitle');
            if (type === 'D') {
                title.innerHTML = '<i class="fas fa-plus-circle text-success"></i> รับสินค้าเข้าคลัง';
            } else {
                title.innerHTML = '<i class="fas fa-minus-circle text-warning"></i> เบิกสินค้าใช้งาน';
            }
            transactionModal.show();
        }

        async function submitTransaction() {
            const form = document.getElementById('transactionForm');
            const formData = new FormData(form);
             const result = await apiCall('addTransaction', formData);
             if (result.success) {
                showToast(result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message, false);
            }
        }

        // Delete Product
        function deleteProduct(id, name) {
            if (confirm(`คุณต้องการลบสินค้า '${name}' ใช่หรือไม่?\nการกระทำนี้จะลบประวัติทั้งหมดของสินค้านี้ด้วย`)) {
                (async () => {
                    const formData = new FormData();
                    formData.append('productId', id);
                    const result = await apiCall('deleteProduct', formData);
                    if (result.success) {
                        showToast(result.message);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast(result.message, false);
                    }
                })();
            }
        }

        // Live Search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productTable tbody tr');
            rows.forEach(row => {
                if (row.textContent.toLowerCase().includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Inline Editing
        document.querySelectorAll('.editable-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                if (this.querySelector('input')) return; // Prevent re-clicking

                const originalValue = this.textContent;
                const field = this.dataset.field;
                const id = this.dataset.id;

                this.innerHTML = `<input type="number" class="form-control form-control-sm" value="${originalValue}" min="0">`;
                const input = this.querySelector('input');
                input.focus();

                input.addEventListener('blur', () => handleEdit(this, id, field, input.value, originalValue));
                input.addEventListener('keydown', e => {
                    if (e.key === 'Enter') input.blur();
                    if (e.key === 'Escape') {
                        this.textContent = originalValue;
                    }
                });
            });
        });

        async function handleEdit(cell, id, field, newValue, originalValue) {
            if (newValue === originalValue) {
                cell.textContent = originalValue;
                return;
            }

            const formData = new FormData();
            formData.append('productId', id);
            formData.append('field', field);
            formData.append('value', newValue);

            const result = await apiCall('updateProductDetail', formData);

            if (result.success) {
                cell.textContent = newValue;
                showToast('อัปเดตข้อมูลสำเร็จ');
            } else {
                cell.textContent = originalValue;
                showToast(result.message || 'อัปเดตไม่สำเร็จ', false);
            }
        }
    </script>
</body>
</html>
