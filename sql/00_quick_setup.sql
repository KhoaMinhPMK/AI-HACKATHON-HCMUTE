-- =====================================================
-- Victoria AI - QUICK SETUP (ALL-IN-ONE)
-- Chạy file này để setup toàn bộ database từ đầu
-- =====================================================

-- =====================================================
-- 1. CREATE DATABASE
-- =====================================================
CREATE DATABASE IF NOT EXISTS victoria_ai
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE victoria_ai;

SELECT 'Database victoria_ai created!' AS Status;

-- =====================================================
-- 2. CREATE BASIC TABLES
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL UNIQUE COMMENT 'Firebase User ID',
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) DEFAULT NULL,
    role ENUM('student', 'lecturer') DEFAULT NULL COMMENT 'Vai trò người dùng',
    profile_completed BOOLEAN DEFAULT FALSE COMMENT 'Đã hoàn thiện thông tin chưa',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
    photo_url TEXT DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0 COMMENT '0=not verified, 1=verified',
    auth_provider ENUM('google', 'password', 'facebook', 'github') NOT NULL DEFAULT 'password',
    is_active TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    metadata JSON DEFAULT NULL COMMENT 'User preferences, settings, etc',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_email (email),
    INDEX idx_auth_provider (auth_provider),
    INDEX idx_users_role (role),
    INDEX idx_users_profile_completed (profile_completed),
    INDEX idx_created_at (created_at),
    INDEX idx_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    firebase_uid VARCHAR(128) NOT NULL,
    access_token TEXT DEFAULT NULL COMMENT 'OAuth access token (if applicable)',
    refresh_token TEXT DEFAULT NULL COMMENT 'OAuth refresh token',
    id_token TEXT DEFAULT NULL COMMENT 'Firebase ID token',
    token_type VARCHAR(50) DEFAULT 'Bearer',
    scope TEXT DEFAULT NULL COMMENT 'OAuth scopes',
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'login, logout, register, update_profile, etc',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
    user_agent TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL COMMENT 'Additional context data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_user_action (user_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(64) DEFAULT NULL COMMENT 'Chat session identifier',
    role ENUM('user', 'ai', 'system') NOT NULL,
    message TEXT NOT NULL,
    tokens_used INT DEFAULT 0 COMMENT 'Number of tokens consumed',
    model VARCHAR(50) DEFAULT NULL COMMENT 'AI model used (gpt-4, etc)',
    metadata JSON DEFAULT NULL COMMENT 'Additional data (images, files, etc)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at),
    INDEX idx_user_session (user_id, session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(20) DEFAULT 'light' COMMENT 'light, dark, auto',
    language VARCHAR(10) DEFAULT 'vi' COMMENT 'vi, en, etc',
    notifications_enabled TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh',
    custom_settings JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Basic tables created!' AS Status;

-- =====================================================
-- 3. CREATE PROFILE TABLES
-- =====================================================
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL COMMENT 'Mã số sinh viên',
    university VARCHAR(255) NOT NULL COMMENT 'Trường đại học',
    major VARCHAR(255) NOT NULL COMMENT 'Chuyên ngành',
    academic_year VARCHAR(20) DEFAULT NULL COMMENT 'Khóa học (VD: 2020, K45)',
    gpa DECIMAL(3,2) DEFAULT NULL COMMENT 'Điểm GPA',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL COMMENT 'Giới thiệu bản thân',
    research_interests TEXT DEFAULT NULL COMMENT 'Sở thích nghiên cứu',
    skills TEXT DEFAULT NULL COMMENT 'Kỹ năng (JSON array)',
    social_links JSON DEFAULT NULL COMMENT 'Links mạng xã hội',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_id (student_id),
    INDEX idx_student_university (university),
    INDEX idx_student_major (major)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lecturer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lecturer_id VARCHAR(20) NOT NULL COMMENT 'Mã giảng viên',
    university VARCHAR(255) NOT NULL COMMENT 'Trường đại học',
    department VARCHAR(255) NOT NULL COMMENT 'Khoa/Bộ môn',
    degree ENUM('bachelor', 'master', 'phd', 'associate_prof', 'professor') NOT NULL COMMENT 'Học vị',
    title VARCHAR(100) DEFAULT NULL COMMENT 'Chức danh (Trưởng khoa, ...)',
    research_interests TEXT NOT NULL COMMENT 'Lĩnh vực nghiên cứu',
    publications_count INT DEFAULT 0 COMMENT 'Số lượng công bố khoa học',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
    office_location VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ phòng làm việc',
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL COMMENT 'Giới thiệu',
    website_url VARCHAR(255) DEFAULT NULL COMMENT 'Website cá nhân',
    google_scholar_url VARCHAR(255) DEFAULT NULL COMMENT 'Link Google Scholar',
    orcid VARCHAR(50) DEFAULT NULL COMMENT 'ORCID ID',
    social_links JSON DEFAULT NULL COMMENT 'Links mạng xã hội',
    available_for_mentoring BOOLEAN DEFAULT TRUE COMMENT 'Sẵn sàng hướng dẫn NCKH',
    max_students INT DEFAULT 5 COMMENT 'Số sinh viên tối đa có thể hướng dẫn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lecturer_id (lecturer_id),
    INDEX idx_lecturer_university (university),
    INDEX idx_lecturer_department (department),
    INDEX idx_lecturer_degree (degree),
    INDEX idx_lecturer_available (available_for_mentoring)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profile_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'create, update, complete',
    field_changed VARCHAR(100) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_profile_logs_user (user_id),
    INDEX idx_profile_logs_action (action),
    INDEX idx_profile_logs_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Profile tables created!' AS Status;

-- =====================================================
-- 4. SAMPLE DATA (Optional)
-- =====================================================
-- Sample student
INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone) VALUES
('sample_student_1', 'student1@example.com', 'Nguyễn Văn A', 'student', TRUE, '0123456789')
ON DUPLICATE KEY UPDATE display_name = display_name;

