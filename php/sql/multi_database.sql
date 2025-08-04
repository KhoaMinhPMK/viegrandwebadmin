-- =====================================================
-- VieGrand Multi-Database Setup Script
-- Author: VieGrand Team
-- Date: August 4, 2025
-- Purpose: Tạo 2 database riêng biệt cho hệ thống
-- =====================================================

-- ============= DATABASE 1: VIEGRANDWEBADMIN =============
-- Dành cho hệ thống đăng nhập/quản trị web
CREATE DATABASE IF NOT EXISTS `viegrandwebadmin` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `viegrandwebadmin`;

-- Bảng users cho web admin
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
    UNIQUE KEY `email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu cho WEB ADMIN
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@viegrand.com', 'Quản Trị Viên', '0123456789', 'admin', 'active'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager1@viegrand.com', 'Nguyễn Văn Manager', '0987654321', 'manager', 'active'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user1@viegrand.com', 'Trần Thị User', '0369852147', 'user', 'active');

-- Bảng sessions cho web admin
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `session_token` varchar(255) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `session_token` (`session_token`),
    KEY `user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng login attempts cho bảo mật
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) DEFAULT NULL,
    `ip_address` varchar(45) NOT NULL,
    `success` tinyint(1) DEFAULT 0,
    `user_agent` text,
    `attempted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_username` (`username`),
    KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============= DATABASE 2: VIEGRAND =============
-- Dành cho dữ liệu chính/production
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
    `avatar` varchar(255) DEFAULT NULL,
    `birth_date` date DEFAULT NULL,
    `address` text DEFAULT NULL,
    `city` varchar(50) DEFAULT NULL,
    `country` varchar(50) DEFAULT 'Vietnam',
    `verified_email` tinyint(1) DEFAULT 0,
    `verified_phone` tinyint(1) DEFAULT 0,
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`),
    KEY `idx_city` (`city`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu cho MAIN SYSTEM
INSERT INTO `user` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`, `city`, `verified_email`) VALUES
('viegrand_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@viegrand.vn', 'VieGrand Administrator', '0123456789', 'admin', 'active', 'Ho Chi Minh City', 1),
('customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer1@gmail.com', 'Nguyễn Văn Khách', '0987654321', 'customer', 'active', 'Ha Noi', 1),
('customer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer2@gmail.com', 'Trần Thị Hàng', '0369852147', 'customer', 'pending', 'Da Nang', 0),
('manager_main', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@viegrand.vn', 'Lê Văn Quản Lý', '0555666777', 'manager', 'active', 'Ho Chi Minh City', 1);

-- Bảng user_profiles cho thông tin mở rộng
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `company` varchar(100) DEFAULT NULL,
    `job_title` varchar(100) DEFAULT NULL,
    `bio` text DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `facebook` varchar(255) DEFAULT NULL,
    `instagram` varchar(255) DEFAULT NULL,
    `preferences` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user_activity cho tracking
CREATE TABLE IF NOT EXISTS `user_activity` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `description` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============= THÔNG TIN QUAN TRỌNG =============
-- 
-- DATABASE 1: viegrandwebadmin
-- - Bảng: users (dành cho đăng nhập web admin)
-- - Mật khẩu mặc định: password (đã hash)
-- - Accounts: admin/admin@viegrand.com, manager1, user1
--
-- DATABASE 2: viegrand  
-- - Bảng: user (dành cho hệ thống chính)
-- - Mật khẩu mặc định: password (đã hash)
-- - Accounts: viegrand_admin, customer1, customer2, manager_main
--
-- Cách sử dụng API:
-- - users_multi.php?action=admin => Lấy users từ viegrandwebadmin.users
-- - users_multi.php?action=main => Lấy users từ viegrand.user
-- - users_multi.php?action=search&db=both => Tìm kiếm cả 2 database
--
-- ============= KẾT THÚC =============
