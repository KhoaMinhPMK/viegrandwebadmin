<?php
/**
 * VieGrand Web Admin - Installer
 * Cài đặt database admin cho web login
 * Không động vào database viegrand có sẵn
 */

// Security check
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Method not allowed');
}

// Config database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

$install_status = '';
$install_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Kết nối MySQL server
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Đọc file SQL
        $sql_file = __DIR__ . '/php/sql/admin_only.sql';
        if (!file_exists($sql_file)) {
            throw new Exception('File admin_only.sql không tồn tại!');
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Tách các câu lệnh SQL
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)), 
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(--|#)/', $stmt);
            }
        );
        
        // Thực thi từng câu lệnh
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $pdo->exec($statement);
            }
        }
        
        $install_status = 'success';
        
    } catch (PDOException $e) {
        $install_error = 'Database Error: ' . $e->getMessage();
    } catch (Exception $e) {
        $install_error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VieGrand Admin - Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        
        .installer-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .installer-header h1 {
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .installer-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .installer-body {
            padding: 30px;
        }
        
        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .status-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .status-info {
            background: #cce7ff;
            border-color: #007bff;
            color: #004085;
        }
        
        .install-btn {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .install-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-list li:last-child {
            border-bottom: none;
        }
        
        .login-link {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .login-link:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 VieGrand Admin Installer</h1>
            <p>Cài đặt hệ thống quản trị web cho VieGrand</p>
        </div>
        
        <div class="installer-body">
            <?php if ($install_status === 'success'): ?>
                <div class="status-box status-success">
                    <h3>✅ Cài đặt thành công!</h3>
                    <p>Database admin đã được tạo và sẵn sàng sử dụng.</p>
                </div>
                
                <div class="status-box status-info">
                    <h4>📋 Thông tin đăng nhập:</h4>
                    <ul class="info-list">
                        <li><strong>Admin:</strong> username: admin, password: password</li>
                        <li><strong>Manager:</strong> username: manager, password: password</li>
                        <li><strong>Database admin:</strong> viegrandwebadmin</li>
                        <li><strong>Database chính:</strong> viegrand (giữ nguyên)</li>
                    </ul>
                </div>
                
                <p style="text-align: center; margin-top: 20px;">
                    <a href="login/" class="login-link">🔐 Đăng nhập Admin</a>
                </p>
                
            <?php elseif (!empty($install_error)): ?>
                <div class="status-box status-error">
                    <h3>❌ Lỗi cài đặt</h3>
                    <p><?php echo htmlspecialchars($install_error); ?></p>
                </div>
                
                <form method="POST">
                    <button type="submit" class="install-btn">🔄 Thử lại</button>
                </form>
                
            <?php else: ?>
                <div class="status-box status-info">
                    <h3>📖 Hướng dẫn cài đặt</h3>
                    <p>Installer này sẽ tạo database <strong>viegrandwebadmin</strong> cho hệ thống login web admin.</p>
                    <p><strong>Lưu ý:</strong> Database <strong>viegrand</strong> hiện tại của bạn sẽ không bị thay đổi gì!</p>
                </div>
                
                <div class="status-box status-info">
                    <h4>🔧 Cấu hình database hiện tại:</h4>
                    <div class="code-block">
                        Host: <?php echo $db_host; ?><br>
                        User: <?php echo $db_user; ?><br>
                        Password: <?php echo empty($db_pass) ? '(trống)' : '***'; ?>
                    </div>
                </div>
                
                <div class="status-box status-info">
                    <h4>📦 Sẽ được tạo:</h4>
                    <ul class="info-list">
                        <li>Database: viegrandwebadmin</li>
                        <li>Bảng: users (login web admin)</li>
                        <li>Bảng: user_sessions (sessions)</li>
                        <li>Bảng: login_attempts (bảo mật)</li>
                        <li>Admin account: admin/password</li>
                        <li>Manager account: manager/password</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <button type="submit" class="install-btn">🚀 Bắt đầu cài đặt</button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
                VieGrand Admin Panel v1.0.0
            </div>
        </div>
    </div>
</body>
</html>
