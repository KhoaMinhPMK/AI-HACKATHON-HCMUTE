<?php
/**
 * Victoria AI - Update Auth Tokens
 * Updates OAuth access/refresh tokens for a user
 * 
 * Endpoint: POST /php/api/auth/update-token.php
 * Body: { firebaseUid, accessToken, refreshToken, expiresIn, scope }
 */

require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/validator.php';

setJSONHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$requestBody = getRequestBody();

if (!$requestBody) {
    sendError('Invalid request body', 400);
}

// Validate required fields
$missing = validateRequiredFields($requestBody, ['firebaseUid']);
if ($missing) {
    sendValidationError(['missing_fields' => $missing]);
}

$firebaseUid = $requestBody['firebaseUid'];
$accessToken = $requestBody['accessToken'] ?? null;
$refreshToken = $requestBody['refreshToken'] ?? null;
$expiresIn = $requestBody['expiresIn'] ?? 3600; // Default 1 hour
$scope = $requestBody['scope'] ?? null;

if (!isValidFirebaseUID($firebaseUid)) {
    sendError('Invalid Firebase UID', 400);
}

try {
    $pdo = getDBConnection();
    
    // Get user ID
    $stmt = executeQuery(
        "SELECT id FROM users WHERE firebase_uid = ?",
        [$firebaseUid]
    );
    
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    
    // Calculate expiration timestamp
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
    
    // Check if token entry exists
    $checkStmt = executeQuery(
        "SELECT id FROM auth_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    $existingToken = $checkStmt->fetch();
    
    if ($existingToken) {
        // Update existing token
        $sql = "UPDATE auth_tokens SET 
                    access_token = :access_token,
                    refresh_token = :refresh_token,
                    scope = :scope,
                    expires_at = :expires_at,
                    updated_at = NOW()
                WHERE id = :id";
        
        executeQuery($sql, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'scope' => $scope,
            'expires_at' => $expiresAt,
            'id' => $existingToken['id']
        ]);
        
        $tokenId = $existingToken['id'];
        $message = 'Token updated successfully';
        
    } else {
        // Insert new token
        $sql = "INSERT INTO auth_tokens 
                (user_id, firebase_uid, access_token, refresh_token, scope, expires_at, created_at)
                VALUES (:user_id, :firebase_uid, :access_token, :refresh_token, :scope, :expires_at, NOW())";
        
        executeQuery($sql, [
            'user_id' => $userId,
            'firebase_uid' => $firebaseUid,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'scope' => $scope,
            'expires_at' => $expiresAt
        ]);
        
        $tokenId = (int)$pdo->lastInsertId();
        $message = 'Token created successfully';
    }
    
    // Return token info
    $tokenStmt = executeQuery(
        "SELECT id, user_id, token_type, expires_at, created_at, updated_at 
         FROM auth_tokens WHERE id = ?",
        [$tokenId]
    );
    
    $token = $tokenStmt->fetch();
    
    sendSuccess([
        'token' => $token,
        'expires_in' => $expiresIn
    ], $message);
    
} catch (Exception $e) {
    error_log("Update token error: " . $e->getMessage());
    sendServerError('Failed to update token');
}
