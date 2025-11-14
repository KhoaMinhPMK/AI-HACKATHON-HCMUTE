<?php
/**
 * Victoria AI - Verify Firebase Token
 * Verifies Firebase ID token and returns user data
 * 
 * Endpoint: POST /php/api/auth/verify-token.php
 * Body: { idToken }
 */

require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/validator.php';

setJSONHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$requestBody = getRequestBody();

if (!$requestBody || empty($requestBody['idToken'])) {
    sendError('ID token is required', 400);
}

$idToken = $requestBody['idToken'];

// Validate token format
if (!isValidFirebaseToken($idToken)) {
    sendUnauthorized('Invalid token format');
}

try {
    // Method 1: Verify token using Firebase REST API (no Admin SDK needed)
    $verifyUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . 
                 'AIzaSyA8zc27rx6YIJoyoXyf7dugS-zCjazE6lU'; // Your Firebase API key
    
    $response = file_get_contents($verifyUrl, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['idToken' => $idToken]),
            'timeout' => 10
        ]
    ]));
    
    if ($response === false) {
        sendUnauthorized('Token verification failed');
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['users']) || empty($data['users'])) {
        sendUnauthorized('Invalid or expired token');
    }
    
    $firebaseUser = $data['users'][0];
    $firebaseUid = $firebaseUser['localId'];
    
    // Get user from MySQL
    $pdo = getDBConnection();
    $stmt = executeQuery(
        "SELECT id, firebase_uid, email, display_name, photo_url, email_verified, 
                auth_provider, is_active, last_login 
         FROM users 
         WHERE firebase_uid = ? AND is_active = 1",
        [$firebaseUid]
    );
    
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found in database. Please sync user first.', 404);
    }
    
    // Update last login
    executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    sendSuccess([
        'user' => $user,
        'firebase_data' => [
            'uid' => $firebaseUser['localId'],
            'email' => $firebaseUser['email'],
            'emailVerified' => $firebaseUser['emailVerified'] ?? false,
            'lastLoginAt' => $firebaseUser['lastLoginAt'] ?? null
        ]
    ], 'Token verified successfully');
    
} catch (Exception $e) {
    error_log("Token verification error: " . $e->getMessage());
    sendServerError('Token verification failed');
}
