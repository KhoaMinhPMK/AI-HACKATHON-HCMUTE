-- =====================================================
-- Victoria AI - User Profile System
-- Schema cho Sinh viên và Giảng viên
-- =====================================================

USE victoria_ai;

-- Bảng users chính (cập nhật thêm trường)
-- Kiểm tra và thêm cột 'role' nếu chưa có
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'role';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_name = @tablename
     AND table_schema = @dbname
     AND column_name = @columnname
  ) > 0,
  "SELECT 'Column role already exists' AS message;",
  "ALTER TABLE users ADD COLUMN role ENUM('student', 'lecturer') DEFAULT NULL COMMENT 'Vai trò người dùng' AFTER display_name;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Kiểm tra và thêm cột 'profile_completed' nếu chưa có
SET @columnname = 'profile_completed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_name = @tablename
     AND table_schema = @dbname
     AND column_name = @columnname
  ) > 0,
  "SELECT 'Column profile_completed already exists' AS message;",
  "ALTER TABLE users ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE COMMENT 'Đã hoàn thiện thông tin chưa' AFTER role;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Kiểm tra và thêm cột 'phone' nếu chưa có
SET @columnname = 'phone';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_name = @tablename
     AND table_schema = @dbname
     AND column_name = @columnname
  ) > 0,
  "SELECT 'Column phone already exists' AS message;",
  "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại' AFTER profile_completed;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Index cho tìm kiếm nhanh
DROP INDEX IF EXISTS idx_users_role ON users;
CREATE INDEX idx_users_role ON users(role);

DROP INDEX IF EXISTS idx_users_profile_completed ON users;
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
-- Views để truy vấn dễ dàng
-- =====================================================

-- View: Danh sách sinh viên đầy đủ
CREATE OR REPLACE VIEW v_students AS
SELECT 
    u.id as user_id,
    u.firebase_uid,
    u.email,
    u.display_name,
    u.profile_completed,
    sp.*
FROM users u
INNER JOIN student_profiles sp ON u.id = sp.user_id
WHERE u.role = 'student';

-- View: Danh sách giảng viên đầy đủ
CREATE OR REPLACE VIEW v_lecturers AS
SELECT 
    u.id as user_id,
    u.firebase_uid,
    u.email,
    u.display_name,
    u.profile_completed,
    lp.*
FROM users u
INNER JOIN lecturer_profiles lp ON u.id = lp.user_id
WHERE u.role = 'lecturer';

-- View: Giảng viên đang nhận sinh viên
CREATE OR REPLACE VIEW v_available_lecturers AS
SELECT 
    u.id as user_id,
    u.display_name,
    u.email,
    lp.university,
    lp.department,
    lp.degree,
    lp.research_interests,
    lp.publications_count,
    lp.max_students,
    COUNT(DISTINCT rp.id) as current_students
FROM users u
INNER JOIN lecturer_profiles lp ON u.id = lp.user_id
LEFT JOIN research_projects rp ON u.id = rp.supervisor_id
WHERE u.role = 'lecturer' 
  AND lp.available_for_mentoring = TRUE
GROUP BY u.id
HAVING current_students < lp.max_students;

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
-- Stored Procedures
-- =====================================================

-- Procedure: Kiểm tra profile có đầy đủ không
DELIMITER //

DROP PROCEDURE IF EXISTS check_profile_complete //

CREATE PROCEDURE check_profile_complete(IN p_user_id INT)
BEGIN
    DECLARE v_role VARCHAR(20);
    DECLARE v_complete BOOLEAN DEFAULT FALSE;
    
    SELECT role INTO v_role FROM users WHERE id = p_user_id;
    
    IF v_role = 'student' THEN
        SELECT COUNT(*) > 0 INTO v_complete
        FROM student_profiles
        WHERE user_id = p_user_id
          AND student_id IS NOT NULL
          AND university IS NOT NULL
          AND major IS NOT NULL
          AND phone IS NOT NULL;
    ELSEIF v_role = 'lecturer' THEN
        SELECT COUNT(*) > 0 INTO v_complete
        FROM lecturer_profiles
        WHERE user_id = p_user_id
          AND lecturer_id IS NOT NULL
          AND university IS NOT NULL
          AND department IS NOT NULL
          AND degree IS NOT NULL
          AND research_interests IS NOT NULL
          AND phone IS NOT NULL;
    END IF;
    
    UPDATE users SET profile_completed = v_complete WHERE id = p_user_id;
    SELECT v_complete as is_complete;
END //

DELIMITER ;

-- =====================================================
-- Triggers
-- =====================================================

-- Trigger: Tự động check complete khi update student profile
DELIMITER //

DROP TRIGGER IF EXISTS after_student_profile_update //

CREATE TRIGGER after_student_profile_update
AFTER UPDATE ON student_profiles
FOR EACH ROW
BEGIN
    CALL check_profile_complete(NEW.user_id);
END //

-- Trigger: Tự động check complete khi update lecturer profile
DROP TRIGGER IF EXISTS after_lecturer_profile_update //

CREATE TRIGGER after_lecturer_profile_update
AFTER UPDATE ON lecturer_profiles
FOR EACH ROW
BEGIN
    CALL check_profile_complete(NEW.user_id);
END //

DELIMITER ;

-- =====================================================
-- Kết thúc script
-- =====================================================

