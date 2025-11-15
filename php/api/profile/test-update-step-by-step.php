<?php
/**
 * Test Update Step by Step
 * Debug từng step để tìm lỗi
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = ['success' => true, 'steps' => []];

// Step 1: PHP works?
$result['steps']['1_php'] = 'OK';

// Step 2: Can receive POST?
$result['steps']['2_method'] = $_SERVER['REQUEST_METHOD'];

// Step 3: Can read input?
$input = file_get_contents('php://input');
$result['steps']['3_input_received'] = strlen($input) . ' bytes';

// Step 4: Can parse JSON?
$data = json_decode($input, true);
$result['steps']['4_json_parsed'] = $data ? 'OK' : 'FAILED';

if ($data) {
    $result['steps']['5_data_keys'] = array_keys($data);
}

// Step 5: Can connect DB?
try {
    $db = new PDO("mysql:host=localhost;dbname=victoria_ai", "root", "123456");
    $result['steps']['6_db_connected'] = 'OK';
    
    // Step 6: Can query users?
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    $result['steps']['7_users_count'] = $count['count'];
    
} catch (Exception $e) {
    $result['steps']['6_db_error'] = $e->getMessage();
    $result['success'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT);

