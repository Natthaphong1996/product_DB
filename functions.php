<?php
include 'db_config.php';

function getAlertMessage() {
    if (isset($_GET['message']) && isset($_GET['alertType'])) {
        return "<div class='alert alert-" . htmlspecialchars($_GET['alertType']) . " alert-dismissible fade show' role='alert' id='alertMessage'>" .
            htmlspecialchars($_GET['message']) .
            "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>" .
            "<span aria-hidden='true'>&times;</span></button></div>";
    }
    return '';
}

function getProductQuantityList() {
    global $conn;
    $sql = "
        SELECT p.id as product_id, p.product_name as product, 
               SUM(CASE WHEN d.type = 'D' THEN d.quantity ELSE 0 END) - 
               SUM(CASE WHEN d.type = 'W' THEN d.quantity ELSE 0 END) AS remaining_quantity,
               p.low, p.focus
        FROM products d 
        INNER JOIN product_list p ON d.product_id = p.id
        GROUP BY p.id, p.product_name, p.low, p.focus
    ";
    $result = $conn->query($sql);

    $output = "";
    if ($result->num_rows > 0) {
        $index = 1;
        while ($row = $result->fetch_assoc()) {
            $product_id = htmlspecialchars($row["product_id"]);
            $product_name = htmlspecialchars($row["product"]);
            $remaining_quantity = htmlspecialchars($row["remaining_quantity"]);
            $low_quantity = htmlspecialchars($row["low"]);
            $focus_quantity = htmlspecialchars($row["focus"]);

            if ($focus_quantity > 0) {
                $percentage = ($remaining_quantity / $focus_quantity) * 100;
            } else {
                $percentage = 0;
            }

            $color = getColorBasedOnPercentage($percentage, $remaining_quantity, $focus_quantity);

            $output .= "<tr style='background-color: {$color}'>
                            <td>{$index}</td>
                            <td>{$product_id}</td>
                            <td>{$product_name}</td>
                            <td>{$remaining_quantity}</td>
                            <td>{$low_quantity}</td>
                            <td>{$focus_quantity}</td>
                            <td>
                                <button class='btn btn-success' onclick=\"setTypeAndShowModal('D', '{$product_name}')\">เก็บเข้าคลัง</button>
                                <button class='btn btn-warning' onclick=\"setTypeAndShowModal('W', '{$product_name}')\">เบิกใช้งาน</button>
                                <button class='btn btn-primary' onclick=\"setEditAndShowModal('{$product_name}', '{$low_quantity}', '{$focus_quantity}')\">แก้ไข</button>
                                <button class='btn btn-danger' onclick=\"setDeleteAndShowModal('{$product_name}')\">ลบสินค้า</button>
                            </td>
                        </tr>";
            $index++;
        }
    } else {
        $output .= "<tr><td colspan='7'>No data available</td></tr>";
    }

    return $output;
}

function getColorBasedOnPercentage($percentage, $remaining_quantity, $focus_quantity) {
    if ($remaining_quantity > $focus_quantity) {
        return 'white';
    }

    $red = 255;
    $green = intval(255 * ($percentage / 100));
    if ($green > 255) {
        $green = 255;
    }
    return "rgb($red, $green, 0)";
}

function getProductOptions() {
    global $conn;
    $sql_products = "SELECT product_name FROM product_list";
    $result_products = $conn->query($sql_products);

    $options = "<option value=''>Select a product</option>";
    if ($result_products->num_rows > 0) {
        while ($row = $result_products->fetch_assoc()) {
            $options .= "<option value='" . htmlspecialchars($row["product_name"]) . "'>" . htmlspecialchars($row["product_name"]) . "</option>";
        }
    }
    return $options;
}


function sendLineNotify($message) {
    $token = 'FMYgvhudxerIuC0bYSzN3AuzU8jXpND92Tl1tLRybm1'; // นำ Access Token ของ Line Notify ของคุณมาใส่ที่นี่
    $line_api = 'https://notify-api.line.me/api/notify';

    $headers = array(
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Bearer ' . $token
    );

    $data = array('message' => $message);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $line_api);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}



?>
