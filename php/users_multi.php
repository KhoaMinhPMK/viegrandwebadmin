<?php
/**
 * VieGrand Multi-Database Users API
 * URL: https://viegrand.site/viegrandwebadmin/php/users_multi.php
 */

define('VIEGRAND_ACCESS', true);
require_once 'config_multi.php';

/**
 * Lớp xử lý quản lý users với multi-database
 */
class MultiUsersHandler {
    private $adminDb;
    private $mainDb;
    
    public function __construct() {
        $this->adminDb = Database::getAdminInstance()->getConnection();
        $this->mainDb = Database::getMainInstance()->getConnection();
    }
    
    /**
     * Lấy danh sách users từ database ADMIN (cho login web)
     */
    public function getAdminUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Đếm tổng số users từ admin DB
            $countStmt = $this->adminDb->prepare("SELECT COUNT(*) as total FROM users");
            $countStmt->execute();
            $totalUsers = $countStmt->fetch()['total'];
            
            // Lấy danh sách users từ admin DB
            $stmt = $this->adminDb->prepare("
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
                $user['database_source'] = 'admin';
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            Utils::logActivity("Retrieved ADMIN users list (page: $page, limit: $limit)", 'INFO');
            
            return [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'database' => 'viegrandwebadmin',
                    'table' => 'users',
                    'pagination' => [
                        'current_page' => (int)$page,
                        'total_pages' => ceil($totalUsers / $limit),
                        'total_users' => (int)$totalUsers,
                        'limit' => (int)$limit
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error getting ADMIN users list: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách người dùng Admin'
            ];
        }
    }
    
    /**
     * Lấy danh sách users từ database MAIN (viegrand chính)
     */
    public function getMainUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Đếm tổng số users từ main DB
            $countStmt = $this->mainDb->prepare("SELECT COUNT(*) as total FROM user");
            $countStmt->execute();
            $totalUsers = $countStmt->fetch()['total'];
            
            // Lấy danh sách users từ main DB
            $stmt = $this->mainDb->prepare("
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
                FROM user 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Format dữ liệu
            foreach ($users as &$user) {
                $user['database_source'] = 'main';
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            Utils::logActivity("Retrieved MAIN users list (page: $page, limit: $limit)", 'INFO');
            
            return [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'database' => 'viegrand',
                    'table' => 'user',
                    'pagination' => [
                        'current_page' => (int)$page,
                        'total_pages' => ceil($totalUsers / $limit),
                        'total_users' => (int)$totalUsers,
                        'limit' => (int)$limit
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error getting MAIN users list: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách người dùng Main'
            ];
        }
    }
    
    /**
     * Tìm kiếm users trong cả 2 database
     */
    public function searchUsers($query, $database = 'both', $page = 1, $limit = 10) {
        $results = [
            'success' => true,
            'data' => [
                'users' => [],
                'databases_searched' => [],
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_pages' => 0,
                    'total_users' => 0,
                    'limit' => (int)$limit,
                    'search_query' => $query
                ]
            ]
        ];
        
        $searchTerm = "%$query%";
        $allUsers = [];
        
        try {
            // Tìm trong Admin DB
            if ($database === 'admin' || $database === 'both') {
                $stmt = $this->adminDb->prepare("
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
                ");
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $adminUsers = $stmt->fetchAll();
                
                foreach ($adminUsers as $user) {
                    $user['database_source'] = 'admin';
                    $allUsers[] = $user;
                }
                
                $results['data']['databases_searched'][] = 'viegrandwebadmin.users';
            }
            
            // Tìm trong Main DB
            if ($database === 'main' || $database === 'both') {
                $stmt = $this->mainDb->prepare("
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
                    FROM user 
                    WHERE username LIKE ? 
                       OR email LIKE ? 
                       OR full_name LIKE ? 
                       OR phone LIKE ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $mainUsers = $stmt->fetchAll();
                
                foreach ($mainUsers as $user) {
                    $user['database_source'] = 'main';
                    $allUsers[] = $user;
                }
                
                $results['data']['databases_searched'][] = 'viegrand.user';
            }
            
            // Phân trang và format
            $totalUsers = count($allUsers);
            $offset = ($page - 1) * $limit;
            $paginatedUsers = array_slice($allUsers, $offset, $limit);
            
            foreach ($paginatedUsers as &$user) {
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            $results['data']['users'] = $paginatedUsers;
            $results['data']['pagination']['total_users'] = $totalUsers;
            $results['data']['pagination']['total_pages'] = ceil($totalUsers / $limit);
            
            Utils::logActivity("Multi-database search with query: '$query' in: $database", 'INFO');
            
            return $results;
            
        } catch (Exception $e) {
            Utils::logActivity("Error in multi-database search: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm người dùng'
            ];
        }
    }
    
    /**
     * Lấy thông tin cả 2 database
     */
    public function getDatabaseInfo() {
        try {
            // Thông tin Admin DB
            $adminStmt = $this->adminDb->prepare("SELECT COUNT(*) as total FROM users");
            $adminStmt->execute();
            $adminTotal = $adminStmt->fetch()['total'];
            
            // Thông tin Main DB
            $mainStmt = $this->mainDb->prepare("SELECT COUNT(*) as total FROM user");
            $mainStmt->execute();
            $mainTotal = $mainStmt->fetch()['total'];
            
            return [
                'success' => true,
                'data' => [
                    'admin_database' => [
                        'name' => 'viegrandwebadmin',
                        'table' => 'users',
                        'total_users' => (int)$adminTotal,
                        'purpose' => 'Web Admin Login System'
                    ],
                    'main_database' => [
                        'name' => 'viegrand',
                        'table' => 'user',
                        'total_users' => (int)$mainTotal,
                        'purpose' => 'VieGrand Main Production Data'
                    ],
                    'total_combined' => $adminTotal + $mainTotal
                ]
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error getting database info: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin database'
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
$multiUsersHandler = new MultiUsersHandler();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'info';
        
        switch ($action) {
            case 'admin':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $result = $multiUsersHandler->getAdminUsers($page, $limit);
                Utils::sendResponse($result);
                break;
                
            case 'main':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                $result = $multiUsersHandler->getMainUsers($page, $limit);
                Utils::sendResponse($result);
                break;
                
            case 'search':
                $query = $_GET['q'] ?? '';
                $database = $_GET['db'] ?? 'both'; // admin, main, both
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 10);
                
                if (empty($query)) {
                    Utils::sendResponse([
                        'success' => false,
                        'message' => 'Vui lòng nhập từ khóa tìm kiếm'
                    ], 400);
                }
                
                $result = $multiUsersHandler->searchUsers($query, $database, $page, $limit);
                Utils::sendResponse($result);
                break;
                
            case 'info':
                $result = $multiUsersHandler->getDatabaseInfo();
                Utils::sendResponse($result);
                break;
                
            default:
                Utils::sendResponse([
                    'success' => true,
                    'message' => 'VieGrand Multi-Database Users API',
                    'version' => APP_VERSION,
                    'endpoints' => [
                        'GET /users_multi.php?action=info' => 'Thông tin cả 2 database',
                        'GET /users_multi.php?action=admin&page=1&limit=10' => 'Users từ Admin DB',
                        'GET /users_multi.php?action=main&page=1&limit=10' => 'Users từ Main DB',
                        'GET /users_multi.php?action=search&q=keyword&db=both&page=1&limit=10' => 'Tìm kiếm (db: admin|main|both)'
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
