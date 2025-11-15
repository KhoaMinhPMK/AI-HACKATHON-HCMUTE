<?php
/**
 * Update User Profile API
 * Cập nhật thông tin profile (student/lecturer) và kiểm tra completeness
 * 
 * Method: POST
 * Auth: Required (Firebase Token)
 * Body: JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Lấy dữ liệu từ request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON input', 400);
}

// Kết nối database
$db = getDBConnection();

try {
    // Lấy user hiện tại
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    $role = $input['role'] ?? $user['role']; // Cho phép set role nếu chưa có
    
    // Bắt đầu transaction
    $db->beginTransaction();
    
    // Update role nếu chưa có
    if (!$user['role'] && $role) {
        if (!in_array($role, ['student', 'lecturer'])) {
            $db->rollBack();
            sendError('Invalid role. Must be "student" or "lecturer"', 400);
        }
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
    }
    
    // Update phone nếu có
    if (isset($input['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$input['phone'], $userId]);
    }
    
    // Update profile dựa trên role
    if ($role === 'student') {
        $result = updateStudentProfile($db, $userId, $input);
    } elseif ($role === 'lecturer') {
        $result = updateLecturerProfile($db, $userId, $input);
    } else {
        $db->rollBack();
        sendError('Role not set. Please set role first.', 400);
    }
    
    if (!$result['success']) {
        $db->rollBack();
        sendError($result['message'], 400);
    }
    
    // Check profile completeness
    $isComplete = checkProfileCompleteness($db, $userId, $role);
    
    // Update profile_completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
    $stmt->execute([$isComplete, $userId]);
    
    // Log update
    logProfileUpdate($db, $userId, 'update', $input);
    
    // Commit transaction
    $db->commit();
    
    sendSuccess([
        'profile_completed' => $isComplete,
        'message' => $isComplete ? 'Profile updated and completed!' : 'Profile updated. Please complete remaining fields.'
    ], 'Profile updated successfully');
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Database error in update-profile: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
}

/**
 * Update student profile
 */
function updateStudentProfile($db, $userId, $data) {
    // Validate required fields
    $errors = [];
    
    if (isset($data['student_id']) && !validateStudentId($data['student_id'])) {
        $errors[] = 'Invalid student ID format';
    }
    
    if (isset($data['phone']) && !validatePhone($data['phone'])) {
        $errors[] = 'Invalid phone number';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    // Check if profile exists
    $stmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing
        $fields = [];
        $values = [];
        
        $allowedFields = ['student_id', 'university', 'major', 'academic_year', 'gpa', 
                          'phone', 'bio', 'research_interests', 'skills', 'avatar_url'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $values[] = $userId;
            $sql = "UPDATE student_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        }
        
    } else {
        // Insert new
        $sql = "INSERT INTO student_profiles (user_id, student_id, university, major, academic_year, phone, bio, research_interests) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userId,
            $data['student_id'] ?? '',
            $data['university'] ?? '',
            $data['major'] ?? '',
            $data['academic_year'] ?? '',
            $data['phone'] ?? '',
            $data['bio'] ?? '',
            $data['research_interests'] ?? ''
        ]);
    }
    
    return ['success' => true];
}

/**
 * Update lecturer profile
 */
function updateLecturerProfile($db, $userId, $data) {
    // Validate required fields
    $errors = [];
    
    if (isset($data['lecturer_id']) && strlen($data['lecturer_id']) < 3) {
        $errors[] = 'Lecturer ID must be at least 3 characters';
    }
    
    if (isset($data['degree']) && !in_array($data['degree'], ['bachelor', 'master', 'phd', 'associate_prof', 'professor'])) {
        $errors[] = 'Invalid degree';
    }
    
    if (isset($data['phone']) && !validatePhone($data['phone'])) {
        $errors[] = 'Invalid phone number';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    // Check if profile exists
    $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing
        $fields = [];
        $values = [];
        
        $allowedFields = ['lecturer_id', 'university', 'department', 'degree', 'title',
                          'research_interests', 'publications_count', 'phone', 'office_location',
                          'bio', 'website_url', 'google_scholar_url', 'orcid', 'avatar_url',
                          'available_for_mentoring', 'max_students'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $values[] = $userId;
            $sql = "UPDATE lecturer_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        }
        
    } else {
        // Insert new
        $sql = "INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userId,
            $data['lecturer_id'] ?? '',
            $data['university'] ?? '',
            $data['department'] ?? '',
            $data['degree'] ?? 'bachelor',
            $data['research_interests'] ?? '',
            $data['phone'] ?? ''
        ]);
    }
    
    return ['success' => true];
}

/**
 * Check if profile is complete
 */
function checkProfileCompleteness($db, $userId, $role) {
    if ($role === 'student') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM student_profiles 
            WHERE user_id = ? 
              AND student_id IS NOT NULL AND student_id != ''
              AND university IS NOT NULL AND university != ''
              AND major IS NOT NULL AND major != ''
              AND phone IS NOT NULL AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['complete'] > 0;
        
    } elseif ($role === 'lecturer') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM lecturer_profiles 
            WHERE user_id = ? 
              AND lecturer_id IS NOT NULL AND lecturer_id != ''
              AND university IS NOT NULL AND university != ''
              AND department IS NOT NULL AND department != ''
              AND degree IS NOT NULL
              AND research_interests IS NOT NULL AND research_interests != ''
              AND phone IS NOT NULL AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['complete'] > 0;
    }
    
    return false;
}

/**
 * Log profile update
 */
function logProfileUpdate($db, $userId, $action, $data) {
    try {
        $stmt = $db->prepare("
            INSERT INTO profile_update_logs (user_id, action, field_changed, new_value, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            'multiple',
            json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Failed to log profile update: ' . $e->getMessage());
    }
}

