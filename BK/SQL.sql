/*
 * สร้างฐานข้อมูล 'product_db' หากยังไม่มีอยู่
 * และกำหนดให้ใช้งานฐานข้อมูลนี้
 */
CREATE DATABASE IF NOT EXISTS `product_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `product_db`;

-- --------------------------------------------------------

/*
 * =================================================================
 * ตาราง: `product_list`
 * =================================================================
 * เก็บข้อมูลหลักของสินค้าแต่ละรายการ
 */
CREATE TABLE IF NOT EXISTS `product_list` (
  `id` varchar(14) NOT NULL COMMENT 'รหัสสินค้า (Primary Key)',
  `product_name` varchar(255) NOT NULL COMMENT 'ชื่อสินค้า',
  `low` int(10) UNSIGNED DEFAULT 0 COMMENT 'เกณฑ์แจ้งเตือนสต็อกต่ำ',
  `focus` int(11) UNSIGNED DEFAULT 0 COMMENT 'จำนวนอ้างอิงสำหรับคำนวณสต็อก',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_name_unique` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

/*
 * =================================================================
 * ตาราง: `products`
 * =================================================================
 * เก็บข้อมูลการเคลื่อนไหวของสินค้า (รับเข้า/เบิกออก)
 * มี Foreign Key เชื่อมโยงกับ `product_list`
 */
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID ของรายการ (Primary Key)',
  `product_id` varchar(14) NOT NULL COMMENT 'รหัสสินค้า (Foreign Key)',
  `date` date NOT NULL COMMENT 'วันที่ทำรายการ',
  `type` enum('D','W') NOT NULL COMMENT 'ประเภทรายการ D=Deposit, W=Withdraw',
  `quantity` int(11) NOT NULL COMMENT 'จำนวน',
  `note` text DEFAULT NULL COMMENT 'หมายเหตุเพิ่มเติม',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_product_id` FOREIGN KEY (`product_id`) REFERENCES `product_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
