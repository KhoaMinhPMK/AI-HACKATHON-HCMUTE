<?php
/**
 * Papers Search API - SIMPLIFIED VERSION
 * Bypass all complex logic, return mock data for testing
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

// Return mock data immediately for testing
echo json_encode([
    'success' => true,
    'query' => $query,
    'results' => [
        [
            'id' => 'mock-1',
            'source' => 'mock',
            'title' => 'Sample Paper: ' . $query,
            'abstract' => 'This is a test paper about ' . $query,
            'authors' => 'Test Author',
            'year' => '2024',
            'citations' => 10,
            'url' => 'https://example.com/paper/1',
            'pdf_url' => null,
            'venue' => 'Test Conference',
            'thumbnail' => '/assets/default.png'
        ]
    ],
    'total' => 1,
    'message' => 'Using simplified mock version - real API integration pending',
    'sources' => ['mock']
], JSON_UNESCAPED_UNICODE);
