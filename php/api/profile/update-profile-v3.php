<?php
/**
 * Update Profile API - Version 3 (Ultra Simple)
 * Không dùng validation functions - Chỉ INSERT/UPDATE thuần
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple JSON response helper
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

// Get token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
    jsonResponse(false, 'No authorization token', null, 401);
}

$token = $matches[1];

// Decode token (simplified - không verify signature)
try {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        jsonResponse(false, 'Invalid token format', null, 401);
    }
    
    $payload = json_decode(base64_decode($parts[1]), true);
    $firebaseUid = $payload['user_id'] ?? null;
    
    if (!$firebaseUid) {
        jsonResponse(false, 'Invalid token payload', null, 401);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Token decode error', null, 401);
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON input', null, 400);
}

// Database connection
try {
    $dsn = "mysql:host=localhost;dbname=victoria_ai;charset=utf8mb4";
    $db = new PDO($dsn, 'root', '123456', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    jsonResponse(false, 'Database connection failed', null, 500);
}

try {
    // Get user
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(false, 'User not found', null, 404);
    }
    
    $userId = (int)$user['id'];
    $currentRole = $user['role'];
    $newRole = $input['role'] ?? $currentRole;
    
    // Start transaction
    $db->beginTransaction();
    
    // Update role if needed
    if (!$currentRole && $newRole) {
        if (!in_array($newRole, ['student', 'lecturer'])) {
            $db->rollBack();
            jsonResponse(false, 'Invalid role', null, 400);
        }
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
    }
    
    // Update phone in users table
    if (!empty($input['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$input['phone'], $userId]);
    }
    
    // ========================================
    // UPDATE STUDENT PROFILE
    // ========================================
    if ($newRole === 'student') {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing
            $sql = "UPDATE student_profiles SET 
                    student_id = ?,
                    university = ?,
                    major = ?,
                    academic_year = ?,
                    phone = ?,
                    bio = ?,
                    research_interests = ?
                    WHERE user_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['student_id'] ?? '',
                $input['university'] ?? '',
                $input['major'] ?? '',
                $input['academic_year'] ?? '',
                $input['phone'] ?? '',
                $input['bio'] ?? '',
                $input['research_interests'] ?? '',
                $userId
            ]);
        } else {
            // Insert new
            $sql = "INSERT INTO student_profiles 
                    (user_id, student_id, university, major, academic_year, phone, bio, research_interests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $input['student_id'] ?? '',
                $input['university'] ?? '',
                $input['major'] ?? '',
                $input['academic_year'] ?? '',
                $input['phone'] ?? '',
                $input['bio'] ?? '',
                $input['research_interests'] ?? ''
            ]);
        }
        
        // Check if complete
        $stmt = $db->prepare("
            SELECT 
                (student_id IS NOT NULL AND student_id != '') AND
                (university IS NOT NULL AND university != '') AND
                (major IS NOT NULL AND major != '') AND
                (phone IS NOT NULL AND phone != '') 
                AS is_complete
            FROM student_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $isComplete = (bool)($result['is_complete'] ?? false);
    }
    // ========================================
    // UPDATE LECTURER PROFILE
    // ========================================
    elseif ($newRole === 'lecturer') {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing
            $sql = "UPDATE lecturer_profiles SET 
                    lecturer_id = ?,
                    university = ?,
                    department = ?,
                    degree = ?,
                    research_interests = ?,
                    phone = ?,
                    bio = ?,
                    office_location = ?,
                    publications_count = ?,
                    available_for_mentoring = ?,
                    max_students = ?
                    WHERE user_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['lecturer_id'] ?? '',
                $input['university'] ?? '',
                $input['department'] ?? '',
                $input['degree'] ?? 'bachelor',
                $input['research_interests'] ?? '',
                $input['phone'] ?? '',
                $input['bio'] ?? '',
                $input['office_location'] ?? '',
                (int)($input['publications_count'] ?? 0),
                isset($input['available_for_mentoring']) ? ($input['available_for_mentoring'] ? 1 : 0) : 1,
                (int)($input['max_students'] ?? 5),
                $userId
            ]);
        } else {
            // Insert new
            $sql = "INSERT INTO lecturer_profiles 
                    (user_id, lecturer_id, university, department, degree, research_interests, phone, bio, office_location, publications_count, available_for_mentoring, max_students) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $input['lecturer_id'] ?? '',
                $input['university'] ?? '',
                $input['department'] ?? '',
                $input['degree'] ?? 'bachelor',
                $input['research_interests'] ?? '',
                $input['phone'] ?? '',
                $input['bio'] ?? '',
                $input['office_location'] ?? '',
                (int)($input['publications_count'] ?? 0),
                isset($input['available_for_mentoring']) ? ($input['available_for_mentoring'] ? 1 : 0) : 1,
                (int)($input['max_students'] ?? 5)
            ]);
        }
        
        // Check if complete
        $stmt = $db->prepare("
            SELECT 
                (lecturer_id IS NOT NULL AND lecturer_id != '') AND
                (university IS NOT NULL AND university != '') AND
                (department IS NOT NULL AND department != '') AND
                (degree IS NOT NULL AND degree != '') AND
                (research_interests IS NOT NULL AND research_interests != '') AND
                (phone IS NOT NULL AND phone != '') 
                AS is_complete
            FROM lecturer_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $isComplete = (bool)($result['is_complete'] ?? false);
    } else {
        $db->rollBack();
        jsonResponse(false, 'Role not set', null, 400);
    }
    
    // Update profile_completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
    $stmt->execute([$isComplete, $userId]);
    
    // Commit
    $db->commit();
    
    // Success
    jsonResponse(true, 'Cập nhật thành công', [
        'profile_completed' => $isComplete,
        'message' => $isComplete ? 'Hồ sơ đã hoàn thiện!' : 'Đã lưu. Vui lòng hoàn thiện các trường còn thiếu.'
    ], 200);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update Profile Error: ' . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update Profile Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
}

