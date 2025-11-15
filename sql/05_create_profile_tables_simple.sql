-- =====================================================
-- Victoria AI - User Profile System (SIMPLIFIED VERSION)
-- Schema cho Sinh viên và Giảng viên
-- CHỈ DÙNG FILE NÀY NẾU FILE GỐC BÁO LỖI
-- =====================================================

USE victoria_ai;

-- =====================================================
-- Bảng users chính (cập nhật thêm trường)
-- NẾU BÁO LỖI "Duplicate column" thì BỎ QUA, chạy tiếp
-- =====================================================
ALTER TABLE users 
ADD COLUMN role ENUM('student', 'lecturer') DEFAULT NULL COMMENT 'Vai trò người dùng' AFTER display_name;

ALTER TABLE users 
ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE COMMENT 'Đã hoàn thiện thông tin chưa' AFTER role;

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại' AFTER profile_completed;

-- Index cho tìm kiếm nhanh
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_profile_completed ON users(profile_completed);

-- =====================================================
-- Bảng thông tin Sinh viên
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

-- =====================================================
-- Bảng thông tin Giảng viên
-- =====================================================
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

-- =====================================================
-- Bảng lịch sử cập nhật profile (audit log)
-- =====================================================
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

-- =====================================================
-- Dữ liệu mẫu (Sample data)
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

-- =====================================================
-- Hiển thị kết quả
-- =====================================================
SELECT 'Profile tables created successfully!' AS Status;
SHOW TABLES;

