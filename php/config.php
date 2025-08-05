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

// Cấu hình database cho WEB ADMIN (login web)
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand_admin'); // Database cho login web admin
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_CHARSET', 'utf8mb4');

// Cấu hình database VIEGRAND CHÍNH (database có sẵn của bạn)
define('MAIN_DB_HOST', 'localhost');
define('MAIN_DB_NAME', 'viegrand'); // Database viegrand có sẵn
define('MAIN_DB_USER', 'root');
define('MAIN_DB_PASS', '');
define('MAIN_DB_CHARSET', 'utf8mb4');

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
 * Kết nối database với PDO - Hỗ trợ 2 database
 */
class Database {
    private static $instance = null;
    private static $mainInstance = null;
    private $pdo;
    
    private function __construct($useMainDb = false) {
        try {
            if ($useMainDb) {
                // Kết nối database chính (viegrand)
                $dsn = "mysql:host=" . MAIN_DB_HOST . ";dbname=" . MAIN_DB_NAME . ";charset=" . MAIN_DB_CHARSET;
                $user = MAIN_DB_USER;
                $pass = MAIN_DB_PASS;
            } else {
                // Kết nối database admin (viegrand_admin)
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $user = DB_USER;
                $pass = DB_PASS;
            }
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . ($useMainDb ? MAIN_DB_CHARSET : DB_CHARSET)
            ];
            
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]));
        }
    }
    
    // Lấy instance cho database admin (mặc định)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self(false);
        }
        return self::$instance;
    }
    
    // Lấy instance cho database chính (viegrand)
    public static function getMainInstance() {
        if (self::$mainInstance === null) {
            self::$mainInstance = new self(true);
        }
        return self::$mainInstance;
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
        
        // Chỉ set CORS headers nếu chưa được set
        if (!headers_sent()) {
            // Kiểm tra origin
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
            $allowedOrigins = [
                'http://localhost',
                'http://127.0.0.1',
                'https://viegrand.site'
            ];
            
            $allowOrigin = '*';
            foreach ($allowedOrigins as $allowed) {
                if (strpos($origin, $allowed) === 0) {
                    $allowOrigin = $origin;
                    break;
                }
            }
            
            if (!self::headers_sent_check('Access-Control-Allow-Origin')) {
                header("Access-Control-Allow-Origin: $allowOrigin");
            }
            if (!self::headers_sent_check('Access-Control-Allow-Methods')) {
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            }
            if (!self::headers_sent_check('Access-Control-Allow-Headers')) {
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            }
            if (!self::headers_sent_check('Access-Control-Allow-Credentials')) {
                header('Access-Control-Allow-Credentials: true');
            }
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Kiểm tra xem header đã được gửi chưa
     */
    private static function headers_sent_check($header) {
        $headers = headers_list();
        foreach ($headers as $h) {
            if (stripos($h, $header) === 0) {
                return true;
            }
        }
        return false;
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

// Xử lý CORS cho tất cả requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Xử lý CORS cho preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>