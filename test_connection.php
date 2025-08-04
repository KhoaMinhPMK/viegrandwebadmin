<?php
/**
 * Test kết nối database - Kiểm tra cấu hình
 */

echo "<h2>Test Kết Nối Database VieGrand</h2>";

// Thông tin kết nối (giống trong config.php)
$configs = [
    'Admin Database' => [
        'host' => 'localhost',
        'name' => 'viegrand_admin',
        'user' => 'root',
        'pass' => ''
    ],
    'Main Database (VieGrand)' => [
        'host' => 'localhost', 
        'name' => 'viegrand',
        'user' => 'root',
        'pass' => ''
    ]
];

foreach ($configs as $label => $config) {
    echo "<h3>🔍 Test: $label</h3>";
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Kết nối thành công!<br>";
        
        // Liệt kê các bảng
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Các bảng có sẵn: " . implode(', ', $tables) . "<br>";
        
        // Nếu là database viegrand, kiểm tra bảng user
        if ($config['name'] === 'viegrand' && in_array('user', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
            echo "👥 Số lượng users: $count<br>";
            
            // Lấy vài user mẫu
            $users = $pdo->query("SELECT userId, userName, email FROM user LIMIT 3")->fetchAll();
            echo "📝 Users mẫu:<br>";
            foreach ($users as $user) {
                echo "&nbsp;&nbsp;- ID: {$user['userId']}, Name: {$user['userName']}, Email: {$user['email']}<br>";
            }
        }
        
        // Nếu là database admin, kiểm tra bảng users
        if ($config['name'] === 'viegrand_admin' && in_array('users', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "👤 Số lượng admin users: $count<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Lỗi kết nối: " . $e->getMessage() . "<br>";
        
        // Kiểm tra database có tồn tại không
        try {
            $dsn = "mysql:host={$config['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);
            
            $databases = $pdo->query("SHOW DATABASES LIKE '{$config['name']}'")->fetchAll();
            if (empty($databases)) {
                echo "💡 Database '{$config['name']}' không tồn tại<br>";
            }
        } catch (PDOException $e2) {
            echo "💡 Lỗi kết nối MySQL: " . $e2->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
}

echo "<h3>🔧 Hướng dẫn sửa lỗi:</h3>";
echo "<ul>";
echo "<li>Đảm bảo XAMPP/WAMP đã khởi động MySQL</li>";
echo "<li>Kiểm tra username/password MySQL (mặc định: root/trống)</li>";
echo "<li>Đảm bảo database 'viegrand' đã tồn tại</li>";
echo "<li>Nếu dùng hosting, cập nhật thông tin host/user/pass trong config.php</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #2E86AB; }
h3 { color: #1B5E7A; margin-top: 20px; }
</style>
