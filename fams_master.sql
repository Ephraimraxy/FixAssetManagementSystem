-- Fixed Asset Management System (FAMS)
-- Master schema & seed data
-- Generated 2025-07-15

/* ------------------------------------------------------------------
   DATABASE
------------------------------------------------------------------ */
CREATE DATABASE IF NOT EXISTS `fams_db`;
USE `fams_db`;

/* ------------------------------------------------------------------
   TABLE DEFINITIONS
------------------------------------------------------------------ */

-- USERS -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20),
  `department` VARCHAR(100),
  `position` VARCHAR(100),
  `profile_picture` VARCHAR(255),
  `role` ENUM('admin','user') DEFAULT 'user',
  `status` ENUM('active','inactive') DEFAULT 'active',
  `theme_preference` ENUM('light','dark','system') DEFAULT 'system',
  `language_preference` VARCHAR(10) DEFAULT 'en',
  `last_login` TIMESTAMP NULL,
  `failed_login_attempts` INT DEFAULT 0,
  `lockout_time` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_department` (`department`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
);

-- ASSETS ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assets` (
  `asset_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_name` VARCHAR(100) NOT NULL,
  `asset_type` VARCHAR(50),
  `purchase_date` DATE,
  `purchase_cost` DECIMAL(12,2),
  `acquisition_value` DECIMAL(12,2),
  `current_value` DECIMAL(12,2),
  `last_valuation_date` DATE,
  `location` VARCHAR(100),
  `location_details` VARCHAR(255),
  `status` ENUM('active','inactive','disposed') DEFAULT 'active',
  `assigned_to` INT,
  `asset_image` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`user_id`)
);

-- DEPRECIATION ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `depreciation` (
  `depreciation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT NOT NULL,
  `depreciation_method` VARCHAR(50),
  `useful_life` INT,
  `salvage_value` DECIMAL(12,2),
  `start_date` DATE,
  `accumulated_depreciation` DECIMAL(12,2) DEFAULT 0.00,
  `last_depreciation_date` DATE,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`)
);

-- MAINTENANCE -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `maintenance` (
  `maintenance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT NOT NULL,
  `maintenance_date` DATE NOT NULL,
  `description` TEXT,
  `cost` DECIMAL(12,2),
  `performed_by` VARCHAR(100),
  `next_due_date` DATE,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`)
);

-- MAINTENANCE REQUESTS -------------------------------------------
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `request_type` ENUM('repair','maintenance','inspection','other') NOT NULL,
  `priority` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` TEXT NOT NULL,
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending','approved','in_progress','completed','rejected') DEFAULT 'pending',
  `approved_by` INT,
  `approved_date` DATETIME,
  `completion_date` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`)
);

-- ASSET REQUESTS --------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `request_type` ENUM('new_asset','transfer','disposal','return') NOT NULL,
  `asset_id` INT,
  `asset_type` VARCHAR(100),
  `reason` TEXT NOT NULL,
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending','approved','in_progress','completed','rejected') DEFAULT 'pending',
  `approved_by` INT,
  `approved_date` DATETIME,
  `completion_date` DATETIME,
  `notes` TEXT,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_asset_requests_status` (`status`),
  INDEX `idx_asset_requests_user` (`user_id`)
);

-- ASSET IMAGES ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_images` (
  `image_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
);

-- ASSET HISTORY ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_history` (
  `history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT NOT NULL,
  `change_date` DATE NOT NULL,
  `previous_value` DECIMAL(12,2),
  `new_value` DECIMAL(12,2),
  `change_type` ENUM('depreciation','appreciation','revaluation') NOT NULL,
  `change_reason` TEXT,
  `recorded_by` INT,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`)
);

-- USER SETTINGS ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_settings` (
  `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `email_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `dashboard_view` ENUM('grid','list') NOT NULL DEFAULT 'grid',
  `theme` ENUM('light','dark','auto') NOT NULL DEFAULT 'light',
  `maintenance_updates` TINYINT(1) NOT NULL DEFAULT 1,
  `asset_updates` TINYINT(1) NOT NULL DEFAULT 1,
  `system_announcements` TINYINT(1) NOT NULL DEFAULT 1,
  `depreciation_alerts` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_user_settings_user` (`user_id`)
);

/* ------------------------------------------------------------------
   SEED DATA
------------------------------------------------------------------ */
-- Ensure admin user exists
INSERT INTO `users` (username, password, email, full_name, role)
SELECT 'admin',
       '$2y$10$X2THWk7cAT1R.Nb6fNpqsO4447vtAULv0nzt1Z7ZT75WfOTeuLSCu',
       'admin@fams.com',
       'System Administrator',
       'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `role` = 'admin' LIMIT 1);

-- Generic test user
INSERT INTO `users` (username, password, email, full_name, role)
SELECT 'testuser',
       '$2y$10$X2THWk7cAT1R.Nb6fNpqsO4447vtAULv0nzt1Z7ZT75WfOTeuLSCu',
       'testuser@example.com',
       'Test User',
       'user'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'testuser' LIMIT 1);

-- Sample assets
INSERT INTO `assets` (
  asset_name, asset_type, purchase_date, purchase_cost, acquisition_value, current_value, last_valuation_date, location, location_details, status, assigned_to, asset_image
) VALUES
  ('Dell XPS 15 Laptop', 'Electronics', '2023-01-15', 1500.00, 1500.00, 1350.00, '2024-01-15', 'Main Office', 'IT Department, 2nd Floor', 'active', 1, 'laptop.jpg'),
  ('Executive Desk', 'Furniture', '2022-11-10', 800.00, 800.00, 720.00, '2023-11-10', 'Main Office', 'Executive Suite, Room 305', 'active', 1, 'desk.jpg'),
  ('Toyota Camry', 'Vehicle', '2022-05-20', 25000.00, 25000.00, 22500.00, '2023-05-20', 'Parking Lot', 'Company Garage – Space B12', 'active', 1, 'car.jpg');

-- Sample depreciation data
INSERT INTO `depreciation` (asset_id, depreciation_method, useful_life, salvage_value, start_date) VALUES
  (1, 'straight-line', 5, 300.00, '2023-01-15'),
  (2, 'straight-line', 10, 100.00, '2022-11-10'),
  (3, 'straight-line', 8, 5000.00, '2022-05-20');

-- Sample maintenance requests
INSERT INTO `maintenance_requests` (asset_id, user_id, request_type, priority, description, status) VALUES
  (1, 1, 'repair', 'high', 'Laptop screen is flickering and sometimes goes black', 'pending'),
  (2, 1, 'maintenance', 'medium', 'Regular maintenance check for office desk', 'approved'),
  (3, 1, 'inspection', 'medium', 'Annual vehicle inspection due', 'in_progress');

-- Sample asset requests
INSERT INTO `asset_requests` (user_id, request_type, asset_type, reason, status) VALUES
  (1, 'new_asset', 'Electronics', 'Need a new monitor for dual-screen setup', 'pending'),
  (1, 'transfer', 'Furniture', 'Requesting transfer of ergonomic chair from storage', 'pending');

-- Attach asset_id to the transfer request
UPDATE `asset_requests` SET `asset_id` = 2 WHERE `request_type` = 'transfer' AND `asset_id` IS NULL;

/* End of FAMS master schema */
