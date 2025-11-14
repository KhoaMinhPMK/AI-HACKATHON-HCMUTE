<?php
/**
 * Debug script - Check PHP environment
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$debug = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_filename' => __FILE__,
    'current_dir' => __DIR__,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json')
    ],
    'files_exist' => [
        'database.php' => file_exists(__DIR__ . '/config/database.php'),
        'response.php' => file_exists(__DIR__ . '/helpers/response.php')
    ]
];

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    $debug['database'] = [
        'connected' => true,
        'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn()
    ];
} catch (Exception $e) {
    $debug['database'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($debug, JSON_PRETTY_PRINT);
