<?php
require 'vendor/autoload.php'; // โหลด autoload ของ Composer

include 'db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ดึงข้อมูลจากตาราง products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// สร้าง Spreadsheet ใหม่
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Product History');

// กำหนดหัวตาราง
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Date');
$sheet->setCellValue('C1', 'Type');
$sheet->setCellValue('D1', 'Product');
$sheet->setCellValue('E1', 'Quantity');

if ($result->num_rows > 0) {
    $rowIndex = 2; // เริ่มที่แถวที่ 2
    while($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowIndex, $row['id']);
        $sheet->setCellValue('B' . $rowIndex, $row['date']);
        $sheet->setCellValue('C' . $rowIndex, $row['type']);
        $sheet->setCellValue('D' . $rowIndex, $row['product']);
        $sheet->setCellValue('E' . $rowIndex, $row['quantity']);
        $rowIndex++;
    }
} else {
    $sheet->setCellValue('A2', 'No data available');
}

// ปิดการเชื่อมต่อ
$conn->close();

// สร้างไฟล์ Excel และส่งออกไปยังผู้ใช้
$filename = 'product_history.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
