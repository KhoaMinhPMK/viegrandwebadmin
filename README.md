# VieGrand Web Admin - Documentation

## 📋 Tổng quan dự án

VieGrand Web Admin là hệ thống quản lý người dùng web-based được xây dựng bằng PHP, HTML, CSS và JavaScript. Hệ thống hỗ trợ kết nối đồng thời với 2 database và cung cấp đầy đủ các chức năng CRUD cho quản lý user.

### 🎯 Mục tiêu chính
- Quản lý người dùng từ 2 database khác nhau
- Giao diện responsive, thân thiện với người dùng
- API RESTful với xử lý lỗi tốt
- Bảo mật cao với validation đầy đủ

---

## 🏗️ Kiến trúc hệ thống

```
viegrandweb/
├── index.html              # Landing page
├── home/                   # Dashboard chính
│   ├── index.html          # Giao diện quản lý user
│   ├── script.js           # Logic frontend
│   └── styles.css          # Styling
├── login/                  # Trang đăng nhập
│   ├── index.html
│   ├── script.js
│   └── styles.css
├── php/                    # Backend API
│   ├── config.php          # Cấu hình database & utils
│   ├── users.php           # API quản lý users
│   └── logs/               # Thư mục log
└── README.md               # Documentation
```

---

## 🗄️ Database Schema

### Database 1: `viegrand_admin` (Bảng: `users`)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Database 2: `viegrand` (Bảng: `user`)
```sql
CREATE TABLE user (
    userId INT PRIMARY KEY AUTO_INCREMENT,
    userName VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    premium_status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🔧 Cấu hình hệ thống

### File: `php/config.php`

#### 1. Database Configuration
```php
// Database Admin (cho login web)
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand_admin');
define('DB_USER', 'root');
define('DB_PASS', '');

// Database chính VieGrand
define('MAIN_DB_HOST', 'localhost');
define('MAIN_DB_NAME', 'viegrand');
define('MAIN_DB_USER', 'root');
define('MAIN_DB_PASS', '');
```

#### 2. Class Database - Singleton Pattern
```php
class Database {
    private static $instance = null;        // Admin DB instance
    private static $mainInstance = null;    // Main DB instance
    
    // Lấy connection cho admin database
    public static function getInstance()
    
    // Lấy connection cho main database  
    public static function getMainInstance()
}
```

#### 3. Class Utils - Helper Functions
```php
class Utils {
    public static function sendResponse($data, $code = 200)  // Gửi JSON response
    public static function validateInput($data)              // Validate & sanitize input
    public static function hashPassword($password)           // Hash password
    public static function verifyPassword($password, $hash)  // Verify password
    public static function logActivity($message, $level)     // Ghi log
}
```

---

## 📡 API Endpoints

### File: `php/users.php`

#### Class UsersHandler
```php
class UsersHandler {
    private $db;         // Admin database connection
    private $mainDb;     // Main database connection  
    private $currentDb;  // Current active database ('admin' | 'main')
}
```

### API Routes

| Method | Endpoint | Parameters | Description |
|--------|----------|------------|-------------|
| `GET` | `users.php?action=get` | `page`, `limit`, `db` | Lấy danh sách users |
| `GET` | `users.php?action=get&id={id}` | `id`, `db` | Lấy thông tin 1 user |
| `GET` | `users.php?action=search` | `query`, `page`, `limit`, `db` | Tìm kiếm users |
| `POST` | `users.php` | `db`, `userData` | Tạo user mới |
| `PUT` | `users.php?email={email}` | `email`, `db`, `updateData` | Cập nhật user theo email |
| `DELETE` | `users.php?id={id}` | `id`, `db` | Xóa user |

### Request/Response Format

#### GET Users Request
```javascript
GET /php/users.php?action=get&page=1&limit=10&db=admin
```

#### Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "username": "john_doe",
            "email": "john@example.com",
            "full_name": "John Doe",
            "phone": "0123456789",
            "role": "user",
            "status": "active",
            "created_at": "2025-08-05 10:00:00",
            "last_login": "2025-08-05 10:30:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 5,
        "total_users": 45,
        "per_page": 10
    }
}
```

#### PUT Update User Request
```javascript
PUT /php/users.php?email=john@example.com&db=admin
Content-Type: application/json

{
    "username": "john_updated",
    "full_name": "John Updated Name",
    "phone": "0987654321"
}
```

**⚠️ Lưu ý quan trọng**: Email KHÔNG được phép thay đổi vì được sử dụng làm định danh chính.

---

## 🎨 Frontend Architecture

### File: `home/index.html`

#### Structure
```html
<!DOCTYPE html>
<html>
<head>
    <!-- Meta tags, CSS links -->
</head>
<body>
    <!-- Header với navigation -->
    <header class="main-header">
        
    <!-- Main content -->
    <main class="main-content">
        <!-- Database selector -->
        <div class="database-selector">
        
        <!-- Users table -->
        <div class="users-table-container">
        
        <!-- Pagination -->
        <div class="pagination">
    </main>
    
    <!-- Modal cho xem thông tin user -->
    <div id="userModal" class="modal">
    
    <!-- Modal cho chỉnh sửa user -->
    <div id="editModal" class="modal">
</body>
</html>
```

