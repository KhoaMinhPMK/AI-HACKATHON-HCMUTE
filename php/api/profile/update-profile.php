<?php
/**
 * Update User Profile API - REWRITTEN
 * Version 2.0 - Simplified and Fixed
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include dependencies
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    sendError('Missing authorization token', 401);
}

$firebaseToken = $matches[1];

// Verify Firebase token (simplified)
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
    sendError('Token verification failed', 401);
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON input', 400);
}

// Connect to database
try {
    $db = getDBConnection();
} catch (Exception $e) {
    sendError('Database connection failed', 500);
}

try {
    // Get current user
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    $role = $input['role'] ?? $user['role'];
    
    // Start transaction
    $db->beginTransaction();
    
    // Update role if not set
    if (!$user['role'] && $role) {
        if (!in_array($role, ['student', 'lecturer'])) {
            $db->rollBack();
            sendError('Invalid role', 400);
        }
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
    }
    
    // Update phone in users table
    if (isset($input['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$input['phone'], $userId]);
    }
    
    // Update profile based on role
    if ($role === 'student') {
        // Check if profile exists
        $stmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetch();
        
        if ($profileExists) {
            // UPDATE existing profile
            $updates = [];
            $values = [];
            
            if (isset($input['student_id'])) { $updates[] = "student_id = ?"; $values[] = $input['student_id']; }
            if (isset($input['university'])) { $updates[] = "university = ?"; $values[] = $input['university']; }
            if (isset($input['major'])) { $updates[] = "major = ?"; $values[] = $input['major']; }
            if (isset($input['academic_year'])) { $updates[] = "academic_year = ?"; $values[] = $input['academic_year']; }
            if (isset($input['phone'])) { $updates[] = "phone = ?"; $values[] = $input['phone']; }
            if (isset($input['bio'])) { $updates[] = "bio = ?"; $values[] = $input['bio']; }
            if (isset($input['research_interests'])) { $updates[] = "research_interests = ?"; $values[] = $input['research_interests']; }
            
            if (!empty($updates)) {
                $values[] = $userId;
                $sql = "UPDATE student_profiles SET " . implode(', ', $updates) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
        } else {
            // INSERT new profile
            $sql = "INSERT INTO student_profiles (user_id, student_id, university, major, academic_year, phone, bio, research_interests) 
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
        
        // Check completeness
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM student_profiles 
            WHERE user_id = ? 
              AND student_id != ''
              AND university != ''
              AND major != ''
              AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isComplete = $result['complete'] > 0;
        
    } elseif ($role === 'lecturer') {
        // Check if profile exists
        $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetch();
        
        if ($profileExists) {
            // UPDATE existing profile
            $updates = [];
            $values = [];
            
            if (isset($input['lecturer_id'])) { $updates[] = "lecturer_id = ?"; $values[] = $input['lecturer_id']; }
            if (isset($input['university'])) { $updates[] = "university = ?"; $values[] = $input['university']; }
            if (isset($input['department'])) { $updates[] = "department = ?"; $values[] = $input['department']; }
            if (isset($input['degree'])) { $updates[] = "degree = ?"; $values[] = $input['degree']; }
            if (isset($input['research_interests'])) { $updates[] = "research_interests = ?"; $values[] = $input['research_interests']; }
            if (isset($input['phone'])) { $updates[] = "phone = ?"; $values[] = $input['phone']; }
            if (isset($input['bio'])) { $updates[] = "bio = ?"; $values[] = $input['bio']; }
            if (isset($input['office_location'])) { $updates[] = "office_location = ?"; $values[] = $input['office_location']; }
            if (isset($input['publications_count'])) { $updates[] = "publications_count = ?"; $values[] = $input['publications_count']; }
            if (isset($input['available_for_mentoring'])) { $updates[] = "available_for_mentoring = ?"; $values[] = $input['available_for_mentoring'] ? 1 : 0; }
            if (isset($input['max_students'])) { $updates[] = "max_students = ?"; $values[] = $input['max_students']; }
            
            if (!empty($updates)) {
                $values[] = $userId;
                $sql = "UPDATE lecturer_profiles SET " . implode(', ', $updates) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
        } else {
            // INSERT new profile
            $sql = "INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $input['lecturer_id'] ?? '',
                $input['university'] ?? '',
                $input['department'] ?? '',
                $input['degree'] ?? 'bachelor',
                $input['research_interests'] ?? '',
                $input['phone'] ?? ''
            ]);
        }
        
        // Check completeness
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM lecturer_profiles 
            WHERE user_id = ? 
              AND lecturer_id != ''
              AND university != ''
              AND department != ''
              AND degree != ''
              AND research_interests != ''
              AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isComplete = $result['complete'] > 0;
        
    } else {
        $db->rollBack();
        sendError('Role not set', 400);
    }
    
    // Update profile_completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
    $stmt->execute([$isComplete, $userId]);
    
    // Commit transaction
    $db->commit();
    
    // Success response
    sendSuccess([
        'profile_completed' => $isComplete,
        'message' => $isComplete ? 'Hồ sơ đã hoàn thiện!' : 'Đã lưu. Vui lòng hoàn thiện các trường còn thiếu.'
    ], 'Cập nhật thành công');
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update profile error: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update profile error: ' . $e->getMessage());
    sendError('Server error: ' . $e->getMessage(), 500);
}
