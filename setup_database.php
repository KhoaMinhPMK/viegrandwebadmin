<?php
/**
 * Database Setup cho VieGrand Web Admin
 * Chạy file này để tạo database và tables cần thiết
 */

// Cấu hình database (phải giống với config.php)
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Kết nối MySQL mà không chọn database cụ thể
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>VieGrand Database Setup</h2>";
    
    // 1. Tạo database viegrandwebadmin (nếu chưa có)
    echo "<h3>1. Tạo database viegrandwebadmin...</h3>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS viegrandwebadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database viegrandwebadmin đã được tạo hoặc đã tồn tại<br>";
    
    // 2. Chọn database viegrandwebadmin
    $pdo->exec("USE viegrandwebadmin");
    
    // 3. Tạo bảng users cho admin login
    echo "<h3>2. Tạo bảng users...</h3>";
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        role ENUM('admin', 'manager', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createUsersTable);
    echo "✅ Bảng users đã được tạo hoặc đã tồn tại<br>";
    
    // 4. Tạo user admin mặc định (nếu chưa có)
    echo "<h3>3. Tạo user admin mặc định...</h3>";
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    
    if ($checkAdmin == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = "
        INSERT INTO users (username, password, email, full_name, phone, role, status) 
        VALUES ('admin', ?, 'admin@viegrand.site', 'Administrator', '0123456789', 'admin', 'active')";
        
        $stmt = $pdo->prepare($insertAdmin);
        $stmt->execute([$adminPassword]);
        echo "✅ User admin đã được tạo (username: admin, password: admin123)<br>";
    } else {
        echo "ℹ️ User admin đã tồn tại<br>";
    }
    
    // 5. Kiểm tra database viegrand (main database)
    echo "<h3>4. Kiểm tra database viegrand...</h3>";
    $databases = $pdo->query("SHOW DATABASES LIKE 'viegrand'")->fetchAll();
    if (count($databases) > 0) {
        echo "✅ Database viegrand đã tồn tại<br>";
        
        // Kiểm tra bảng user trong database viegrand
        $pdo->exec("USE viegrand");
        $tables = $pdo->query("SHOW TABLES LIKE 'user'")->fetchAll();
        if (count($tables) > 0) {
            echo "✅ Bảng user trong database viegrand đã tồn tại<br>";
            
            // Đếm số user trong bảng
            $userCount = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
            echo "📊 Có $userCount users trong database viegrand<br>";
        } else {
            echo "⚠️ Bảng user không tồn tại trong database viegrand<br>";
        }
    } else {
        echo "⚠️ Database viegrand không tồn tại<br>";
        echo "💡 Bạn cần tạo database viegrand hoặc import dữ liệu từ file SQL<br>";
    }
    
    echo "<h3>✅ Setup hoàn thành!</h3>";
    echo "<p><strong>Thông tin đăng nhập web admin:</strong></p>";
    echo "<ul>";
    echo "<li>URL: <a href='/viegrandweb/login/' target='_blank'>http://localhost/viegrandweb/login/</a></li>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Lỗi kết nối database:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<h4>Kiểm tra:</h4>";
    echo "<ul>";
    echo "<li>XAMPP/MySQL đã được khởi động chưa?</li>";
    echo "<li>Thông tin kết nối database trong config.php có đúng không?</li>";
    echo "<li>User 'root' có quyền tạo database không?</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2E86AB; }
h3 { color: #1B5E7A; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
</style>
