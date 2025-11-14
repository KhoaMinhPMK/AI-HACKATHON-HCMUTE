<?php
/**
 * Victoria AI - Update Auth Tokens
 * Updates OAuth access/refresh tokens for a user
 * 
 * Endpoint: POST /php/api/auth/update-token.php
 * Body: { firebaseUid, accessToken, refreshToken, expiresIn, scope }
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/response.php';

setJSONHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['firebaseUid'])) {
    sendError('Missing firebaseUid', 400);
}

$firebaseUid = $data['firebaseUid'];
$accessToken = $data['accessToken'] ?? null;
$refreshToken = $data['refreshToken'] ?? null;
$expiresIn = $data['expiresIn'] ?? 3600;
$scope = $data['scope'] ?? null;

try {
    $pdo = getDBConnection();
    
    // Get user ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
    
    // Check if token entry exists
    $stmt = $pdo->prepare("SELECT id FROM auth_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $existingToken = $stmt->fetch();
    
    if ($existingToken) {
        // Update existing token
        $stmt = $pdo->prepare("
            UPDATE auth_tokens 
            SET access_token = ?, refresh_token = ?, scope = ?, expires_at = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$accessToken, $refreshToken, $scope, $expiresAt, $existingToken['id']]);
        $tokenId = $existingToken['id'];
        $message = 'Token updated successfully';
    } else {
        // Insert new token
        $stmt = $pdo->prepare("
            INSERT INTO auth_tokens (user_id, access_token, refresh_token, scope, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $accessToken, $refreshToken, $scope, $expiresAt]);
        $tokenId = $pdo->lastInsertId();
        $message = 'Token created successfully';
    }
    
    sendSuccess([
        'token_id' => $tokenId,
        'expires_in' => $expiresIn,
        'expires_at' => $expiresAt
    ], $message);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
