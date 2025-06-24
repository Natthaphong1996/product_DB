<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['deleteProductName'];

    // ดึง product_id จาก product_list โดยใช้ product_name
    $sql_get_id = "SELECT id FROM product_list WHERE product_name=?";
    $stmt_get_id = $conn->prepare($sql_get_id);
    $stmt_get_id->bind_param("s", $product_name);
    $stmt_get_id->execute();
    $stmt_get_id->bind_result($product_id);
    $stmt_get_id->fetch();
    $stmt_get_id->close();

    if ($product_id) {
        // ลบข้อมูลในตาราง products โดยใช้ product_id
        $sql_delete_products = "DELETE FROM products WHERE product_id=?";
        $stmt_delete_products = $conn->prepare($sql_delete_products);
        $stmt_delete_products->bind_param("s", $product_id);
        $stmt_delete_products->execute();
        $stmt_delete_products->close();

        // ลบข้อมูลในตาราง product_list โดยใช้ product_name
        $sql_delete_product_list = "DELETE FROM product_list WHERE id=?";
        $stmt_delete_product_list = $conn->prepare($sql_delete_product_list);
        $stmt_delete_product_list->bind_param("s", $product_id);

        if ($stmt_delete_product_list->execute()) {
            header("Location: index.php?message=Product deleted successfully&alertType=success");
        } else {
            header("Location: index.php?message=Error deleting product&alertType=danger");
        }

        $stmt_delete_product_list->close();
    } else {
        // หากไม่พบ product_id ที่เกี่ยวข้อง
        header("Location: index.php?message=Product not found&alertType=danger");
    }

    $conn->close();
}
?>
