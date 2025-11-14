<?php
/**
 * Victoria AI - Verify Firebase Token
 * Verifies Firebase ID token via Firebase REST API
 * 
 * Endpoint: POST /php/api/auth/verify-token.php
 * Body: { idToken }
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/response.php';

setJSONHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['idToken'])) {
    sendError('Missing idToken', 400);
}

$idToken = $data['idToken'];

try {
    // Verify token using Firebase REST API
    $apiKey = 'AIzaSyA8zc27rx6YIJoyoXyf7dugS-zCjazE6lU';
    $verifyUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $apiKey;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['idToken' => $idToken]),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($verifyUrl, false, $context);
    
    if ($response === false) {
        sendError('Token verification failed', 401);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['users']) || empty($result['users'])) {
        sendError('Invalid or expired token', 401);
    }
    
    $firebaseUser = $result['users'][0];
    $firebaseUid = $firebaseUser['localId'];
    
    // Get user from MySQL
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, firebase_uid, email, display_name, photo_url, auth_provider, last_login FROM users WHERE firebase_uid = ? AND is_active = 1");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found. Please sync user first.', 404);
    }
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    sendSuccess([
        'user' => $user,
        'firebase_data' => [
            'uid' => $firebaseUser['localId'],
            'email' => $firebaseUser['email'],
            'emailVerified' => $firebaseUser['emailVerified'] ?? false
        ]
    ], 'Token verified successfully');
    
} catch (Exception $e) {
    sendError('Token verification error: ' . $e->getMessage(), 500);
}
