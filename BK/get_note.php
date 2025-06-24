<?php
include 'db_config.php';

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // ป้องกัน SQL Injection
    $product_id = $conn->real_escape_string($product_id);

    // ดึงข้อมูลหมายเหตุจากฐานข้อมูล
    $sql = "SELECT note FROM products WHERE id='$product_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'note' => $row['note']]);
    } else {
        echo json_encode(['success' => false, 'note' => '']);
    }

    $conn->close();
}
?>
