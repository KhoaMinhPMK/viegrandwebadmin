-- =====================================================
-- VieGrand Dual Database Setup Script  
-- Author: VieGrand Team
-- Date: August 4, 2025
-- =====================================================

-- ============= DATABASE 1: ADMIN LOGIN =============
CREATE DATABASE IF NOT EXISTS `viegrandwebadmin` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `viegrandwebadmin`;

-- Bảng users cho web admin login
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL,
    `full_name` varchar(100) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `role` enum('admin','manager','user') DEFAULT 'user',
    `status` enum('active','inactive','suspended') DEFAULT 'active',
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data cho admin login
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@viegrand.com', 'Quản Trị Viên', '0123456789', 'admin', 'active'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager1@viegrand.com', 'Nguyễn Văn Manager', '0987654321', 'manager', 'active');

-- ============= DATABASE 2: MAIN SYSTEM =============
CREATE DATABASE IF NOT EXISTS `viegrand` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `viegrand`;

-- Bảng user cho hệ thống chính
CREATE TABLE IF NOT EXISTS `user` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL,
    `full_name` varchar(100) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `role` enum('admin','manager','user','customer') DEFAULT 'customer',
    `status` enum('active','inactive','suspended','pending') DEFAULT 'pending',
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data cho main system
INSERT INTO `user` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`) VALUES
('customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer1@gmail.com', 'Nguyễn Văn Khách', '0987654321', 'customer', 'active'),
('customer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer2@gmail.com', 'Trần Thị Hàng', '0369852147', 'customer', 'pending'),
('viegrand_main', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'main@viegrand.vn', 'VieGrand Main User', '0555666777', 'admin', 'active');

-- ============= HƯỚNG DẪN SỬ DỤNG =============
-- 
-- 1. Database cho Web Admin Login:
--    - Database: viegrandwebadmin
--    - Table: users
--    - Accounts: admin/admin@viegrand.com, manager1
--
-- 2. Database cho Main System:
--    - Database: viegrand  
--    - Table: user
--    - Accounts: customer1, customer2, viegrand_main
--
-- 3. API Usage:
--    - /users.php?db=admin => Lấy users từ admin database
--    - /users.php?db=main => Lấy users từ main database
--    - /login.php => Luôn dùng admin database để login web
--
-- 4. Mật khẩu mặc định: password
--
