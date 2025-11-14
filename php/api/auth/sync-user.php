<?php
/**
 * Victoria AI - Sync User API
 * Syncs user data from Firebase to MySQL
 * 
 * Endpoint: POST /php/api/auth/sync-user.php
 * Body: { idToken, user: { uid, email, displayName, photoURL, emailVerified, provider } }
 */

require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/validator.php';

// Set headers
setJSONHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Rate limiting
$clientIP = getClientIP();
if (!checkRateLimit($clientIP, 60, 3600)) {
    sendError('Rate limit exceeded. Try again later.', 429);
}

// Get request body
$requestBody = getRequestBody();

if (!$requestBody) {
    sendError('Invalid request body', 400);
}

// Validate required fields
$missing = validateRequiredFields($requestBody, ['idToken', 'user']);
if ($missing) {
    sendValidationError(['missing_fields' => $missing]);
}

$idToken = $requestBody['idToken'];
$userData = $requestBody['user'];

// Verify Firebase token (simplified - in production use Firebase Admin SDK)
if (!isValidFirebaseToken($idToken)) {
    sendUnauthorized('Invalid Firebase token format');
}

// Validate user data
$validation = validateUserData($userData);
if (!$validation['valid']) {
    sendValidationError($validation['errors']);
}

$sanitizedData = $validation['data'];

try {
    $pdo = getDBConnection();
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if user exists
    $stmt = executeQuery(
        "SELECT id, firebase_uid, email FROM users WHERE firebase_uid = ?",
        [$sanitizedData['firebase_uid']]
    );
    
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user
        $sql = "UPDATE users SET 
                    email = :email,
                    display_name = :display_name,
                    photo_url = :photo_url,
                    email_verified = :email_verified,
                    auth_provider = :auth_provider,
                    last_login = NOW()
                WHERE firebase_uid = :firebase_uid";
        
        executeQuery($sql, [
            'email' => $sanitizedData['email'],
            'display_name' => $sanitizedData['display_name'] ?? null,
            'photo_url' => $sanitizedData['photo_url'] ?? null,
            'email_verified' => $sanitizedData['email_verified'],
            'auth_provider' => $sanitizedData['auth_provider'],
            'firebase_uid' => $sanitizedData['firebase_uid']
        ]);
        
        $userId = $existingUser['id'];
        $action = 'login';
        $message = 'User synced successfully';
        
    } else {
        // Insert new user
        $sql = "INSERT INTO users 
                (firebase_uid, email, display_name, photo_url, email_verified, auth_provider, created_at, last_login)
                VALUES (:firebase_uid, :email, :display_name, :photo_url, :email_verified, :auth_provider, NOW(), NOW())";
        
        executeQuery($sql, [
            'firebase_uid' => $sanitizedData['firebase_uid'],
            'email' => $sanitizedData['email'],
            'display_name' => $sanitizedData['display_name'] ?? null,
            'photo_url' => $sanitizedData['photo_url'] ?? null,
            'email_verified' => $sanitizedData['email_verified'],
            'auth_provider' => $sanitizedData['auth_provider']
        ]);
        
        $userId = (int)$pdo->lastInsertId();
        $action = 'register';
        $message = 'User created successfully';
        
        // Create default preferences for new user
        $prefSql = "INSERT INTO user_preferences (user_id, theme, language) VALUES (?, 'light', 'vi')";
        executeQuery($prefSql, [$userId]);
    }
    
    // Log activity
    $logSql = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, created_at)
               VALUES (?, ?, ?, ?, NOW())";
    executeQuery($logSql, [
        $userId,
        $action,
        $clientIP,
        getUserAgent()
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Fetch complete user data
    $userStmt = executeQuery(
        "SELECT id, firebase_uid, email, display_name, photo_url, email_verified, 
                auth_provider, is_active, created_at, last_login
         FROM users WHERE id = ?",
        [$userId]
    );
    
    $user = $userStmt->fetch();
    
    sendSuccess([
        'user' => $user,
        'action' => $action
    ], $message, $existingUser ? 200 : 201);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Sync user error: " . $e->getMessage());
    sendServerError('Failed to sync user data');
}
