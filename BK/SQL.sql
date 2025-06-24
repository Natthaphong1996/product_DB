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
 * ตารางนี้ต้องถูกสร้างขึ้นก่อนตาราง `products`
 * เพราะตาราง `products` มี Foreign Key ที่อ้างอิงมายัง `id` ของตารางนี้
 * ทำหน้าที่เก็บข้อมูลหลักของสินค้าแต่ละรายการ
 */
CREATE TABLE IF NOT EXISTS `product_list` (
  `id` varchar(14) NOT NULL COMMENT 'รหัสสินค้า (Primary Key)',
  `product_name` varchar(255) NOT NULL COMMENT 'ชื่อสินค้า',
  `low` int(10) DEFAULT NULL COMMENT 'เกณฑ์แจ้งเตือนสต็อกต่ำ',
  `focus` int(11) DEFAULT 0 COMMENT 'สถานะพิเศษ (เช่น สินค้าแนะนำ)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

/*
 * =================================================================
 * ตาราง: `products`
 * =================================================================
 * ตารางนี้เก็บข้อมูลการเคลื่อนไหวของสินค้า (รับเข้า/เบิกออก)
 * มีการสร้าง Foreign Key Constraint (`fk_product_id`)
 * เพื่อเชื่อมโยง `product_id` กับ `id` ในตาราง `product_list`
 */
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT 'วันที่ทำรายการ',
  `type` varchar(255) NOT NULL COMMENT 'ประเภทรายการ (เช่น รับเข้า, เบิกออก)',
  `product_id` varchar(14) NOT NULL COMMENT 'รหัสสินค้า (Foreign Key)',
  `quantity` int(11) NOT NULL COMMENT 'จำนวน',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_product_id` FOREIGN KEY (`product_id`) REFERENCES `product_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
