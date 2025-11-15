<?php
/**
 * Search Cache API
 * Cache search results to improve performance
 */

// Suppress errors
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save search results to cache
        $input = json_decode(file_get_contents('php://input'), true);
        
        $query = $input['query'] ?? null;
        $results = $input['results'] ?? null;
        $source = $input['source'] ?? 'semantic_scholar';
        
        if (!$query || !$results) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }
        
        // Create cache key (normalize query)
        $cacheKey = md5(strtolower(trim($query)));
        
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM search_cache WHERE cache_key = ?");
        $stmt->execute([$cacheKey]);
        
        if ($stmt->fetch()) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE search_cache 
                SET results = ?, 
                    hit_count = hit_count + 1,
                    last_accessed = NOW(),
                    expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY)
                WHERE cache_key = ?
            ");
            $stmt->execute([json_encode($results), $cacheKey]);
        } else {
            // Insert new
            $stmt = $db->prepare("
                INSERT INTO search_cache (
                    cache_key, 
                    query, 
                    results, 
                    source,
                    created_at,
                    last_accessed,
                    expires_at,
                    hit_count
                ) VALUES (?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 1)
            ");
            $stmt->execute([
                $cacheKey,
                $query,
                json_encode($results),
                $source
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cache saved',
            'cache_key' => $cacheKey
        ]);
        
    } else {
        // GET - Retrieve from cache
        $query = $_GET['q'] ?? '';
        
        if (!$query) {
            echo json_encode(['success' => false, 'message' => 'Missing query']);
            exit;
        }
        
        $cacheKey = md5(strtolower(trim($query)));
        
        $stmt = $db->prepare("
            SELECT results, created_at, hit_count
            FROM search_cache 
            WHERE cache_key = ? 
            AND expires_at > NOW()
        ");
        $stmt->execute([$cacheKey]);
        
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cache) {
            // Update hit count and last accessed
            $stmt = $db->prepare("
                UPDATE search_cache 
                SET hit_count = hit_count + 1, 
                    last_accessed = NOW()
                WHERE cache_key = ?
            ");
            $stmt->execute([$cacheKey]);
            
            // Return cached results
            echo json_encode([
                'success' => true,
                'cached' => true,
                'results' => json_decode($cache['results'], true),
                'cached_at' => $cache['created_at'],
                'hit_count' => $cache['hit_count'] + 1
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'cached' => false,
                'message' => 'No cache found'
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'cached' => false
    ]);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'cached' => false
    ]);
}
