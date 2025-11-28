-- Migration: add user support and user_id column to carts
-- Run this if you already imported the previous create_shop_db.sql without users/user_id

ALTER TABLE `carts` ADD COLUMN IF NOT EXISTS `user_id` INT DEFAULT NULL;
ALTER TABLE `carts` ADD UNIQUE INDEX `user_product` (`user_id`,`product_id`);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(120) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
