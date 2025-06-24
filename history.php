<?php
include 'db_config.php';

// ดึงรายการสินค้าทั้งหมดสำหรับ Dropdown filter
$product_list_result = $conn->query("SELECT id, product_name FROM product_list ORDER BY product_name");
$product_list = $product_list_result->fetch_all(MYSQLI_ASSOC);

// สร้าง query เริ่มต้น
$query = "
    SELECT p.id, p.date, p.type, p.quantity, p.note, pl.product_name, p.product_id
    FROM products p
    JOIN product_list pl ON p.product_id = pl.id
";
$where_clauses = [];
$params = [];
$types = '';

// จัดการ Filter
if (!empty($_GET['product_id'])) {
    $where_clauses[] = "p.product_id = ?";
    $params[] = $_GET['product_id'];
    $types .= 's';
}
if (!empty($_GET['start_date'])) {
    $where_clauses[] = "p.date >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
}
if (!empty($_GET['end_date'])) {
    $where_clauses[] = "p.date <= ?";
    $params[] = $_GET['end_date'];
    $types .= 's';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// จัดการ Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// นับจำนวนทั้งหมดสำหรับ Pagination
$count_query = str_replace("SELECT p.id, p.date, p.type, p.quantity, p.note, pl.product_name, p.product_id", "SELECT COUNT(p.id)", $query);
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();

// ดึงข้อมูลสำหรับหน้านี้
$query .= " ORDER BY p.date DESC, p.id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $items_per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$history_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการทำรายการ</title>
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .editable-cell { cursor: pointer; }
        .editable-cell:hover { background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-history"></i> ประวัติการทำรายการ</h3>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="history.php" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="product_id" class="form-label">สินค้า</label>
                        <select name="product_id" id="product_id" class="form-select">
                            <option value="">-- ทั้งหมด --</option>
                            <?php foreach ($product_list as $product) {
                                $selected = ($_GET['product_id'] ?? '') == $product['id'] ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($product['id'])."' $selected>".htmlspecialchars($product['product_name'])."</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- History Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>วันที่</th>
                        <th>สินค้า</th>
                        <th>ประเภท</th>
                        <th>จำนวน</th>
                        <th>หมายเหตุ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="history-body">
                    <?php if (empty($history_data)): ?>
                        <tr><td colspan="6" class="text-center p-5">ไม่พบข้อมูล</td></tr>
                    <?php else: ?>
                        <?php foreach($history_data as $row): ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="editable-cell" data-field="date" data-type="date"><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="editable-cell" data-field="type" data-type="select" data-options='{"D":"รับเข้า", "W":"เบิกออก"}'><?= $row['type'] == 'D' ? 'รับเข้า' : 'เบิกออก' ?></td>
                            <td class="editable-cell" data-field="quantity" data-type="number"><?= htmlspecialchars($row['quantity']) ?></td>
                            <td class="editable-cell" data-field="note" data-type="text"><?= htmlspecialchars($row['note']) ?></td>
                            <td class="text-center">
                                <i class="fas fa-trash-alt text-danger" style="cursor:pointer;" onclick="deleteRecord(<?= $row['id'] ?>)"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php
                    $query_params = $_GET;
                    for($i = 1; $i <= $total_pages; $i++):
                        $query_params['page'] = $i;
                ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query($query_params) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // API and Toast utilities (same as index.php)
        async function apiCall(action, formData) {
            formData.append('action', action);
            const response = await fetch('api.php', { method: 'POST', body: formData });
            return await response.json();
        }
        function showToast(message, isSuccess = true) {
            Toastify({ text: message, duration: 3000, gravity: "top", position: "right", backgroundColor: isSuccess ? "#28a745" : "#dc3545" }).showToast();
        }

        // Delete Record
        function deleteRecord(id) {
            if (confirm('คุณต้องการลบรายการนี้ใช่หรือไม่?')) {
                (async () => {
                    const formData = new FormData();
                    formData.append('transactionId', id);
                    const result = await apiCall('deleteHistoryRecord', formData);
                    if (result.success) {
                        showToast(result.message);
                        document.querySelector(`tr[data-id="${id}"]`).remove();
                    } else {
                        showToast(result.message, false);
                    }
                })();
            }
        }
        
        // Inline Editing Logic
        document.querySelectorAll('.editable-cell').forEach(cell => {
            cell.addEventListener('click', function(e) {
                if (this.querySelector('input, select, textarea')) return;

                const originalContent = this.innerHTML;
                const transactionId = this.closest('tr').dataset.id;
                const field = this.dataset.field;
                const type = this.dataset.type;
                let inputElement;

                switch (type) {
                    case 'date':
                        inputElement = `<input type="date" class="form-control form-control-sm" value="${this.textContent}">`;
                        break;
                    case 'number':
                         inputElement = `<input type="number" class="form-control form-control-sm" value="${this.textContent}">`;
                        break;
                    case 'select':
                        const options = JSON.parse(this.dataset.options);
                        let optionsHtml = '';
                        for (const [value, text] of Object.entries(options)) {
                            const selected = this.textContent === text ? 'selected' : '';
                            optionsHtml += `<option value="${value}" ${selected}>${text}</option>`;
                        }
                        inputElement = `<select class="form-select form-select-sm">${optionsHtml}</select>`;
                        break;
                    default: // text
                        inputElement = `<textarea class="form-control form-control-sm">${this.textContent}</textarea>`;
                }

                this.innerHTML = inputElement;
                const input = this.querySelector('input, select, textarea');
                input.focus();

                const handleBlur = () => {
                    const newValue = input.value;
                    let originalValue = originalContent;

                    // For select, we need to compare value not text
                    if(type === 'select'){
                        const selectedOption = input.options[input.selectedIndex];
                        originalValue = selectedOption.text;
                    }

                    if (newValue === this.textContent) {
                        this.innerHTML = originalContent;
                        return;
                    }

                    (async () => {
                        const formData = new FormData();
                        formData.append('transactionId', transactionId);
                        formData.append('field', field);
                        formData.append('value', newValue);

                        const result = await apiCall('updateHistoryRecord', formData);
                        if (result.success) {
                            if (type === 'select') {
                                this.innerHTML = input.options[input.selectedIndex].text;
                            } else {
                                this.textContent = newValue;
                            }
                            showToast('อัปเดตสำเร็จ');
                        } else {
                            this.innerHTML = originalContent;
                            showToast(result.message, false);
                        }
                    })();
                };
                
                input.addEventListener('blur', handleBlur);
                input.addEventListener('keydown', e => {
                    if (e.key === 'Enter' && type !== 'textarea') input.blur();
                    if (e.key === 'Escape') {
                         this.innerHTML = originalContent;
                         input.removeEventListener('blur', handleBlur); // remove listener to prevent saving
                    }
                });
            });
        });
    </script>
</body>
</html>
