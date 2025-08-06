<?php
/**
 * Update User API - viegrand database
 * Updates user in viegrand.user table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if this is a test request (GET method)
$isTestRequest = $_SERVER['REQUEST_METHOD'] === 'GET';

if ($isTestRequest) {
    echo json_encode([
        'success' => false,
        'message' => 'This is an API endpoint for updating users. Use PUT method with JSON data.',
        'usage' => [
            'method' => 'PUT',
            'content-type' => 'application/json',
            'example_data' => [
                'id' => 19,
                'userName' => 'New User Name',
                'email' => 'new@email.com',
                'phone' => '123456789',
                'age' => 25,
                'gender' => 'Nam',
                'blood' => 'A+',
                'premium_status' => 1,
                'height' => 170,
                'weight' => 65,
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'heart_rate' => 75
            ]
        ],
        'note' => 'This endpoint is designed to be called from the frontend application, not accessed directly in browser.'
    ]);
    exit();
}

// Only allow PUT requests for actual updates
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed. This API only accepts PUT requests.',
        'received_method' => $_SERVER['REQUEST_METHOD'],
        'expected_method' => 'PUT'
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields. Please provide user ID and data to update.',
        'required_fields' => ['id'],
        'received_data' => $input
    ]);
    exit();
}

// Database configuration
$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $userId = (int)$input['id'];
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    
    // Allowed fields to update
    $allowedFields = [
        'userName', 'email', 'phone', 'role', 'age', 'gender', 'blood', 
        'chronic_diseases', 'allergies', 'premium_status', 'notifications',
        'relative_phone', 'home_address', 'hypertension', 'heart_disease',
        'ever_married', 'work_type', 'residence_type', 'avg_glucose_level',
        'bmi', 'smoking_status', 'stroke', 'height', 'weight',
        'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate'
    ];
    
    // Handle field mapping - convert frontend field names to database field names
    if (isset($input['username']) && !isset($input['userName'])) {
        $input['userName'] = $input['username'];
        unset($input['username']);
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            // Additional validation for specific fields
            if ($field === 'email' && $input[$field] !== '' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid email format provided.'
                ]);
                exit();
            }
            
            if ($field === 'age' && $input[$field] !== '' && (!is_numeric($input[$field]) || $input[$field] < 0 || $input[$field] > 150)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid age. Must be between 0 and 150.'
                ]);
                exit();
            }
            
            if ($field === 'premium_status' && !in_array($input[$field], ['0', '1', 0, 1])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid premium status. Must be 0 or 1.'
                ]);
                exit();
            }
            
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    // Check if premium_status is being changed
    $isPremiumUpgrade = false;
    $isPremiumDowngrade = false;
    
    if (isset($input['premium_status'])) {
        // Check current premium status and get private_key to see if this is an upgrade or downgrade
        $currentStatusStmt = $pdo->prepare("SELECT premium_status, private_key FROM user WHERE userId = ?");
        $currentStatusStmt->execute([$userId]);
        $currentUser = $currentStatusStmt->fetch();
        
        if ($currentUser) {
            if ($input['premium_status'] == '1' && $currentUser['premium_status'] != '1') {
                // Upgrading to premium
                $isPremiumUpgrade = true;
            } elseif ($input['premium_status'] == '0' && $currentUser['premium_status'] == '1') {
                // Downgrading from premium
                $isPremiumDowngrade = true;
            }
        }
    }
    
    // Add premium dates if upgrading to premium
    if ($isPremiumUpgrade) {
        $now = new DateTime();
        $endDate = clone $now;
        $endDate->add(new DateInterval('P30D')); // Add 30 days
        
        $updateFields[] = "premium_start_date = ?";
        $params[] = $now->format('Y-m-d H:i:s');
        
        $updateFields[] = "premium_end_date = ?";
        $params[] = $endDate->format('Y-m-d H:i:s');
        
        // Insert into premium_subscriptions_json table
        if ($currentUser && $currentUser['private_key']) {
            try {
                // Generate premium_key in format: dd + 10-digit auto-increment ID + mmyy
                // First, get the next auto-increment value for this table
                $countStmt = $pdo->prepare("SELECT COUNT(*) + 1 as next_id FROM premium_subscriptions_json");
                $countStmt->execute();
                $nextId = $countStmt->fetch()['next_id'];
                
                // Format the premium_key: dd + 10-digit zero-padded ID + mmyy
                $dayStr = $now->format('d'); // dd format (day)
                $monthYearStr = $now->format('my'); // mmyy format (month + year)
                $idStr = str_pad($nextId, 10, '0', STR_PAD_LEFT); // 10-digit zero-padded
                $premiumKey = $dayStr . $idStr . $monthYearStr;
                
                // Insert into premium_subscriptions_json
                $premiumInsertStmt = $pdo->prepare("
                    INSERT INTO premium_subscriptions_json 
                    (premium_key, young_person_key, elderly_keys, start_date, end_date, note) 
                    VALUES (?, ?, '[]', ?, ?, ?)
                ");
                $premiumInsertStmt->execute([
                    $premiumKey,
                    $currentUser['private_key'],
                    $now->format('Y-m-d H:i:s'),
                    $endDate->format('Y-m-d H:i:s'),
                    'Subscription by admin'
                ]);
                
            } catch (Exception $e) {
                // Log the error but don't fail the main update
                error_log("Failed to insert into premium_subscriptions_json: " . $e->getMessage());
            }
        }
    }
    
    // Handle premium downgrade: set end dates to current moment in both tables
    if ($isPremiumDowngrade) {
        $now = new DateTime();
        $currentMoment = $now->format('Y-m-d H:i:s');
        
        // Set end date to current moment instead of NULL in viegrand.user table
        $updateFields[] = "premium_end_date = ?";
        $params[] = $currentMoment;
        
        // Also update the premium_subscriptions_json table to set end_date to current moment
        if ($currentUser && $currentUser['private_key']) {
            try {
                // Update the end_date in premium_subscriptions_json table for this user
                // Only update records where end_date is in the future (active subscriptions)
                $updatePremiumStmt = $pdo->prepare("
                    UPDATE premium_subscriptions_json 
                    SET end_date = ? 
                    WHERE young_person_key = ? AND end_date > NOW()
                ");
                $updatePremiumStmt->execute([
                    $currentMoment,
                    $currentUser['private_key']
                ]);
                
                // Log the number of affected rows for debugging
                $affectedRows = $updatePremiumStmt->rowCount();
                if ($affectedRows > 0) {
                    error_log("Premium downgrade: Updated {$affectedRows} subscription record(s) for user {$currentUser['private_key']}");
                }
                
            } catch (Exception $e) {
                // Log the error but don't fail the main update
                error_log("Failed to update premium_subscriptions_json end_date during downgrade: " . $e->getMessage());
            }
        }
    }
    
    if (empty($updateFields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No fields to update. Please provide at least one field to modify.',
            'allowed_fields' => $allowedFields,
            'received_data' => $input
        ]);
        exit();
    }
    
    // Add user ID to params
    $params[] = $userId;
    
    $sql = "UPDATE user SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE userId = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Get updated user data
        $getUserStmt = $pdo->prepare("SELECT * FROM user WHERE userId = ?");
        $getUserStmt->execute([$userId]);
        $updatedUser = $getUserStmt->fetch();
        
        if ($updatedUser) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $updatedUser['userId'],
                    'username' => $updatedUser['userName'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'],
                    'premium_status' => $updatedUser['premium_status'],
                    'premium_start_date' => $updatedUser['premium_start_date'],
                    'premium_end_date' => $updatedUser['premium_end_date'],
                    'updated_at' => $updatedUser['updated_at'],
                    'premium_upgraded' => $isPremiumUpgrade,
                    'premium_downgraded' => $isPremiumDowngrade
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found after update']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug_info' => [
            'host' => $host,
            'dbname' => $dbname,
            'error_code' => $e->getCode()
        ]
    ]);
}
?> 