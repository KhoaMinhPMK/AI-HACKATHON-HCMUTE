<?php
/**
 * Check Profile Completeness API
 * Kiểm tra xem profile đã hoàn thiện chưa và trả về danh sách field còn thiếu
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

// Verify token (simplified)
try {
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
    // Lấy thông tin user
    $stmt = $db->prepare("
        SELECT id, role, profile_completed 
        FROM users 
        WHERE firebase_uid = ?
    ");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    $role = $user['role'];
    
    // Nếu chưa chọn role
    if (!$role) {
        sendSuccess([
            'complete' => false,
            'profile_completed' => false,
            'missing_fields' => ['role'],
            'message' => 'Vui lòng chọn vai trò (Sinh viên hoặc Giảng viên)'
        ]);
        exit;
    }
    
    // Kiểm tra completeness dựa trên role
    $missingFields = [];
    $profileExists = false;
    
    if ($role === 'student') {
        $stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            $missingFields = ['student_id', 'university', 'major', 'phone'];
        } else {
            $profileExists = true;
            $requiredFields = [
                'student_id' => 'Mã số sinh viên',
                'university' => 'Trường đại học',
                'major' => 'Chuyên ngành',
                'phone' => 'Số điện thoại'
            ];
            
            foreach ($requiredFields as $field => $label) {
                if (empty($profile[$field])) {
                    $missingFields[] = [
                        'field' => $field,
                        'label' => $label
                    ];
                }
            }
        }
        
    } elseif ($role === 'lecturer') {
        $stmt = $db->prepare("SELECT * FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            $missingFields = ['lecturer_id', 'university', 'department', 'degree', 'research_interests', 'phone'];
        } else {
            $profileExists = true;
            $requiredFields = [
                'lecturer_id' => 'Mã giảng viên',
                'university' => 'Trường đại học',
                'department' => 'Khoa/Bộ môn',
                'degree' => 'Học vị',
                'research_interests' => 'Lĩnh vực nghiên cứu',
                'phone' => 'Số điện thoại'
            ];
            
            foreach ($requiredFields as $field => $label) {
                if (empty($profile[$field])) {
                    $missingFields[] = [
                        'field' => $field,
                        'label' => $label
                    ];
                }
            }
        }
    }
    
    $isComplete = empty($missingFields);
    
    // Update flag nếu khác với DB
    if ($isComplete !== (bool)$user['profile_completed']) {
        $stmt = $db->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
        $stmt->execute([$isComplete, $userId]);
    }
    
    $response = [
        'complete' => $isComplete,
        'profile_completed' => $isComplete,
        'profile_exists' => $profileExists,
        'role' => $role,
        'missing_fields' => $missingFields,
        'message' => $isComplete 
            ? 'Hồ sơ đã hoàn thiện' 
            : 'Vui lòng cập nhật đầy đủ thông tin trong phần Cài đặt'
    ];
    
    sendSuccess($response);
    
} catch (PDOException $e) {
    error_log('Database error in check-complete: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
}

