<?php
/**
 * Test Papers API Locally
 * Simulate what papers-search.php should do
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? 'test';
$limit = (int)($_GET['limit'] ?? 20);

// Check if service file exists
$serviceFile = __DIR__ . '/../../services/papers-api.php';

if (!file_exists($serviceFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Service file not found',
        'path' => $serviceFile,
        'exists' => false
    ]);
    exit;
}

// Try to include it
try {
    require_once $serviceFile;
    
    if (!class_exists('PapersAPI')) {
        echo json_encode([
            'success' => false,
            'error' => 'PapersAPI class not found after require',
            'file_loaded' => true
        ]);
        exit;
    }
    
    // Try to create instance
    $api = new PapersAPI();
    
    // Try to call searchSemanticScholar
    $papers = $api->searchSemanticScholar($query, $limit);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $papers,
        'total' => count($papers),
        'source' => 'semantic_scholar'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
