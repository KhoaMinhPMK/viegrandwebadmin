<?php
/**
 * VieGrand Database Configuration
 * Author: VieGrand Team
 * Date: August 4, 2025
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('VIEGRAND_ACCESS')) {
    define('VIEGRAND_ACCESS', true);
}

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand_admin');
define('DB_USER', 'root'); // Thay đổi username database của bạn
define('DB_PASS', ''); // Thay đổi password database của bạn
define('DB_CHARSET', 'utf8mb4');

// Cấu hình ứng dụng
define('APP_NAME', 'VieGrand Admin');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://viegrand.site/viegrandwebadmin/');
define('API_URL', 'https://viegrand.site/viegrandwebadmin/php/');

// Cấu hình bảo mật
define('SECRET_KEY', 'viegrand_secret_key_2025'); // Thay đổi key này
define('SESSION_LIFETIME', 3600); // 1 giờ (tính bằng giây)
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 phút

// Cấu hình CORS
define('ALLOWED_ORIGINS', [
    'https://viegrand.site',
    'http://localhost',
    'http://127.0.0.1'
]);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Kết nối database với PDO
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

/**
 * Các hàm tiện ích
 */
class Utils {
    /**
     * Gửi response JSON
     */
    public static function sendResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Validate input
     */
    public static function validateInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate session token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Get client IP
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log activity
     */
    public static function logActivity($message, $level = 'INFO') {
        $logFile = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIP();
        $logMessage = "[$timestamp] [$level] [IP: $ip] $message" . PHP_EOL;
        
        // Tạo thư mục logs nếu chưa có
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Xử lý CORS cho preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Utils::sendResponse(['message' => 'CORS preflight OK']);
}

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>