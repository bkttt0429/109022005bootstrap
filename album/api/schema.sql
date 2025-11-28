-- Database schema for the Album shop demo
-- Tested with MySQL/MariaDB (XAMPP default)

CREATE DATABASE IF NOT EXISTS shop_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Users table (simple auth for demo only)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart items (one row per product per visitor)
CREATE TABLE IF NOT EXISTS carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(255) NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uc_cart_user_product UNIQUE (user_id, product_id),
  CONSTRAINT uc_cart_session_product UNIQUE (session_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example user (password: password)
INSERT INTO users (email, name, password_hash)
VALUES ('demo@example.com', 'Demo User', '$2y$10$sF0rtCwAd3C3BjiMo0VwJeE9suNr97W7ikhkEjvq9ZBAzF.vS8HXe')
ON DUPLICATE KEY UPDATE email=email;
