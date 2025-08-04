<?php
/**
 * VieGrand Login API (Multi-Database Support)
 * URL: https://viegrand.site/viegrandweb/php/login.php
 */

define('VIEGRAND_ACCESS', true);
require_once 'config_multi.php';

/**
 * Lớp xử lý đăng nhập cho web admin
 */
class LoginHandler {
    private $db;
    
    public function __construct() {
        // Sử dụng Admin Database cho login web
        $this->db = Database::getAdminInstance()->getConnection();
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
            
            // Kiểm tra rate limiting
            if (!$this->checkRateLimit($username)) {
                Utils::logActivity("Rate limit exceeded for username: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.'
                ];
            }
            
            // Tìm user trong database ADMIN
            $stmt = $this->db->prepare("
                SELECT id, username, password, email, full_name, role, status 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            // Log attempt
            $this->logLoginAttempt($username, false);
            
            if (!$user) {
                Utils::logActivity("Login failed - user not found: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác'
                ];
            }
            
            // Kiểm tra status
            if ($user['status'] !== 'active') {
                Utils::logActivity("Login failed - inactive user: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tài khoản của bạn đã bị vô hiệu hóa'
                ];
            }
            
            // Verify password
            if (!Utils::verifyPassword($password, $user['password'])) {
                Utils::logActivity("Login failed - wrong password: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc mật khẩu không chính xác'
                ];
            }
            
            // Login thành công
            $this->logLoginAttempt($username, true);
            
            // Tạo session
            $sessionToken = $this->createSession($user['id']);
            
            // Cập nhật last_login
            $this->updateLastLogin($user['id']);
            
            // Prepare user data
            $userData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'database_source' => 'admin'
            ];
            
            Utils::logActivity("Successful login for user: $username (Admin DB)", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => $userData,
                    'token' => $sessionToken,
                    'expires_in' => SESSION_LIFETIME
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
        try {
            $token = Utils::generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = Utils::getClientIP();
            
            // Xóa session cũ của user
            $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$userId]);
            
            // Tạo session mới
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $token, $ipAddress, $userAgent, $expiresAt]);
            
            // Lưu vào PHP session
            $_SESSION['user_id'] = $userId;
            $_SESSION['session_token'] = $token;
            $_SESSION['login_time'] = time();
            
            return $token;
            
        } catch (Exception $e) {
            Utils::logActivity("Session creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Kiểm tra rate limiting
     */
    private function checkRateLimit($username) {
        try {
            $ipAddress = Utils::getClientIP();
            $timeLimit = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME);
            
            // Đếm số lần thất bại trong khoảng thời gian
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE (username = ? OR ip_address = ?) 
                AND success = 0 
                AND attempted_at > ?
            ");
            $stmt->execute([$username, $ipAddress, $timeLimit]);
            $attempts = $stmt->fetch()['attempts'];
            
            return $attempts < MAX_LOGIN_ATTEMPTS;
            
        } catch (Exception $e) {
            Utils::logActivity("Rate limit check error: " . $e->getMessage(), 'ERROR');
            return true; // Cho phép login nếu có lỗi
        }
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $success) {
        try {
            $ipAddress = Utils::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, success, user_agent) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $ipAddress, $success ? 1 : 0, $userAgent]);
            
        } catch (Exception $e) {
            Utils::logActivity("Login attempt logging error: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Cập nhật last_login
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            Utils::logActivity("Update last login error: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Verify session token
     */
    public function verifySession($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.username, u.email, u.full_name, u.role, u.status
                FROM user_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.session_token = ? 
                AND s.expires_at > NOW() 
                AND s.is_active = 1
                AND u.status = 'active'
            ");
            $stmt->execute([$token]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session không hợp lệ hoặc đã hết hạn'
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $session['user_id'],
                        'username' => $session['username'],
                        'email' => $session['email'],
                        'full_name' => $session['full_name'],
                        'role' => $session['role'],
                        'database_source' => 'admin'
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Session verification error: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra session'
            ];
        }
    }
    
    /**
     * Logout
     */
    public function logout($token = null) {
        try {
            $token = $token ?: ($_SESSION['session_token'] ?? null);
            
            if ($token) {
                // Xóa session từ database
                $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?")->execute([$token]);
            }
            
            // Xóa PHP session
            session_destroy();
            
            Utils::logActivity("User logged out", 'INFO');
            
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
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $result = $loginHandler->login($username, $password);
                Utils::sendResponse($result);
                break;
                
            case 'logout':
                $token = $_POST['token'] ?? null;
                $result = $loginHandler->logout($token);
                Utils::sendResponse($result);
                break;
                
            default:
                Utils::sendResponse([
                    'success' => false,
                    'message' => 'Action không được hỗ trợ'
                ], 400);
        }
        break;
        
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'verify':
                $token = $_GET['token'] ?? ($_SESSION['session_token'] ?? '');
                
                if (empty($token)) {
                    Utils::sendResponse([
                        'success' => false,
                        'message' => 'Token không được cung cấp'
                    ], 400);
                }
                
                $result = $loginHandler->verifySession($token);
                Utils::sendResponse($result);
                break;
                
            default:
                Utils::sendResponse([
                    'success' => true,
                    'message' => 'VieGrand Login API (Multi-Database)',
                    'version' => APP_VERSION,
                    'database' => 'viegrandwebadmin (Admin Login)',
                    'endpoints' => [
                        'POST /login.php (action=login, username, password)' => 'Đăng nhập',
                        'POST /login.php (action=logout, token)' => 'Đăng xuất',
                        'GET /login.php?action=verify&token=xxx' => 'Kiểm tra session'
                    ]
                ]);
        }
        break;
        
    default:
        Utils::sendResponse([
            'success' => false,
            'message' => 'Method không được hỗ trợ'
        ], 405);
}
?>
