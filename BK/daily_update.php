<?php

include 'db_config.php';

// Line Notify Token
$line_notify_token = 'XXXXX'; // เปลี่ยนตาม token ของคุณ

//$line_notify_token = 'FMYgvhudxerIuC0bYSzN3AuzU8jXpND92Tl1tLRybm1'; // เปลี่ยนตาม token ของคุณ

// ฟังก์ชันสำหรับส่งการแจ้งเตือนผ่าน Line Notify
function sendLineNotify($message, $token) {
    $url = 'https://notify-api.line.me/api/notify';
    $data = array('message' => $message);
    $headers = array(
        'Content-Type: multipart/form-data',
        'Authorization: Bearer ' . $token
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// ดึงข้อมูลสินค้าคงเหลือ
$sql = "
    SELECT p.product_name as product, 
           SUM(CASE WHEN d.type = 'D' THEN d.quantity ELSE 0 END) - 
           SUM(CASE WHEN d.type = 'W' THEN d.quantity ELSE 0 END) AS remaining_quantity
    FROM products d 
    INNER JOIN product_list p ON d.product = p.product_name
    GROUP BY p.product_name
";
$result = $conn->query($sql);

// เตรียมข้อความแจ้งเตือน
$message = "อัปเดตสินค้าคงเหลือประจำวันที่ " . date('Y-m-d H:i:s') . "\n";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $message .= "สินค้า: " . htmlspecialchars($row["product"]) . " คงเหลือ: " . htmlspecialchars($row["remaining_quantity"]) . "\n";
    }
} else {
    $message .= "ไม่มีข้อมูลสินค้าคงเหลือ";
}

// ปิดการเชื่อมต่อ
$conn->close();

// ส่งแจ้งเตือนผ่าน Line Notify
sendLineNotify($message, $line_notify_token);
?>
