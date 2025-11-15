<?php
/**
 * Log Search Query
 * Track student searches for monitoring
 */

// Suppress all PHP errors to ensure JSON-only output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['query'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Always return success to not block frontend
try {
    // Log to error_log for monitoring
    error_log(sprintf(
        'Search: user=%s, query=%s, type=%s',
        $input['user_id'] ?? 'unknown',
        $input['query'] ?? '',
        $input['search_type'] ?? 'papers'
    ));
    
    // Try to save to database if available
    if (file_exists(__DIR__ . '/../../config/database.php')) {
        require_once __DIR__ . '/../../config/database.php';
        $db = getDBConnection();
        
        // Check if table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'search_logs'");
        if ($tableCheck && $tableCheck->rowCount() > 0) {
            $stmt = $db->prepare("
                INSERT INTO search_logs (user_id, query, search_type, results_count, session_id, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['user_id'],
                $input['query'],
                $input['search_type'] ?? 'papers',
                $input['results_count'] ?? 0,
                $input['session_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            $searchId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Search logged',
                'data' => ['search_id' => $searchId]
            ]);
            exit;
        }
    }
} catch (Exception $e) {
    error_log('Log search error: ' . $e->getMessage());
}

// Always return success even if logging failed
echo json_encode([
    'success' => true,
    'message' => 'Search received',
    'data' => ['search_id' => 0]
]);

