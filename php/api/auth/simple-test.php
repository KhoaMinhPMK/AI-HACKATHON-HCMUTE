<?php
/**
 * Super simple test - just echo JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'PHP is working!',
    'server' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => phpversion(),
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