### File: `home/script.js`

#### Main Functions

##### 1. Data Loading
```javascript
// Load danh sách users
async function loadUsers(page = 1)

// Load user detail theo ID
async function loadUserDetail(userId, database)

// Search users
async function searchUsers(query, page = 1)
```

##### 2. Modal Management
```javascript
// Mở modal xem thông tin
function openUserModal(userId, database)

// Mở modal chỉnh sửa
function openEditUserModal(userId, database)

// Đóng modal
function hideModal(), hideEditModal()
```

##### 3. User Management
```javascript
// Tạo user mới
function createUser(userData)

// Cập nhật user (theo email)
function saveUserChanges()

// Xóa user
function deleteUser(userId, database)
```

##### 4. Form Handling
```javascript
// Populate form với data
function populateEditForm(userData)

// Validate fields
function validateField(event)
function validateAllFields()

// Track changes
function getFormChanges()
function displayChangesPreview(changes)
```

##### 5. UI Utilities
```javascript
// Notifications
function showNotification(message, type)
function showWarning(message)

// Loading states
function showLoadingIndicator()
function hideLoadingIndicator()

// Database switching
function switchDatabase(dbType)
```

---

## 🔄 Application Flow

### 1. Khởi tạo ứng dụng
```
1. Load trang home/index.html
2. Script.js khởi tạo
3. Load danh sách users từ database mặc định (admin)
4. Render table và pagination
```

### 2. Xem thông tin user
```
1. User click vào button "Xem" 
2. Gọi openUserModal(userId, database)
3. Fetch API: GET users.php?action=get&id={userId}&db={database}
4. Populate modal với dữ liệu nhận được
5. Hiển thị modal
```

### 3. Chỉnh sửa user
```
1. User click vào button "Sửa"
2. Gọi openEditUserModal(userId, database)
3. Fetch user data từ API
4. Populate form với dữ liệu hiện tại
5. User thay đổi thông tin (trừ email - readonly)
6. Validate dữ liệu real-time
7. Preview changes
8. Click "Lưu thay đổi"
9. Gọi saveUserChanges()
10. PUT API: users.php?email={email}&db={database}
11. Refresh danh sách users
```

### 4. Logic cập nhật user theo email
```
Frontend:
1. Thu thập dữ liệu từ form (loại bỏ email)
2. Gửi PUT request với email trong URL parameter

Backend (updateUserByEmail):
1. Nhận email từ URL parameter
2. Tìm user trong database theo email
3. Lấy userId từ kết quả tìm kiếm
4. Validate dữ liệu cập nhật
5. Kiểm tra trùng lặp username (nếu có)
6. Thực hiện UPDATE với điều kiện WHERE email = ?
7. Trả về kết quả
```

### 5. Database switching
```
1. User chọn database từ dropdown
2. Gọi switchDatabase(dbType)
3. Reset pagination về trang 1
4. Gọi loadUsers() với database mới
5. Update UI indicators
```

---

## 🔒 Security Features

### 1. Input Validation
```php
// Tất cả input đều được validate
Utils::validateInput($data)  // htmlspecialchars + strip_tags + trim
```

### 2. SQL Injection Prevention
```php
// Sử dụng prepared statements
$stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 3. CORS Configuration
```php
// Chỉ cho phép origins được định nghĩa
define('ALLOWED_ORIGINS', [
    'https://viegrand.site',
    'http://localhost',
    'http://127.0.0.1'
]);
```

### 4. Error Handling
```php
try {
    // Database operations
} catch (Exception $e) {
    Utils::logActivity("Error: " . $e->getMessage(), 'ERROR');
    return ['success' => false, 'message' => 'Generic error message'];
}
```

---

## 📝 Logging System

### Log Files
```
php/logs/app_YYYY-MM-DD.log
```

### Log Format
```
[2025-08-05 10:30:45] [INFO] [IP: 192.168.1.100] User loaded successfully
[2025-08-05 10:31:20] [DEBUG] UpdateUserByEmail called - Email: john@test.com
[2025-08-05 10:31:25] [ERROR] Database connection failed
```

### Log Levels
- `INFO`: Thông tin chung
- `DEBUG`: Debug information (chỉ trong development)
- `ERROR`: Lỗi hệ thống
- `WARNING`: Cảnh báo

---

## 🎨 CSS Architecture

### File: `home/styles.css`

#### 1. Global Styles
```css
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --danger-color: #e74c3c;
    --warning-color: #f39c12;
}
```

#### 2. Component Styles
- `.main-header`: Header navigation
- `.main-content`: Main container
- `.users-table`: Table styling với responsive
- `.modal`: Modal overlays
- `.form-group`: Form field containers
- `.pagination`: Pagination controls
- `.notification`: Toast notifications

#### 3. Responsive Design
```css
@media (max-width: 768px) {
    /* Mobile responsive styles */
}
```

---

## 🚀 Getting Started

### 1. Cài đặt môi trường
```bash
# Yêu cầu: PHP 7.4+, MySQL 5.7+, Web server (Apache/Nginx)

