<?php
/**
 * Projects Search API
 * Search student/lecturer projects
 * 
 * GET /api/projects/search.php?q=machine+learning&limit=20
 */

// Suppress all PHP errors to ensure JSON-only output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    
    $db = getDBConnection();
    
    // Check if projects table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'projects'");
    if ($tableCheck->rowCount() === 0) {
        // Return empty results if table doesn't exist
        echo json_encode([
            'success' => true,
            'query' => $query,
            'results' => [],
            'total' => 0,
            'message' => 'Projects feature not yet available'
        ]);
        exit;
    }
    
    // Search projects
    $stmt = $db->prepare("
        SELECT 
            p.*,
            u.username as author_name,
            u.email as author_email,
            (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as team_size
        FROM projects p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE 
            p.title LIKE ? 
            OR p.description LIKE ?
            OR p.tags LIKE ?
            OR p.field LIKE ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
    
    $projects = $stmt->fetchAll();
    
    // Format results
    $results = array_map(function($project) {
        return [
            'id' => $project['id'],
            'title' => $project['title'],
            'description' => $project['description'],
            'field' => $project['field'] ?? 'General',
            'status' => $project['status'] ?? 'active',
            'tags' => $project['tags'] ? explode(',', $project['tags']) : [],
            'author' => [
                'name' => $project['author_name'],
                'email' => $project['author_email']
            ],
            'team_size' => (int)($project['team_size'] ?? 1),
            'created_at' => $project['created_at'],
            'updated_at' => $project['updated_at']
        ];
    }, $projects);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results,
        'total' => count($results)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Projects search error: ' . $e->getMessage());
    
    // Return graceful error
    http_response_code(200); // Don't break frontend
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => [],
        'total' => 0,
        'message' => 'Search temporarily unavailable'
    ]);
}
