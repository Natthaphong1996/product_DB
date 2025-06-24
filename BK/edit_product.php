<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['editProductName'];
    $low_quantity = $_POST['editLowQuantity'];
    $focus_quantity = $_POST['editFocusQuantity'];

    // อัพเดตข้อมูลสินค้าในฐานข้อมูล
    $sql = "UPDATE product_list SET low=?, focus=? WHERE product_name=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $low_quantity, $focus_quantity, $product_name);

    if ($stmt->execute()) {
        header("Location: index.php?message=Product updated successfully&alertType=success");
    } else {
        header("Location: index.php?message=Error updating product&alertType=danger");
    }

    $stmt->close();
    $conn->close();
}
?>
