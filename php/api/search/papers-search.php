<?php
/**
 * Papers Search API
 * Search papers from multiple sources + AI analysis
 * 
 * GET /api/search/papers-search.php?q=machine+learning&limit=20
 */

// Suppress all PHP errors and warnings to ensure JSON-only output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Try to load the service class
try {
    $serviceFile = __DIR__ . '/../../services/papers-api.php';
    if (!file_exists($serviceFile)) {
        throw new Exception('Service file not found: ' . $serviceFile);
    }
    require_once $serviceFile;
} catch (Exception $e) {
    error_log('Failed to load papers-api.php: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'query' => $_GET['q'] ?? '',
        'results' => [],
        'total' => 0,
        'message' => 'Service temporarily unavailable'
    ]);
    exit;
}

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

try {
    // Verify service file exists
    if (!file_exists(__DIR__ . '/../../services/papers-api.php')) {
        throw new Exception('PapersAPI service file not found');
    }
    
    // Initialize API
    if (!class_exists('PapersAPI')) {
        throw new Exception('PapersAPI class not found');
    }
    
    $papersAPI = new PapersAPI();
    
    // Search from multiple sources in parallel
    error_log("Searching for: $query");
    
    $papers = [];
    $errors = [];
    
    // Try Semantic Scholar first (most reliable)
    try {
        $semanticScholar = $papersAPI->searchSemanticScholar($query, $limit);
        error_log("Semantic Scholar found: " . count($semanticScholar) . " papers");
        $papers = array_merge($papers, $semanticScholar);
    } catch (Exception $e) {
        $errorMsg = "Semantic Scholar error: " . $e->getMessage();
        error_log($errorMsg);
        $errors[] = $errorMsg;
    }
    
    // Try arXiv as supplementary source
    try {
        $arxiv = $papersAPI->searchArXiv($query, min(10, $limit));
        error_log("arXiv found: " . count($arxiv) . " papers");
        $papers = array_merge($papers, $arxiv);
    } catch (Exception $e) {
        $errorMsg = "arXiv error: " . $e->getMessage();
        error_log($errorMsg);
        $errors[] = $errorMsg;
    }
    
    // If no papers found from any source, return empty results
    if (empty($papers)) {
        echo json_encode([
            'success' => true,
            'query' => $query,
            'results' => [],
            'total' => 0,
            'message' => 'No papers found',
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Merge and rank all results
    if (count($papers) > 0) {
        $papers = $papersAPI->mergeAndRank([
            'combined' => $papers
        ], $query);
    }
    
    // Limit final results
    $papers = array_slice($papers, 0, $limit);
    
    error_log("Total papers returned: " . count($papers));
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $papers,
        'total' => count($papers),
        'sources' => array_unique(array_column($papers, 'source'))
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Papers search error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return graceful error (200 status to not break frontend)
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => [],
        'total' => 0,
        'message' => 'Search temporarily unavailable: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

