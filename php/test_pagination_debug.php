<?php
/**
 * Debug pagination by testing each page individually
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $limit = 10;
    $totalUsers = $pdo->query("SELECT COUNT(*) as total FROM user")->fetch()['total'];
    $totalPages = ceil($totalUsers / $limit);
    
    $results = [];
    
    // Test each page
    for ($page = 1; $page <= $totalPages; $page++) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT userId, userName, email FROM user ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $results[] = [
            'page' => $page,
            'offset' => $offset,
            'expected_users' => ($page == $totalPages) ? ($totalUsers % $limit ?: $limit) : $limit,
            'actual_users' => count($users),
            'user_ids' => array_column($users, 'userId'),
            'user_names' => array_column($users, 'userName')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total_users' => $totalUsers,
        'total_pages' => $totalPages,
        'limit' => $limit,
        'page_results' => $results
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 