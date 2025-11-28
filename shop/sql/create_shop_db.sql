-- Create simple shop database and carts table for XAMPP (MySQL / MariaDB)
-- Usage: import this file via phpMyAdmin or mysql CLI

CREATE DATABASE IF NOT EXISTS `shop_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shop_db`;

CREATE TABLE IF NOT EXISTS `carts` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `session_product` (`session_id`,`product_id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`)
);

-- users table for basic auth (email+password)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(120) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
