<?php
// api.php
header('Content-Type: application/json'); // กำหนดให้ Response เป็น JSON เสมอ
include 'db_config.php';

// --- ฟังก์ชัน sendLineNotify() และส่วนที่เกี่ยวข้องกับ LINE ได้ถูกลบออกไปแล้ว ---

// ตรวจสอบว่าเป็นการร้องขอแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ตรวจสอบว่ามีการส่ง action มาหรือไม่
$action = $_POST['action'] ?? '';
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad Request: Action is required.']);
    exit;
}

// ใช้ switch-case เพื่อจัดการ action ต่างๆ
switch ($action) {
    // กรณี: เพิ่มสินค้าใหม่
    case 'addProduct':
        $product_name = trim($_POST['productName'] ?? '');
        $low_quantity = (int)($_POST['lowQuantity'] ?? 0);
        $focus_quantity = (int)($_POST['focusQuantity'] ?? 0);
        $initial_quantity = (int)($_POST['initialQuantity'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');

        if (empty($product_name) || $initial_quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }

        // ตรวจสอบชื่อสินค้าซ้ำ
        $stmt_check = $conn->prepare("SELECT id FROM product_list WHERE product_name = ?");
        $stmt_check->bind_param("s", $product_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'มีสินค้านี้ในระบบแล้ว']);
            $stmt_check->close();
            exit;
        }
        $stmt_check->close();

        // สร้าง Product ID
        $product_id = date('dmyHis');

        // เริ่ม Transaction
        $conn->begin_transaction();

        try {
            // 1. เพิ่มใน product_list
            $stmt1 = $conn->prepare("INSERT INTO product_list (id, product_name, low, focus) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("ssii", $product_id, $product_name, $low_quantity, $focus_quantity);
            $stmt1->execute();
            $stmt1->close();

            // 2. เพิ่มใน products (เป็นการรับเข้าครั้งแรก)
            $stmt2 = $conn->prepare("INSERT INTO products (product_id, date, type, quantity) VALUES (?, ?, 'D', ?)");
            $stmt2->bind_param("ssi", $product_id, $date, $initial_quantity);
            $stmt2->execute();
            $stmt2->close();

            // Commit Transaction
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'เพิ่มสินค้าสำเร็จ']);
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $exception->getMessage()]);
        }
        break;

    // กรณี: เพิ่มการเคลื่อนไหว (รับเข้า/เบิกออก)
    case 'addTransaction':
        $product_id = $_POST['productId'] ?? '';
        $type = $_POST['type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if (empty($product_id) || empty($type) || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO products (product_id, date, type, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $product_id, $date, $type, $quantity);

        if ($stmt->execute()) {
            // ส่วนของการตรวจสอบสต็อกและแจ้งเตือน Line ได้ถูกลบออกไปแล้ว
            echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'บันทึกข้อมูลไม่สำเร็จ']);
        }
        $stmt->close();
        break;

    // กรณี: อัปเดตข้อมูลสินค้า (low, focus)
    case 'updateProductDetail':
        $product_id = $_POST['productId'] ?? '';
        $field = $_POST['field'] ?? '';
        $value = (int)($_POST['value'] ?? 0);

        if (empty($product_id) || !in_array($field, ['low', 'focus']) || $value < 0) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE product_list SET {$field} = ? WHERE id = ?");
        $stmt->bind_param("is", $value, $product_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'อัปเดตไม่สำเร็จ']);
        }
        $stmt->close();
        break;
    
    // กรณี: ลบสินค้า (ทั้งรายการและประวัติ)
    case 'deleteProduct':
        $product_id = $_POST['productId'] ?? '';

        if (empty($product_id)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสสินค้า']);
            exit;
        }

        // การตั้งค่า ON DELETE CASCADE ใน SQL จะทำให้ข้อมูลใน `products` ถูกลบไปด้วย
        $stmt = $conn->prepare("DELETE FROM product_list WHERE id = ?");
        $stmt->bind_param("s", $product_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ลบสินค้าสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ลบสินค้าไม่สำเร็จ']);
        }
        $stmt->close();
        break;

    // กรณี: อัปเดตรายการในหน้าประวัติ
    case 'updateHistoryRecord':
        $transaction_id = (int)($_POST['transactionId'] ?? 0);
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';

        if ($transaction_id <= 0 || !in_array($field, ['date', 'type', 'quantity', 'note'])) {
             echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
             exit;
        }

        // Validate value type
        if ($field === 'quantity' && (!is_numeric($value) || (int)$value <= 0)) {
            echo json_encode(['success' => false, 'message' => 'จำนวนต้องเป็นตัวเลขมากกว่า 0']);
            exit;
        }
        if ($field === 'type' && !in_array($value, ['D', 'W'])) {
            echo json_encode(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE products SET {$field} = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $transaction_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตประวัติสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'อัปเดตประวัติไม่สำเร็จ']);
        }
        $stmt->close();
        break;
        
    // กรณี: ลบรายการในหน้าประวัติ
    case 'deleteHistoryRecord':
        $transaction_id = (int)($_POST['transactionId'] ?? 0);

        if ($transaction_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ของรายการ']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $transaction_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ลบรายการสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ลบรายการไม่สำเร็จ']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bad Request: Invalid action.']);
        break;
}

$conn->close();
?>
