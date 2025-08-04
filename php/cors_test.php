<?php
/**
 * VieGrand CORS Test
 * URL: https://viegrand.site/viegrandwebadmin/php/cors_test.php
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test response
$response = [
    'success' => true,
    'message' => 'CORS is working properly!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'None',
        'host' => $_SERVER['HTTP_HOST'] ?? 'Unknown'
    ],
    'cors_headers' => [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
