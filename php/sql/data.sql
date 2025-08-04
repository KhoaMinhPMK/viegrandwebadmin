-- =============================================
-- VieGrand Database Setup
-- =============================================

-- Tạo database
CREATE DATABASE IF NOT EXISTS viegrand_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE viegrand_admin;

-- =============================================
-- Bảng users - Thông tin tài khoản người dùng
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user', 'manager') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    avatar VARCHAR(255) DEFAULT NULL
);

-- =============================================
-- Bảng user_sessions - Quản lý phiên đăng nhập
-- =============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Chèn dữ liệu mặc định
-- =============================================

-- Tài khoản admin mặc định
INSERT INTO users (username, password, email, full_name, role, status) VALUES 
('admin', '123', 'admin@viegrand.com', 'Administrator', 'admin', 'active');

-- Một số tài khoản demo khác
INSERT INTO users (username, password, email, full_name, phone, role, status) VALUES 
('manager1', 'manager123', 'manager@viegrand.com', 'Nguyễn Văn Quản Lý', '0901234567', 'manager', 'active'),
('user1', 'user123', 'user1@viegrand.com', 'Trần Thị Người Dùng', '0907654321', 'user', 'active'),
('demo', 'demo123', 'demo@viegrand.com', 'Demo User', '0909876543', 'user', 'active');

-- =============================================
-- Tạo indexes để tối ưu hiệu suất
-- =============================================
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_role ON users(role);
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_session_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions ON user_sessions(user_id, is_active);

-- =============================================
-- Hiển thị thông tin các tài khoản đã tạo
-- =============================================
SELECT 
    id,
    username,
    email,
    full_name,
    role,
    status,
    created_at
FROM users 
ORDER BY role DESC, created_at ASC;