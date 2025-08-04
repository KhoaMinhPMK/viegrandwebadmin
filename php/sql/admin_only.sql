-- =====================================================
-- VieGrand Web Admin Database Setup
-- Chỉ tạo database admin cho login web
-- Database viegrand đã tồn tại sẵn
-- =====================================================

-- Tạo database cho web admin login (không động vào database viegrand có sẵn)
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

-- Data cho admin login (mật khẩu: password)
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@viegrand.com', 'Web Admin', '0123456789', 'admin', 'active'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@viegrand.com', 'Web Manager', '0987654321', 'manager', 'active');

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
    KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============= HƯỚNG DẪN SỬ DỤNG =============
-- 
-- Database viegrandwebadmin:
-- - Bảng: users (cho login web admin)
-- - Username: admin, Password: password
-- - Username: manager, Password: password
--
-- Database viegrand (SẴN CÓ):
-- - Bảng: user (có sẵn với data thật của bạn)
-- - Các users hiện có: Phùng Minh Khoa, Sơn Tùng, Lê Thị Phương
-- - Tất cả dữ liệu messages, conversations, friend_requests... đều giữ nguyên
--
-- API Usage:
-- - /users.php?db=admin => Users từ viegrandwebadmin.users
-- - /users.php?db=main => Users từ viegrand.user (database có sẵn)
-- - /login.php => Login bằng admin database
--