SET @student_user_id = LAST_INSERT_ID();

INSERT INTO student_profiles (user_id, student_id, university, major, academic_year, phone, bio, research_interests) VALUES
(@student_user_id, '20520001', 'Đại học Bách Khoa TP.HCM', 'Khoa học máy tính', '2020', '0123456789', 
 'Sinh viên năm 3, đam mê AI và Machine Learning', 
 'Machine Learning, Natural Language Processing, Computer Vision')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Sample lecturer
INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone) VALUES
('sample_lecturer_1', 'lecturer1@example.com', 'TS. Trần Thị B', 'lecturer', TRUE, '0987654321')
ON DUPLICATE KEY UPDATE display_name = display_name;

SET @lecturer_user_id = LAST_INSERT_ID();

INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone, available_for_mentoring) VALUES
(@lecturer_user_id, 'GV001', 'Đại học Bách Khoa TP.HCM', 'Khoa Khoa học và Kỹ thuật Máy tính', 'phd',
 'Artificial Intelligence, Machine Learning, Data Science', '0987654321', TRUE)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

SELECT 'Sample data inserted!' AS Status;

-- =====================================================
-- 5. VERIFY SETUP
-- =====================================================
SELECT 'Setup complete!' AS Status;
SELECT '===================' AS Info;

-- Show all tables
SHOW TABLES;

-- Count records
SELECT 
    'users' AS TableName, 
    COUNT(*) AS RecordCount 
FROM users
UNION ALL
SELECT 'student_profiles', COUNT(*) FROM student_profiles
UNION ALL
SELECT 'lecturer_profiles', COUNT(*) FROM lecturer_profiles
UNION ALL
SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL
SELECT 'chat_history', COUNT(*) FROM chat_history
UNION ALL
SELECT 'user_preferences', COUNT(*) FROM user_preferences;

SELECT '✅ Victoria AI Database Setup Complete!' AS Status;

