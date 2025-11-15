<?php
/**
 * Step-by-step test to find exact error
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

$steps = [];
$steps[] = 'Step 1: Script started';

// Step 2: Check service file
$serviceFile = __DIR__ . '/../../services/papers-api.php';
$steps[] = 'Step 2: Service file path = ' . $serviceFile;
$steps[] = 'Step 3: File exists = ' . (file_exists($serviceFile) ? 'YES' : 'NO');

if (!file_exists($serviceFile)) {
    echo json_encode(['error' => 'Service file not found', 'steps' => $steps]);
    exit;
}

// Step 4: Try to read file content
$steps[] = 'Step 4: Trying to read service file';
$content = @file_get_contents($serviceFile);
if ($content === false) {
    echo json_encode(['error' => 'Cannot read service file', 'steps' => $steps]);
    exit;
}
$steps[] = 'Step 5: File readable, size = ' . strlen($content) . ' bytes';

// Step 6: Try to require
$steps[] = 'Step 6: Attempting require_once';
try {
    require_once $serviceFile;
    $steps[] = 'Step 7: require_once successful';
} catch (Throwable $e) {
    echo json_encode([
        'error' => 'require_once failed',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'steps' => $steps
    ]);
    exit;
}

// Step 8: Check class
$steps[] = 'Step 8: Checking if PapersAPI class exists';
if (!class_exists('PapersAPI')) {
    echo json_encode(['error' => 'Class not found', 'steps' => $steps]);
    exit;
}
$steps[] = 'Step 9: Class exists!';

// Step 10: Try to instantiate
$steps[] = 'Step 10: Creating instance';
try {
    $api = new PapersAPI();
    $steps[] = 'Step 11: Instance created!';
} catch (Throwable $e) {
    echo json_encode([
        'error' => 'Cannot create instance',
        'message' => $e->getMessage(),
        'steps' => $steps
    ]);
    exit;
}

// Step 12: Try to call method
$steps[] = 'Step 12: Calling searchSemanticScholar';
try {
    $papers = $api->searchSemanticScholar('test', 1);
    $steps[] = 'Step 13: Search successful! Found ' . count($papers) . ' papers';
    
    echo json_encode([
        'success' => true,
        'steps' => $steps,
        'papers' => $papers
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode([
        'error' => 'Search failed',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'steps' => $steps
    ]);
}