# 1. Clone project
git clone [repository-url]

# 2. Cấu hình database
# - Tạo 2 databases: viegrand_admin, viegrand  
# - Import schema từ file SQL (nếu có)

# 3. Cấu hình config.php
# - Update database credentials
# - Update APP_URL và API_URL
```

### 2. Cấu hình Database
```sql
-- Tạo database admin
CREATE DATABASE viegrand_admin;
USE viegrand_admin;

-- Tạo bảng users cho admin
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tạo database chính
CREATE DATABASE viegrand;
USE viegrand;

-- Tạo bảng user cho main database
CREATE TABLE user (
    userId INT PRIMARY KEY AUTO_INCREMENT,
    userName VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    premium_status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Test ứng dụng
```bash
# 1. Truy cập: http://localhost/viegrandweb/home/
# 2. Kiểm tra kết nối database
# 3. Test các chức năng CRUD
# 4. Kiểm tra responsive trên mobile
```

---

## 🧪 Testing

### 1. API Testing
Sử dụng file `test_edit_api.html` để test API:
```bash
# Mở file: http://localhost/viegrandweb/test_edit_api.html
# Test các endpoint với dữ liệu mẫu
```

### 2. Manual Testing Checklist
- [ ] Load danh sách users từ cả 2 database
- [ ] Search users với keyword
- [ ] Xem chi tiết user
- [ ] Tạo user mới (với validation)
- [ ] Cập nhật user (email readonly)
- [ ] Xóa user (với confirmation)
- [ ] Pagination hoạt động
- [ ] Responsive trên mobile
- [ ] Error handling

---

## 🔧 Troubleshooting

### 1. Database Connection Issues
```php
// Kiểm tra credentials trong config.php
// Kiểm tra MySQL service đang chạy
// Kiểm tra firewall/port 3306
```

### 2. CORS Issues
```javascript
// Kiểm tra ALLOWED_ORIGINS trong config.php
// Kiểm tra browser console cho CORS errors
```

### 3. API Response Issues
```bash
# Kiểm tra logs: php/logs/app_YYYY-MM-DD.log
# Bật error reporting trong PHP
# Sử dụng browser dev tools để debug
```

### 4. Common Errors

#### "Người dùng không tồn tại"
- Kiểm tra email có tồn tại trong database
- Kiểm tra database đang được chọn đúng không

#### "Email không được phép thay đổi"
- Đây là hành vi bình thường, email là readonly field

#### Modal không hiển thị
- Kiểm tra JavaScript console cho errors
- Kiểm tra CSS của modal

---

## 📚 Code Standards

### 1. PHP Coding Standards
```php
// Function names: camelCase
public function getUserById($id)

// Class names: PascalCase  
class UsersHandler

// Constants: UPPER_CASE
define('DB_HOST', 'localhost');

// Always use type hints
public function updateUser(int $userId, array $data): array
```

### 2. JavaScript Standards
```javascript
// Function names: camelCase
function loadUsers(page = 1)

// Constants: UPPER_CASE
const API_BASE_URL = './php/';

// Use const/let, avoid var
const userData = await response.json();

// Async/await preferred over promises
async function fetchData() {
    try {
        const response = await fetch(url);
        return await response.json();
    } catch (error) {
        console.error(error);
    }
}
```

### 3. CSS Standards
```css
/* Class names: kebab-case */
.user-table-container

/* Use CSS custom properties */
:root {
    --primary-color: #2c3e50;
}

/* Mobile-first responsive */
@media (min-width: 768px) {
    /* Desktop styles */
}
```

---

## 🔄 Future Enhancements

### 1. Performance Optimizations
- [ ] Implement pagination caching
- [ ] Add database indexing
- [ ] Optimize SQL queries
- [ ] Add request rate limiting

### 2. Security Enhancements
- [ ] Add authentication system
- [ ] Implement JWT tokens
- [ ] Add role-based permissions
- [ ] Input sanitization improvements

### 3. UI/UX Improvements
- [ ] Add dark mode
- [ ] Improve mobile experience
- [ ] Add bulk operations
- [ ] Export/Import functionality

### 4. Monitoring & Logging
- [ ] Add performance monitoring
- [ ] Implement structured logging
- [ ] Add error tracking
- [ ] Database query logging

---

## 📞 Support & Contact

- **Developer**: VieGrand Team
- **Project Repository**: [GitHub URL]
- **Documentation**: This README.md
- **Issue Tracking**: GitHub Issues

---

## 📜 License

This project is proprietary software. All rights reserved by VieGrand Team.

---

*Last updated: August 5, 2025*
*Version: 1.0.0*
