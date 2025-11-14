<?php
/**
 * Victoria AI - Database Connection Test
 * Test endpoint to verify MySQL connection
 * 
 * Endpoint: GET /php/api/test-connection.php
 */

require_once '../config/database.php';
require_once '../helpers/response.php';

setJSONHeaders();

try {
    // Test connection
    $pdo = getDBConnection();
    
    // Get database version
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    
    // Get tables count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'victoria_ai'");
    $tablesCount = $stmt->fetch();
    
    // Get users count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $usersCount = $stmt->fetch();
    
    sendSuccess([
        'database' => 'victoria_ai',
        'mysql_version' => $version['version'],
        'tables_count' => $tablesCount['count'],
        'users_count' => $usersCount['count'],
        'connection_status' => 'connected',
        'timestamp' => date('Y-m-d H:i:s')
    ], 'Database connection successful');
    
} catch (Exception $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}
