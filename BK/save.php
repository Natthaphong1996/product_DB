<?php
include 'db_config.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $product_name = $_POST['product'];  // รับชื่อสินค้า
    $quantity = $_POST['quantity'];
    $type = $_POST['type'];

    // ดึง product_id จาก product_list
    $sql_get_id = "SELECT id FROM product_list WHERE product_name = ?";
    $stmt_get_id = $conn->prepare($sql_get_id);
    $stmt_get_id->bind_param("s", $product_name);
    $stmt_get_id->execute();
    $stmt_get_id->bind_result($product_id);
    $stmt_get_id->fetch();
    $stmt_get_id->close();

    if ($product_id) {
        // เพิ่มข้อมูลในตาราง products โดยใช้ product_id
        $sql = "INSERT INTO products (date, product_id, quantity, type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $date, $product_id, $quantity, $type);

        if ($stmt->execute()) {
            // คำนวณจำนวนคงเหลือใหม่
            $sql_remaining = "
                SELECT p.product_name as product, 
                       SUM(CASE WHEN d.type = 'D' THEN d.quantity ELSE 0 END) - 
                       SUM(CASE WHEN d.type = 'W' THEN d.quantity ELSE 0 END) AS remaining_quantity,
                       p.low
                FROM products d 
                INNER JOIN product_list p ON d.product_id = p.id
                WHERE p.product_name = ?
                GROUP BY p.product_name, p.low
            ";
            $stmt_remaining = $conn->prepare($sql_remaining);
            $stmt_remaining->bind_param("s", $product_name);
            $stmt_remaining->execute();
            $result_remaining = $stmt_remaining->get_result();

            if ($result_remaining->num_rows > 0) {
                $row = $result_remaining->fetch_assoc();
                $remaining_quantity = $row["remaining_quantity"];
                $low_quantity = $row["low"];

                // ตรวจสอบว่าจำนวนคงเหลือน้อยกว่า Low Quantity หรือไม่
                if ($remaining_quantity < $low_quantity) {
                    $message = "เหลือ $product_name ในคลังจำนวน $remaining_quantity ชึ่งน้อยกว่า Low Quantity ที่ตั้งไว้($low_quantity)";
                    $response = sendLineNotify($message);
                    if ($response === false) {
                        error_log("Failed to send Line Notify");
                    } else {
                        error_log("Line Notify Response: " . $response);
                    }
                }
            }
            $stmt_remaining->close();

            header("Location: index.php?message=Data saved successfully&alertType=success");
        } else {
            header("Location: index.php?message=Error saving data&alertType=danger");
        }

        $stmt->close();
    } else {
        header("Location: index.php?message=Product not found&alertType=danger");
    }

    $conn->close();
}
?>
