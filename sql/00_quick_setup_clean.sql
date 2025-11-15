-- =====================================================
-- Victoria AI - CLEAN QUICK SETUP
-- File đơn giản nhất - không có phần kiểm tra phức tạp
-- =====================================================

DROP DATABASE IF EXISTS victoria_ai;

CREATE DATABASE victoria_ai
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE victoria_ai;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) DEFAULT NULL,
    role ENUM('student', 'lecturer') DEFAULT NULL,
    profile_completed BOOLEAN DEFAULT FALSE,
    phone VARCHAR(20) DEFAULT NULL,
    photo_url TEXT DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    auth_provider ENUM('google', 'password', 'facebook', 'github') NOT NULL DEFAULT 'password',
    is_active TINYINT(1) DEFAULT 1,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_firebase_uid ON users(firebase_uid);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_role ON users(role);
CREATE INDEX idx_profile_completed ON users(profile_completed);

-- =====================================================
-- AUTH TOKENS TABLE
-- =====================================================
CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    firebase_uid VARCHAR(128) NOT NULL,
    access_token TEXT DEFAULT NULL,
    refresh_token TEXT DEFAULT NULL,
    id_token TEXT DEFAULT NULL,
    token_type VARCHAR(50) DEFAULT 'Bearer',
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_id ON auth_tokens(user_id);

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_id ON activity_logs(user_id);
CREATE INDEX idx_action ON activity_logs(action);

-- =====================================================
-- CHAT HISTORY TABLE
-- =====================================================
CREATE TABLE chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(64) DEFAULT NULL,
    role ENUM('user', 'ai', 'system') NOT NULL,
    message TEXT NOT NULL,
    tokens_used INT DEFAULT 0,
    model VARCHAR(50) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_id ON chat_history(user_id);
CREATE INDEX idx_session_id ON chat_history(session_id);

-- =====================================================
-- USER PREFERENCES TABLE
-- =====================================================
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'vi',
    notifications_enabled TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    timezone VARCHAR(50) DEFAULT 'Asia/Ho_Chi_Minh',
    custom_settings JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STUDENT PROFILES TABLE
-- =====================================================
CREATE TABLE student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    university VARCHAR(255) NOT NULL,
    major VARCHAR(255) NOT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    gpa DECIMAL(3,2) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    research_interests TEXT DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    social_links JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_student_university ON student_profiles(university);
CREATE INDEX idx_student_major ON student_profiles(major);

-- =====================================================
-- LECTURER PROFILES TABLE
-- =====================================================
CREATE TABLE lecturer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lecturer_id VARCHAR(20) NOT NULL,
    university VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    degree ENUM('bachelor', 'master', 'phd', 'associate_prof', 'professor') NOT NULL,
    title VARCHAR(100) DEFAULT NULL,
    research_interests TEXT NOT NULL,
    publications_count INT DEFAULT 0,
    phone VARCHAR(20) DEFAULT NULL,
    office_location VARCHAR(255) DEFAULT NULL,
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    google_scholar_url VARCHAR(255) DEFAULT NULL,
    orcid VARCHAR(50) DEFAULT NULL,
    social_links JSON DEFAULT NULL,
    available_for_mentoring BOOLEAN DEFAULT TRUE,
    max_students INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lecturer_id (lecturer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_lecturer_university ON lecturer_profiles(university);
CREATE INDEX idx_lecturer_department ON lecturer_profiles(department);
CREATE INDEX idx_lecturer_degree ON lecturer_profiles(degree);

-- =====================================================
-- PROFILE UPDATE LOGS TABLE
-- =====================================================
CREATE TABLE profile_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_changed VARCHAR(100) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_profile_logs_user ON profile_update_logs(user_id);
CREATE INDEX idx_profile_logs_action ON profile_update_logs(action);

-- =====================================================
-- SAMPLE DATA
-- =====================================================
INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone) VALUES
('sample_student_1', 'student1@example.com', 'Nguyễn Văn A', 'student', TRUE, '0123456789');

INSERT INTO student_profiles (user_id, student_id, university, major, phone, bio, research_interests) VALUES
(LAST_INSERT_ID(), '20520001', 'Đại học Bách Khoa TP.HCM', 'Khoa học máy tính', '0123456789', 
 'Sinh viên năm 3, đam mê AI', 'Machine Learning, AI');

INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone) VALUES
('sample_lecturer_1', 'lecturer1@example.com', 'TS. Trần Thị B', 'lecturer', TRUE, '0987654321');

INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone) VALUES
(LAST_INSERT_ID(), 'GV001', 'Đại học Bách Khoa TP.HCM', 'Khoa KHMT', 'phd', 'AI, Machine Learning', '0987654321');

-- =====================================================
-- DONE
-- =====================================================
SELECT 'Database setup complete!' AS status;
SHOW TABLES;

