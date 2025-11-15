<?php
/**
 * Update Profile - ABSOLUTE MINIMAL VERSION
 * Chỉ INSERT/UPDATE thuần túy, không có gì thêm
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Function đơn giản để response
function respond($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Only POST allowed');
}

// Get input
$input = @file_get_contents('php://input');
if (!$input) {
    respond(false, 'No input data');
}

$data = @json_decode($input, true);
if (!$data) {
    respond(false, 'Invalid JSON');
}

// Get auth header
$headers = @getallheaders();
$auth = $headers['Authorization'] ?? '';

if (empty($auth)) {
    respond(false, 'No authorization');
}

// Extract token
if (!preg_match('/Bearer\s+(.+)$/', $auth, $m)) {
    respond(false, 'Invalid auth format');
}

$token = $m[1];

// Decode token (simplified - no verify)
$parts = explode('.', $token);
if (count($parts) !== 3) {
    respond(false, 'Invalid token');
}

$payload = @json_decode(@base64_decode($parts[1]), true);
$firebaseUid = $payload['user_id'] ?? null;

if (!$firebaseUid) {
    respond(false, 'Invalid token payload');
}

// Connect DB
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=victoria_ai;charset=utf8mb4", 
        "root", 
        "123456",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    respond(false, 'DB connection failed');
}

// Get user
try {
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respond(false, 'User not found');
    }
    
    $userId = $user['id'];
    $role = $data['role'] ?? $user['role'];
    
    // Update role nếu chưa có
    if (!$user['role'] && $role) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
    }
    
    // Update phone
    if (isset($data['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$data['phone'], $userId]);
    }
    
    // UPDATE STUDENT
    if ($role === 'student') {
        // Check exists
        $stmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            // UPDATE
            $sql = "UPDATE student_profiles SET 
                    student_id = ?, university = ?, major = ?, 
                    phone = ?, bio = ?, research_interests = ?
                    WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['student_id'] ?? '',
                $data['university'] ?? '',
                $data['major'] ?? '',
                $data['phone'] ?? '',
                $data['bio'] ?? '',
                $data['research_interests'] ?? '',
                $userId
            ]);
        } else {
            // INSERT
            $sql = "INSERT INTO student_profiles 
                    (user_id, student_id, university, major, phone, bio, research_interests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $data['student_id'] ?? '',
                $data['university'] ?? '',
                $data['major'] ?? '',
                $data['phone'] ?? '',
                $data['bio'] ?? '',
                $data['research_interests'] ?? ''
            ]);
        }
    }
    // UPDATE LECTURER
    elseif ($role === 'lecturer') {
        // Check exists
        $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            // UPDATE
            $sql = "UPDATE lecturer_profiles SET 
                    lecturer_id = ?, university = ?, department = ?, 
                    degree = ?, research_interests = ?, phone = ?
                    WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['lecturer_id'] ?? '',
                $data['university'] ?? '',
                $data['department'] ?? '',
                $data['degree'] ?? 'bachelor',
                $data['research_interests'] ?? '',
                $data['phone'] ?? '',
                $userId
            ]);
        } else {
            // INSERT
            $sql = "INSERT INTO lecturer_profiles 
                    (user_id, lecturer_id, university, department, degree, research_interests, phone) 
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
    }
    
    // Update completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    respond(true, 'Cập nhật thành công', [
        'profile_completed' => true,
        'message' => 'Hồ sơ đã hoàn thiện!'
    ]);
    
} catch (Exception $e) {
    respond(false, 'Error: ' . $e->getMessage());
}

