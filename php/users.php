<?php
/**
 * VieGrand Users API
 * URL: https://viegrand.site/viegrandwebadmin/php/users.php
 */

define('VIEGRAND_ACCESS', true);
require_once 'config.php';

/**
 * Lớp xử lý quản lý users
 */
class UsersHandler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lấy danh sách tất cả users
     */
    public function getAllUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Đếm tổng số users
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
            $countStmt->execute();
            $totalUsers = $countStmt->fetch()['total'];
            
            // Lấy danh sách users với phân trang
            $stmt = $this->db->prepare("
                SELECT 
                    id, 
                    username, 
                    email, 
                    full_name, 
                    phone, 
                    role, 
                    status, 
                    created_at, 
                    last_login 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Format dữ liệu
            foreach ($users as &$user) {
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            Utils::logActivity("Retrieved users list (page: $page, limit: $limit)", 'INFO');
            
            return [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'total_pages' => ceil($totalUsers / $limit),
                        'total_users' => (int)$totalUsers,
                        'limit' => (int)$limit
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error getting users list: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách người dùng'
            ];
        }
    }
    
    /**
     * Lấy thông tin một user theo ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id, 
                    username, 
                    email, 
                    full_name, 
                    phone, 
                    role, 
                    status, 
                    created_at, 
                    last_login 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ];
            }
            
            // Format dữ liệu
            $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
            $user['role_display'] = $this->getRoleDisplay($user['role']);
            $user['status_display'] = $this->getStatusDisplay($user['status']);
            $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
            $user['last_login_formatted'] = $user['last_login'] ? 
                date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            
            Utils::logActivity("Retrieved user info for ID: $userId", 'INFO');
            
            return [
                'success' => true,
                'data' => $user
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error getting user info: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin người dùng'
            ];
        }
    }
    
    /**
     * Tìm kiếm users
     */
    public function searchUsers($query, $page = 1, $limit = 10) {
        try {
            $searchTerm = "%$query%";
            $offset = ($page - 1) * $limit;
            
            // Đếm tổng kết quả tìm kiếm
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM users 
                WHERE username LIKE ? 
                   OR email LIKE ? 
                   OR full_name LIKE ? 
                   OR phone LIKE ?
            ");
            $countStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $totalUsers = $countStmt->fetch()['total'];
            
            // Tìm kiếm users
            $stmt = $this->db->prepare("
                SELECT 
                    id, 
                    username, 
                    email, 
                    full_name, 
                    phone, 
                    role, 
                    status, 
                    created_at, 
                    last_login 
                FROM users 
                WHERE username LIKE ? 
                   OR email LIKE ? 
                   OR full_name LIKE ? 
                   OR phone LIKE ?
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Format dữ liệu
            foreach ($users as &$user) {
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            Utils::logActivity("Searched users with query: '$query'", 'INFO');
            
            return [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'total_pages' => ceil($totalUsers / $limit),
                        'total_users' => (int)$totalUsers,
                        'limit' => (int)$limit,
                        'search_query' => $query
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error searching users: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm người dùng'
            ];
        }
    }
    
    /**
     * Generate avatar từ tên
     */
    private function generateAvatar($name) {
        if (!$name) return 'U';
        
        $words = trim($name) ? explode(' ', trim($name)) : ['User'];
        if (count($words) >= 2) {
            return strtoupper($words[0][0] . $words[count($words) - 1][0]);
        } else {
            return strtoupper(substr($words[0], 0, 2));
        }
    }
    
    /**
     * Lấy tên hiển thị role
     */
    private function getRoleDisplay($role) {
        $roles = [
            'admin' => 'Quản trị viên',
            'manager' => 'Quản lý',
            'user' => 'Người dùng'
        ];
        return $roles[$role] ?? 'Người dùng';
    }
    
    /**
     * Lấy tên hiển thị status
     */
    private function getStatusDisplay($status) {
        $statuses = [
            'active' => 'Hoạt động',
            'inactive' => 'Không hoạt động',
            'suspended' => 'Bị khóa'
        ];
        return $statuses[$status] ?? 'Không xác định';
    }
}

// Xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$usersHandler = new UsersHandler();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $result = $usersHandler->getAllUsers($page, $limit);
                Utils::sendResponse($result);
                break;
                
            case 'search':
                $query = $_GET['q'] ?? '';
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                
                if (empty($query)) {
                    Utils::sendResponse([
                        'success' => false,
                        'message' => 'Vui lòng nhập từ khóa tìm kiếm'
                    ], 400);
                }
                
                $result = $usersHandler->searchUsers($query, $page, $limit);
                Utils::sendResponse($result);
                break;
                
            case 'get':
                $userId = (int)($_GET['id'] ?? 0);
                
                if ($userId <= 0) {
                    Utils::sendResponse([
                        'success' => false,
                        'message' => 'ID người dùng không hợp lệ'
                    ], 400);
                }
                
                $result = $usersHandler->getUserById($userId);
                Utils::sendResponse($result);
                break;
                
            default:
                Utils::sendResponse([
                    'success' => true,
                    'message' => 'VieGrand Users API',
                    'version' => APP_VERSION,
                    'endpoints' => [
                        'GET /users.php?action=list&page=1&limit=10' => 'Lấy danh sách users',
                        'GET /users.php?action=search&q=keyword&page=1&limit=10' => 'Tìm kiếm users',
                        'GET /users.php?action=get&id=1' => 'Lấy thông tin user theo ID'
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
