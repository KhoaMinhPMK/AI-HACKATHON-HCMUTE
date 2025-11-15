<?php
/**
 * Test Database Connection - Direct
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'success' => false,
    'steps' => []
];

// Step 1: Test PDO
try {
    $result['steps']['pdo_available'] = class_exists('PDO') ? 'YES' : 'NO';
} catch (Exception $e) {
    $result['steps']['pdo_available'] = 'ERROR: ' . $e->getMessage();
}

// Step 2: Test connection
try {
    $dsn = "mysql:host=localhost;dbname=victoria_ai;charset=utf8mb4";
    $db = new PDO($dsn, 'root', '123456', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $result['steps']['connection'] = 'SUCCESS';
    
    // Step 3: Check tables
    $tables = ['users', 'student_profiles', 'lecturer_profiles'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $result['steps']["table_$table"] = $stmt->rowCount() > 0 ? 'EXISTS' : 'NOT FOUND';
    }
    
    // Step 4: Check users table structure
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $result['steps']['users_columns'] = $columns;
    
    // Step 5: Try to get a user
    $stmt = $db->query("SELECT firebase_uid, email, role FROM users LIMIT 1");
    $user = $stmt->fetch();
    $result['steps']['sample_user'] = $user;
    
    $result['success'] = true;
    $result['message'] = 'All checks passed!';
    
} catch (PDOException $e) {
    $result['steps']['error'] = $e->getMessage();
    $result['message'] = 'Database error';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

