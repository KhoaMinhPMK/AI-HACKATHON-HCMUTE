<?php
/**
 * Papers Search API - DEBUG VERSION
 * Step-by-step debugging to find the exact error
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't show errors in response
ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$debug = [];
$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

$debug[] = '1. Script started';

// Step 1: Check if service file exists
$serviceFile = __DIR__ . '/../../services/papers-api.php';
$debug[] = '2. Service file path: ' . $serviceFile;
$debug[] = '3. File exists: ' . (file_exists($serviceFile) ? 'YES' : 'NO');

if (!file_exists($serviceFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Service file not found',
        'debug' => $debug,
        'path' => $serviceFile
    ]);
    exit;
}

$debug[] = '4. Attempting to require service file';

try {
    require_once $serviceFile;
    $debug[] = '5. Service file loaded successfully';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load service file: ' . $e->getMessage(),
        'debug' => $debug,
        'trace' => $e->getTraceAsString()
    ]);
    exit;
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error in service file: ' . $e->getMessage(),
        'debug' => $debug,
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    exit;
}

// Step 2: Check if class exists
$debug[] = '6. Checking if PapersAPI class exists';
if (!class_exists('PapersAPI')) {
    echo json_encode([
        'success' => false,
        'error' => 'PapersAPI class not found',
        'debug' => $debug,
        'declared_classes' => get_declared_classes()
    ]);
    exit;
}

$debug[] = '7. PapersAPI class found';

// Step 3: Try to instantiate
try {
    $debug[] = '8. Creating PapersAPI instance';
    $api = new PapersAPI();
    $debug[] = '9. Instance created successfully';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create PapersAPI instance: ' . $e->getMessage(),
        'debug' => $debug
    ]);
    exit;
}

// Step 4: Try Semantic Scholar search
try {
    $debug[] = '10. Calling searchSemanticScholar()';
    $papers = $api->searchSemanticScholar($query, min(3, $limit));
    $debug[] = '11. Search completed, found ' . count($papers) . ' papers';
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $papers,
        'total' => count($papers),
        'debug' => $debug,
        'source' => 'semantic_scholar'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Search failed: ' . $e->getMessage(),
        'debug' => $debug,
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'trace' => explode("\n", $e->getTraceAsString())
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error during search: ' . $e->getMessage(),
        'debug' => $debug,
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
