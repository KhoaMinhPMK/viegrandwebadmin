<?php
/**
 * VieGrand Login API
 * URL: https://viegrand.site/viegrandweb/php/login.php
 */

define('VIEGRAND_ACCESS', true);
require_once 'config.php';

/**
 * Lớp xử lý đăng nhập
 */
class LoginHandler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Xử lý đăng nhập
     */
    public function login($username, $password) {
        try {
            // Validate input
            $username = Utils::validateInput($username);
            $password = Utils::validateInput($password);
            
            if (empty($username) || empty($password)) {
                Utils::logActivity("Login attempt with empty credentials", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Vui lòng nhập đầy đủ thông tin đăng nhập'
                ];
            }
            
            // Kiểm tra số lần đăng nhập sai
            if ($this->isAccountLocked($username)) {
                Utils::logActivity("Login attempt on locked account: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tài khoản đã bị khóa do đăng nhập sai quá nhiều lần. Vui lòng thử lại sau.'
                ];
            }
            
            // Tìm user trong database
            $stmt = $this->db->prepare("
                SELECT id, username, password, email, full_name, role, status 
                FROM users 
                WHERE username = ? AND status = 'active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->recordFailedLogin($username);
                Utils::logActivity("Login failed - user not found: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
                ];
            }
            
            // Kiểm tra mật khẩu (tạm thời so sánh trực tiếp vì trong DB lưu plain text)
            // Trong production nên hash password
            if ($password !== $user['password']) {
                $this->recordFailedLogin($username);
                Utils::logActivity("Login failed - wrong password for user: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
                ];
            }
            
            // Đăng nhập thành công
            $sessionToken = $this->createSession($user['id']);
            $this->updateLastLogin($user['id']);
            $this->clearFailedLogins($username);
            
            Utils::logActivity("Login successful for user: $username (ID: {$user['id']})", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'session_token' => $sessionToken
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Login error: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra trong quá trình đăng nhập'
            ];
        }
    }
    
    /**
     * Tạo session mới
     */
    private function createSession($userId) {
        $token = Utils::generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $ip = Utils::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions 
            (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $ip, $userAgent, $expiresAt]);
        
        // Lưu vào PHP session
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $token;
        $_SESSION['login_time'] = time();
        
        return $token;
    }
    
    /**
     * Cập nhật thời gian đăng nhập cuối
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * Kiểm tra tài khoản có bị khóa không
     */
    private function isAccountLocked($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_count 
            FROM user_sessions 
            WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND session_token LIKE 'FAILED_%'
        ");
        $stmt->execute([Utils::getClientIP(), LOGIN_LOCKOUT_TIME]);
        $result = $stmt->fetch();
        
        return $result['failed_count'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Ghi nhận đăng nhập thất bại
     */
    private function recordFailedLogin($username) {
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions 
            (user_id, session_token, ip_address, user_agent, expires_at, is_active) 
            VALUES (0, ?, ?, ?, NOW(), 0)
        ");
        $failedToken = 'FAILED_' . $username . '_' . time();
        $stmt->execute([$failedToken, Utils::getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? '']);
    }
    
    /**
     * Xóa các lần đăng nhập thất bại
     */
    private function clearFailedLogins($username) {
        $stmt = $this->db->prepare("
            DELETE FROM user_sessions 
            WHERE ip_address = ? AND session_token LIKE 'FAILED_%'
        ");
        $stmt->execute([Utils::getClientIP()]);
    }
    
    /**
     * Đăng xuất
     */
    public function logout($sessionToken) {
        try {
            // Vô hiệu hóa session trong database
            $stmt = $this->db->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE session_token = ?
            ");
            $stmt->execute([$sessionToken]);
            
            // Xóa PHP session
            session_destroy();
            
            Utils::logActivity("User logged out with token: $sessionToken", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ];
        } catch (Exception $e) {
            Utils::logActivity("Logout error: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đăng xuất'
            ];
        }
    }
}

// Xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$loginHandler = new LoginHandler();

switch ($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'login':
                    $username = $input['username'] ?? '';
                    $password = $input['password'] ?? '';
                    $result = $loginHandler->login($username, $password);
                    Utils::sendResponse($result);
                    break;
                    
                case 'logout':
                    $sessionToken = $input['session_token'] ?? $_SESSION['session_token'] ?? '';
                    $result = $loginHandler->logout($sessionToken);
                    Utils::sendResponse($result);
                    break;
                    
                default:
                    Utils::sendResponse([
                        'success' => false,
                        'message' => 'Action không hợp lệ'
                    ], 400);
            }
        } else {
            Utils::sendResponse([
                'success' => false,
                'message' => 'Thiếu tham số action'
            ], 400);
        }
        break;
        
    case 'GET':
        // API info
        Utils::sendResponse([
            'success' => true,
            'message' => 'VieGrand Login API',
            'version' => APP_VERSION,
            'endpoints' => [
                'POST /login.php' => [
                    'action' => 'login',
                    'parameters' => ['username', 'password']
                ],
                'POST /login.php' => [
                    'action' => 'logout',
                    'parameters' => ['session_token']
                ]
            ]
        ]);
        break;
        
    default:
        Utils::sendResponse([
            'success' => false,
            'message' => 'Method không được hỗ trợ'
        ], 405);
}
?>
