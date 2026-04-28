CREATE DATABASE IF NOT EXISTS `ossiqntv` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ossiqntv`;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50),
  `password` VARCHAR(255)
);

INSERT INTO `admin_users` (`username`, `password`) VALUES ('admin', 'ossiqn3131-');

CREATE TABLE IF NOT EXISTS `pf_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) DEFAULT 'OSSIQN TV',
  `bio` TEXT,
  `bg_url` VARCHAR(255),
  `logo_url` VARCHAR(255),
  `avatar_url` VARCHAR(255)
);

INSERT INTO `pf_settings` (`name`) VALUES ('OSSIQN TV');

CREATE TABLE IF NOT EXISTS `pf_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50),
  `email` VARCHAR(100),
  `password` VARCHAR(255),
  `balance` DECIMAL(10,2) DEFAULT 0.00,
  `api_key` VARCHAR(100) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `pf_licenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `product_name` VARCHAR(100),
  `license_key` VARCHAR(100),
  `license_type` VARCHAR(50) DEFAULT 'premium',
  `status` VARCHAR(20) DEFAULT 'Aktif',
  `expires_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `pf_promo_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50),
  `duration_months` INT DEFAULT 1
);

INSERT INTO `pf_promo_codes` (`code`, `duration_months`) VALUES ('OSSIQNVIP', 1);o