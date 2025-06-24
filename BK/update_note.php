<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $note = $_POST['note'];

    // ป้องกัน SQL Injection
    $product_id = $conn->real_escape_string($product_id);
    $note = $conn->real_escape_string($note);

    // อัปเดตหมายเหตุในตาราง products
    $sql = "UPDATE products SET note='$note' WHERE id='$product_id'";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'อัปเดตหมายเหตุเรียบร้อย']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดต']);
    }

    $conn->close();
}
?>
