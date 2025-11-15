<?php
/**
 * Get User Profile API
 * Lấy thông tin profile đầy đủ của user (bao gồm student/lecturer profile)
 * 
 * Method: GET
 * Auth: Required (Firebase Token)
 * Response: JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

// Chỉ cho phép GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

// Lấy Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    sendError('Missing or invalid authorization token', 401);
}

$firebaseToken = $matches[1];

// Verify Firebase token (simplified - trong production nên dùng Firebase Admin SDK)
// Ở đây ta giả sử token đã được verify và extract user info
try {
    // TODO: Implement proper Firebase token verification
    // For now, decode the JWT to get firebase_uid (DEMO ONLY - NOT SECURE)
    $tokenParts = explode('.', $firebaseToken);
    if (count($tokenParts) !== 3) {
        sendError('Invalid token format', 401);
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    $firebase_uid = $payload['user_id'] ?? null;
    
    if (!$firebase_uid) {
        sendError('Invalid token payload', 401);
    }
    
} catch (Exception $e) {
    sendError('Token verification failed: ' . $e->getMessage(), 401);
}

// Kết nối database
$db = getDBConnection();

try {
    // Lấy thông tin user cơ bản
    $stmt = $db->prepare("
        SELECT id, firebase_uid, email, display_name, role, profile_completed, 
               phone, email_verified, created_at, last_login, auth_provider
        FROM users 
        WHERE firebase_uid = ?
    ");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    // Lấy thông tin profile dựa trên role
    $profileData = null;
    
    if ($user['role'] === 'student') {
        $stmt = $db->prepare("
            SELECT * FROM student_profiles WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } elseif ($user['role'] === 'lecturer') {
        $stmt = $db->prepare("
            SELECT * FROM lecturer_profiles WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Parse JSON fields
        if ($profileData && isset($profileData['social_links'])) {
            $profileData['social_links'] = json_decode($profileData['social_links'], true);
        }
    }
    
    // Parse JSON fields cho student
    if ($profileData && isset($profileData['social_links']) && is_string($profileData['social_links'])) {
        $profileData['social_links'] = json_decode($profileData['social_links'], true);
    }
    
    // Combine data
    $response = [
        'user' => $user,
        'profile' => $profileData,
        'profileType' => $user['role']
    ];
    
    sendSuccess($response, 'Profile retrieved successfully');
    
} catch (PDOException $e) {
    error_log('Database error in get-profile: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
}

