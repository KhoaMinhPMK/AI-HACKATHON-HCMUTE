<?php
/**
 * Victoria AI - Sync User API (NO RATE LIMIT VERSION)
 * Syncs user data from Firebase to MySQL
 */

// Include files
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/response.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (!isset($data['user']) || !isset($data['user']['uid'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user.uid']);
    exit;
}

try {
    $user = $data['user'];
    $firebaseUid = $data['user']['uid'];
    $email = $user['email'] ?? null;
    $displayName = $user['displayName'] ?? null;
    $photoURL = $user['photoURL'] ?? null;
    $emailVerified = isset($user['emailVerified']) && $user['emailVerified'] ? 1 : 0;
    $provider = $user['provider'] ?? 'password';
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, firebase_uid, email FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email = ?, display_name = ?, photo_url = ?, 
                email_verified = ?, auth_provider = ?, last_login = NOW()
            WHERE firebase_uid = ?
        ");
        $stmt->execute([$email, $displayName, $photoURL, $emailVerified, $provider, $firebaseUid]);
        
        $action = 'login';
        $userId = $existingUser['id'];
    } else {
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (firebase_uid, email, display_name, photo_url, email_verified, auth_provider)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$firebaseUid, $email, $displayName, $photoURL, $emailVerified, $provider]);
        
        $userId = $pdo->lastInsertId();
        $action = 'register';
        
        // Create default preferences for new user
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, theme, language, notifications_enabled)
            VALUES (?, 'dark', 'vi', 1)
        ");
        $stmt->execute([$userId]);
    }
    
    // Get updated user data
    $stmt = $pdo->prepare("SELECT id, firebase_uid, email, display_name, photo_url, auth_provider, created_at FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $userData = $stmt->fetch();
    
    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User synced successfully',
        'data' => [
            'action' => $action,
            'user' => $userData
        ],
        'timestamp' => time()
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
