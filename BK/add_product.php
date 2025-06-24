<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตั้งค่า timezone เป็นเวลาประเทศไทย
    date_default_timezone_set('Asia/Bangkok');

    // สร้าง product_id ในรูปแบบ ddmmyyhhmmss โดยอ้างอิงเวลาประเทศไทย
    $product_id = date('dmyHis');

    $product_name = $_POST['newProductName'];
    $low_quantity = $_POST['low'];
    $focus_quantity = $_POST['focus'];
    $date = $_POST['date'];
    $quantity = $_POST['quantity'];

    // ตรวจสอบว่า Product ID หรือชื่อสินค้าซ้ำกันหรือไม่
    $sql_check = "SELECT COUNT(*) FROM product_list WHERE id = ? OR product_name = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $product_id, $product_name);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        // หากมี Product ID หรือชื่อสินค้าซ้ำ ให้แสดงข้อความแจ้งเตือนและไม่เพิ่มข้อมูล
        header("Location: index.php?message=Product ID or Name already exists&alertType=danger");
    } else {
        // เพิ่มข้อมูลในตาราง product_list โดยใช้ product_id ที่สร้างขึ้น
        $sql_product_list = "INSERT INTO product_list (id, product_name, low, focus) VALUES (?, ?, ?, ?)";
        $stmt_product_list = $conn->prepare($sql_product_list);
        $stmt_product_list->bind_param("ssii", $product_id, $product_name, $low_quantity, $focus_quantity);

        // เพิ่มข้อมูลในตาราง products โดยใช้ product_id ที่สร้างขึ้น
        $sql_products = "INSERT INTO products (date, product_id, quantity, type) VALUES (?, ?, ?, 'D')";
        $stmt_products = $conn->prepare($sql_products);
        $stmt_products->bind_param("ssi", $date, $product_id, $quantity); // Bind แค่ 3 ตัวแปร

        if ($stmt_product_list->execute() && $stmt_products->execute()) {
            header("Location: index.php?message=Product added successfully&alertType=success");
        } else {
            header("Location: index.php?message=Error adding product&alertType=danger");
        }

        $stmt_product_list->close();
        $stmt_products->close();
    }

    $conn->close();
}
?>
