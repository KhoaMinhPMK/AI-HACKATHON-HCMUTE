-- =====================================================
-- Migration Script: Add Profile System to Existing Database
-- Phiên bản: 1.0
-- Ngày: 2025-11-15
-- =====================================================

USE victoria_ai;

-- Backup reminder
-- RUN THIS BEFORE MIGRATION:
-- mysqldump -u root -p victoria_ai > backup_before_profile_migration_$(date +%Y%m%d).sql

START TRANSACTION;

-- =====================================================
-- Step 1: Alter existing users table
-- =====================================================

-- Check if columns exist, add if not
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'role';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
     AND (table_schema = @dbname)
     AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column role already exists' AS message;",
  "ALTER TABLE users ADD COLUMN role ENUM('student', 'lecturer') DEFAULT NULL COMMENT 'Vai trò người dùng' AFTER display_name;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Add profile_completed
SET @columnname = 'profile_completed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
     AND (table_schema = @dbname)
     AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column profile_completed already exists' AS message;",
  "ALTER TABLE users ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE COMMENT 'Đã hoàn thiện thông tin chưa' AFTER role;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Add phone
SET @columnname = 'phone';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
     AND (table_schema = @dbname)
     AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column phone already exists' AS message;",
  "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại' AFTER profile_completed;"
));
PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- =====================================================
-- Step 2: Create indexes
-- =====================================================

-- Create indexes (ignore error if already exists)
-- MySQL sẽ báo lỗi nếu index đã tồn tại - BỎ QUA lỗi đó
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_profile_completed ON users(profile_completed);

-- =====================================================
-- Step 3: Create new tables (if not exist)
-- =====================================================

CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL COMMENT 'Mã số sinh viên',
    university VARCHAR(255) NOT NULL COMMENT 'Trường đại học',
    major VARCHAR(255) NOT NULL COMMENT 'Chuyên ngành',
    academic_year VARCHAR(20) DEFAULT NULL COMMENT 'Khóa học',
    gpa DECIMAL(3,2) DEFAULT NULL COMMENT 'Điểm GPA',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL COMMENT 'Giới thiệu bản thân',
    research_interests TEXT DEFAULT NULL COMMENT 'Sở thích nghiên cứu',
    skills TEXT DEFAULT NULL COMMENT 'Kỹ năng',
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
    title VARCHAR(100) DEFAULT NULL COMMENT 'Chức danh',
    research_interests TEXT NOT NULL COMMENT 'Lĩnh vực nghiên cứu',
    publications_count INT DEFAULT 0 COMMENT 'Số công bố',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
    office_location VARCHAR(255) DEFAULT NULL COMMENT 'Phòng làm việc',
    avatar_url TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL COMMENT 'Giới thiệu',
    website_url VARCHAR(255) DEFAULT NULL,
    google_scholar_url VARCHAR(255) DEFAULT NULL,
    orcid VARCHAR(50) DEFAULT NULL,
    social_links JSON DEFAULT NULL,
    available_for_mentoring BOOLEAN DEFAULT TRUE,
    max_students INT DEFAULT 5,
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

-- =====================================================
-- Step 4: Migrate existing data (if needed)
-- =====================================================

-- Đánh dấu tất cả user hiện tại là chưa hoàn thiện profile
UPDATE users SET profile_completed = FALSE WHERE role IS NULL;

-- =====================================================
-- Step 5: Verification
-- =====================================================

-- Show summary
SELECT 
    'Migration Summary' as step,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role IS NOT NULL) as users_with_role,
    (SELECT COUNT(*) FROM users WHERE profile_completed = TRUE) as completed_profiles,
    (SELECT COUNT(*) FROM student_profiles) as student_profiles,
    (SELECT COUNT(*) FROM lecturer_profiles) as lecturer_profiles;

COMMIT;

-- =====================================================
-- Rollback Script (if needed)
-- =====================================================
/*
START TRANSACTION;

-- Remove new columns
ALTER TABLE users 
  DROP COLUMN IF EXISTS role,
  DROP COLUMN IF EXISTS profile_completed,
  DROP COLUMN IF EXISTS phone;

-- Drop new tables
DROP TABLE IF EXISTS profile_update_logs;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS lecturer_profiles;

COMMIT;
*/

