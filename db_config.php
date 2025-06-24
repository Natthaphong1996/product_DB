<?php
// กำหนดค่า Timezone ให้เป็นของประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "product_db";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // ส่ง HTTP Status Code 500 และหยุดการทำงาน
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// กำหนด character set เป็น utf8mb4 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");
?>