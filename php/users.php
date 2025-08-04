<?php
/**
 * VieGrand Users API
 * URL: https://viegrand.site/viegrandwebadmin/php/users.php
 */

define('VIEGRAND_ACCESS', true);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    http_response_code(200);
    exit();
}

require_once 'config.php';

/**
 * Lớp xử lý quản lý users - Hỗ trợ 2 database
 */
class UsersHandler {
    private $db;
    private $mainDb;
    private $currentDb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection(); // Admin database
        $this->mainDb = Database::getMainInstance()->getConnection(); // Main database
        $this->currentDb = 'admin'; // Mặc định dùng admin database
    }
    
    /**
     * Chuyển đổi database
     */
    public function switchDatabase($dbType = 'admin') {
        $this->currentDb = $dbType;
        return $this;
    }
    
    /**
     * Lấy connection hiện tại
     */
    private function getCurrentConnection() {
        return $this->currentDb === 'main' ? $this->mainDb : $this->db;
    }
    
    /**
     * Lấy tên table hiện tại
     */
    private function getCurrentTable() {
        return $this->currentDb === 'main' ? 'user' : 'users';
    }
    
    /**
     * Lấy danh sách tất cả users
     */
    public function getAllUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $connection = $this->getCurrentConnection();
            $table = $this->getCurrentTable();
            
            // Đếm tổng số users
            $countStmt = $connection->prepare("SELECT COUNT(*) as total FROM $table");
            $countStmt->execute();
            $totalUsers = $countStmt->fetch()['total'];
            
            // Lấy danh sách users với phân trang
            if ($this->currentDb === 'main') {
                // Query cho database viegrand (bảng user)
                $stmt = $connection->prepare("
                    SELECT 
                        userId as id,
                        userName as username, 
                        email, 
                        userName as full_name,
                        phone, 
                        CASE 
                            WHEN premium_status = 1 THEN 'premium'
                            ELSE 'user' 
                        END as role,
                        CASE 
                            WHEN premium_status = 1 THEN 'premium'
                            ELSE 'active' 
                        END as status,
                        created_at, 
                        updated_at as last_login
                    FROM $table 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
            } else {
                // Query cho database admin (bảng users)
                $stmt = $connection->prepare("
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
                    FROM $table 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
            }
            $stmt->execute([$limit, $offset]);
            $users = $stmt->fetchAll();
            
            // Format dữ liệu
            foreach ($users as &$user) {
                $user['database_source'] = $this->currentDb;
                $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
                $user['role_display'] = $this->getRoleDisplay($user['role']);
                $user['status_display'] = $this->getStatusDisplay($user['status']);
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                $user['last_login_formatted'] = $user['last_login'] ? 
                    date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            }
            
            Utils::logActivity("Retrieved users list from {$this->currentDb} database (page: $page, limit: $limit)", 'INFO');
            
            return [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'database' => $this->currentDb,
                    'table' => $table,
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
            $connection = $this->getCurrentConnection();
            $table = $this->getCurrentTable();
            
            if ($this->currentDb === 'main') {
                // Query cho database viegrand (bảng user)
                $stmt = $connection->prepare("
                    SELECT 
                        userId as id,
                        userName as username, 
                        email, 
                        userName as full_name,
                        phone, 
                        CASE 
                            WHEN premium_status = 1 THEN 'premium'
                            ELSE 'user' 
                        END as role,
                        CASE 
                            WHEN premium_status = 1 THEN 'premium'
                            ELSE 'active' 
                        END as status,
                        premium_status,
                        created_at, 
                        updated_at as last_login
                    FROM $table 
                    WHERE userId = ?
                ");
            } else {
                // Query cho database admin (bảng users)
                $stmt = $connection->prepare("
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
                    FROM $table 
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ];
            }
            
            // Format dữ liệu
            $user['database_source'] = $this->currentDb;
            $user['avatar'] = $this->generateAvatar($user['full_name'] ?: $user['username']);
            $user['role_display'] = $this->getRoleDisplay($user['role']);
            $user['status_display'] = $this->getStatusDisplay($user['status']);
            $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
            $user['last_login_formatted'] = $user['last_login'] ? 
                date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
            
            Utils::logActivity("Retrieved user info for ID: $userId from {$this->currentDb} database", 'INFO');
            
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
     * Cập nhật thông tin người dùng
     */
    public function updateUser($userId, $data) {
        try {
            // Debug log
            Utils::logActivity("UpdateUser called - UserID: $userId, Database: {$this->currentDb}, Data: " . json_encode($data), 'DEBUG');
            
            $connection = $this->getCurrentConnection();
            $table = $this->getCurrentTable();
            
            // Validate dữ liệu
            $allowedFields = $this->getAllowedUpdateFields();
            $updateData = [];
            $updateFields = [];
            
            foreach ($allowedFields as $field => $dbField) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $updateFields[] = "$dbField = ?";
                    $updateData[] = Utils::validateInput($data[$field]);
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'Không có dữ liệu hợp lệ để cập nhật'
                ];
            }
            
            // Kiểm tra user có tồn tại không
            $checkStmt = $connection->prepare("SELECT COUNT(*) FROM $table WHERE " . 
                ($this->currentDb === 'main' ? 'userId' : 'id') . " = ?");
            $checkStmt->execute([$userId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Người dùng không tồn tại'
                ];
            }
            
            // Kiểm tra email trùng lặp (nếu có update email)
            if (isset($data['email'])) {
                $emailCheckStmt = $connection->prepare("SELECT COUNT(*) FROM $table WHERE email = ? AND " . 
                    ($this->currentDb === 'main' ? 'userId' : 'id') . " != ?");
                $emailCheckStmt->execute([Utils::validateInput($data['email']), $userId]);
                
                if ($emailCheckStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Email này đã được sử dụng bởi người dùng khác'
                    ];
                }
            }
            
            // Kiểm tra username trùng lặp (nếu có update username)
            if (isset($data['username'])) {
                $usernameField = $this->currentDb === 'main' ? 'userName' : 'username';
                $usernameCheckStmt = $connection->prepare("SELECT COUNT(*) FROM $table WHERE $usernameField = ? AND " . 
                    ($this->currentDb === 'main' ? 'userId' : 'id') . " != ?");
                $usernameCheckStmt->execute([Utils::validateInput($data['username']), $userId]);
                
                if ($usernameCheckStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Tên đăng nhập này đã được sử dụng bởi người dùng khác'
                    ];
                }
            }
            
            // Thêm timestamp cập nhật
            $updateFields[] = "updated_at = NOW()";
            
            // Thực hiện cập nhật
            $sql = "UPDATE $table SET " . implode(', ', $updateFields) . 
                   " WHERE " . ($this->currentDb === 'main' ? 'userId' : 'id') . " = ?";
            $updateData[] = $userId;
            
            $stmt = $connection->prepare($sql);
            $stmt->execute($updateData);
            
            // Log activity
            $fieldsUpdated = array_keys($data);
            Utils::logActivity("Updated user ID: $userId in {$this->currentDb} database. Fields: " . 
                implode(', ', $fieldsUpdated), 'INFO');
            
            // Lấy thông tin user sau khi cập nhật
            $updatedUser = $this->getUserById($userId);
            
            return [
                'success' => true,
                'message' => 'Cập nhật thông tin người dùng thành công',
                'data' => $updatedUser['data'] ?? null
            ];
            
        } catch (Exception $e) {
            Utils::logActivity("Error updating user ID: $userId - " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin người dùng'
            ];
        }
    }
    
    /**
     * Lấy danh sách fields được phép cập nhật theo database
     */
    private function getAllowedUpdateFields() {
        if ($this->currentDb === 'main') {
            // Fields cho database viegrand (bảng user)
            return [
                'username' => 'userName',
                'email' => 'email',
                'full_name' => 'userName', // Trong DB main, full_name và username cùng field
                'phone' => 'phone'
                // Note: Không cho phép update premium_status trực tiếp
            ];
        } else {
            // Fields cho database admin (bảng users)
            return [
                'username' => 'username',
                'email' => 'email', 
                'full_name' => 'full_name',
                'phone' => 'phone',
                'role' => 'role',
                'status' => 'status'
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
            'user' => 'Người dùng',
            'customer' => 'Khách hàng',
            'premium' => 'Premium User'
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
            'suspended' => 'Bị khóa',
            'pending' => 'Chờ duyệt',
            'premium' => 'Premium'
        ];
        return $statuses[$status] ?? 'Không xác định';
    }
}

// Xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$usersHandler = new UsersHandler();

// Kiểm tra database cần sử dụng
$database = $_GET['db'] ?? 'admin'; // Mặc định dùng admin database
$usersHandler->switchDatabase($database);

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
                    'message' => 'VieGrand Users API - Dual Database Support',
                    'version' => APP_VERSION,
                    'current_database' => $database,
                    'endpoints' => [
                        'GET /users.php?action=list&db=admin&page=1&limit=10' => 'Lấy users từ Admin DB',
                        'GET /users.php?action=list&db=main&page=1&limit=10' => 'Lấy users từ Main DB',
                        'GET /users.php?action=search&q=keyword&db=admin' => 'Tìm kiếm trong Admin DB',
                        'GET /users.php?action=search&q=keyword&db=main' => 'Tìm kiếm trong Main DB',
                        'GET /users.php?action=get&id=1&db=admin' => 'Lấy thông tin user từ Admin DB',
                        'GET /users.php?action=get&id=1&db=main' => 'Lấy thông tin user từ Main DB',
                        'PUT /users.php?id=1&db=admin' => 'Cập nhật thông tin user trong Admin DB',
                        'PUT /users.php?id=1&db=main' => 'Cập nhật thông tin user trong Main DB'
                    ],
                    'databases' => [
                        'admin' => 'viegrandwebadmin.users (Web Admin Login)',
                        'main' => 'viegrand.user (Main Production Data)'
                    ]
                ]);
        }
        break;
        
    case 'PUT':
        // Xử lý cập nhật user
        $database = $_GET['db'] ?? 'admin';
        $usersHandler = new UsersHandler($database);
        
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId <= 0) {
            Utils::sendResponse([
                'success' => false,
                'message' => 'ID người dùng không hợp lệ'
            ], 400);
        }
        
        // Lấy dữ liệu từ request body
        $inputData = json_decode(file_get_contents('php://input'), true);
        
        if (empty($inputData)) {
            Utils::sendResponse([
                'success' => false,
                'message' => 'Dữ liệu cập nhật không hợp lệ'
            ], 400);
        }
        
        $result = $usersHandler->updateUser($userId, $inputData);
        
        if ($result['success']) {
            Utils::sendResponse($result, 200);
        } else {
            Utils::sendResponse($result, 400);
        }
        break;
        
    default:
        Utils::sendResponse([
            'success' => false,
            'message' => 'Method không được hỗ trợ'
        ], 405);
}
?>
