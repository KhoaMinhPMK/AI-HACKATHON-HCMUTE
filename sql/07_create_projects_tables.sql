-- =====================================================
-- Victoria AI - Research Projects & Applications System
-- Hệ thống đề tài NCKH và ứng tuyển (giống Job Board)
-- =====================================================

USE victoria_ai;

-- =====================================================
-- Table: research_projects (Đề tài NCKH)
-- =====================================================
CREATE TABLE IF NOT EXISTS research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL COMMENT 'ID giảng viên đăng đề tài',
    title VARCHAR(255) NOT NULL COMMENT 'Tiêu đề đề tài',
    description TEXT NOT NULL COMMENT 'Mô tả chi tiết',
    requirements TEXT COMMENT 'Yêu cầu về kỹ năng, kiến thức',
    objectives TEXT COMMENT 'Mục tiêu nghiên cứu',
    expected_outcomes TEXT COMMENT 'Kết quả mong đợi',
    
    duration VARCHAR(50) COMMENT 'Thời gian: "3 tháng", "6 tháng", "1 năm"',
    start_date DATE COMMENT 'Ngày bắt đầu dự kiến',
    end_date DATE COMMENT 'Ngày kết thúc dự kiến',
    
    status ENUM('open', 'in_progress', 'completed', 'closed', 'cancelled') DEFAULT 'open',
    
    max_students INT DEFAULT 3 COMMENT 'Số sinh viên tối đa',
    current_students INT DEFAULT 0 COMMENT 'Số sinh viên hiện tại',
    
    tags TEXT COMMENT 'Tags: AI, Machine Learning, Computer Vision (comma separated)',
    
    requirements_major TEXT COMMENT 'Chuyên ngành yêu cầu (comma separated)',
    requirements_skills TEXT COMMENT 'Kỹ năng cần thiết',
    requirements_gpa DECIMAL(3,2) DEFAULT NULL COMMENT 'GPA tối thiểu',
    
    difficulty ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'medium',
    
    funding_available BOOLEAN DEFAULT FALSE COMMENT 'Có hỗ trợ kinh phí không',
    remote_possible BOOLEAN DEFAULT FALSE COMMENT 'Có thể làm remote không',
    
    view_count INT DEFAULT 0 COMMENT 'Số lượt xem',
    application_count INT DEFAULT 0 COMMENT 'Số đơn ứng tuyển',
    
    published_at TIMESTAMP NULL COMMENT 'Thời điểm công bố',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_status (status),
    INDEX idx_published (published_at),
    INDEX idx_created (created_at),
    FULLTEXT INDEX idx_search (title, description, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: applications (Đơn ứng tuyển)
-- =====================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    
    cover_letter TEXT COMMENT 'Thư xin tham gia',
    motivation TEXT COMMENT 'Lý do muốn tham gia',
    relevant_experience TEXT COMMENT 'Kinh nghiệm liên quan',
    
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewer_id INT DEFAULT NULL COMMENT 'Người review (thường là lecturer)',
    
    response_message TEXT COMMENT 'Phản hồi từ giảng viên',
    rejection_reason TEXT COMMENT 'Lý do từ chối (nếu rejected)',
    
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_application (project_id, student_id),
    INDEX idx_project (project_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_applied (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: team_members (Thành viên nhóm nghiên cứu)
-- =====================================================
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    
    role VARCHAR(100) DEFAULT 'Member' COMMENT 'Leader, Member, Data Analyst, ...',
    responsibilities TEXT COMMENT 'Nhiệm vụ cụ thể',
    
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL COMMENT 'Ngày rời nhóm (nếu có)',
    
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_member (project_id, student_id),
    INDEX idx_project (project_id),
    INDEX idx_student (student_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: saved_projects (Đề tài đã lưu - Bookmark)
-- =====================================================
CREATE TABLE IF NOT EXISTS saved_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    project_id INT NOT NULL,
    notes TEXT COMMENT 'Ghi chú cá nhân',
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_save (student_id, project_id),
    INDEX idx_student (student_id),
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: project_views (Lượt xem đề tài - Analytics)
-- =====================================================
CREATE TABLE IF NOT EXISTS project_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    viewer_id INT DEFAULT NULL COMMENT 'User xem (null nếu guest)',
    ip_address VARCHAR(45),
    user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_project (project_id),
    INDEX idx_viewer (viewer_id),
    INDEX idx_viewed (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Views for Quick Queries
-- =====================================================

-- View: Active projects (đang tuyển)
CREATE OR REPLACE VIEW v_active_projects AS
SELECT 
    rp.*,
    u.display_name as lecturer_name,
    lp.department,
    lp.university,
    (rp.max_students - rp.current_students) as remaining_slots
FROM research_projects rp
INNER JOIN users u ON rp.lecturer_id = u.id
INNER JOIN lecturer_profiles lp ON u.id = lp.user_id
WHERE rp.status = 'open'
  AND rp.current_students < rp.max_students
  AND (rp.end_date IS NULL OR rp.end_date > CURDATE());

-- View: Student applications with project info
CREATE OR REPLACE VIEW v_student_applications AS
SELECT 
    a.*,
    rp.title as project_title,
    rp.status as project_status,
    u.display_name as lecturer_name,
    lp.university as lecturer_university
FROM applications a
INNER JOIN research_projects rp ON a.project_id = rp.id
INNER JOIN users u ON rp.lecturer_id = u.id
INNER JOIN lecturer_profiles lp ON u.id = lp.user_id;

-- View: Lecturer's pending applications
CREATE OR REPLACE VIEW v_lecturer_applications AS
SELECT 
    a.*,
    rp.title as project_title,
    u.display_name as student_name,
    sp.student_id as student_code,
    sp.major,
    sp.university as student_university,
    sp.gpa
FROM applications a
INNER JOIN research_projects rp ON a.project_id = rp.id
INNER JOIN users u ON a.student_id = u.id
INNER JOIN student_profiles sp ON u.id = sp.user_id
WHERE a.status = 'pending';

-- =====================================================
-- Stored Procedures
-- =====================================================

DELIMITER //

-- Procedure: Apply to project
DROP PROCEDURE IF EXISTS apply_to_project //

CREATE PROCEDURE apply_to_project(
    IN p_project_id INT,
    IN p_student_id INT,
    IN p_cover_letter TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_max_students INT;
    DECLARE v_current_students INT;
    DECLARE v_status VARCHAR(20);
    
    -- Check if project exists and is open
    SELECT max_students, current_students, status 
    INTO v_max_students, v_current_students, v_status
    FROM research_projects 
    WHERE id = p_project_id;
    
    IF v_status IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Project not found';
    ELSEIF v_status != 'open' THEN
        SET p_success = FALSE;
        SET p_message = 'Project is not accepting applications';
    ELSEIF v_current_students >= v_max_students THEN
        SET p_success = FALSE;
        SET p_message = 'Project is full';
    ELSE
        -- Insert application
        INSERT INTO applications (project_id, student_id, cover_letter)
        VALUES (p_project_id, p_student_id, p_cover_letter);
        
        -- Update application count
        UPDATE research_projects 
        SET application_count = application_count + 1
        WHERE id = p_project_id;
        
        SET p_success = TRUE;
        SET p_message = 'Application submitted successfully';
    END IF;
END //

-- Procedure: Accept application
DROP PROCEDURE IF EXISTS accept_application //

CREATE PROCEDURE accept_application(
    IN p_application_id INT,
    IN p_reviewer_id INT,
    IN p_response_message TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_project_id INT;
    DECLARE v_student_id INT;
    DECLARE v_current_students INT;
    DECLARE v_max_students INT;
    
    -- Get application info
    SELECT project_id, student_id 
    INTO v_project_id, v_student_id
    FROM applications 
    WHERE id = p_application_id;
    
    -- Check project capacity
    SELECT current_students, max_students
    INTO v_current_students, v_max_students
    FROM research_projects
    WHERE id = v_project_id;
    
    IF v_current_students >= v_max_students THEN
        SET p_success = FALSE;
        SET p_message = 'Project is full';
    ELSE
        -- Update application status
        UPDATE applications 
        SET status = 'accepted',
            reviewed_at = CURRENT_TIMESTAMP,
            reviewer_id = p_reviewer_id,
            response_message = p_response_message
        WHERE id = p_application_id;
        
        -- Add to team members
        INSERT INTO team_members (project_id, student_id)
        VALUES (v_project_id, v_student_id);
        
        -- Update project current_students count
        UPDATE research_projects 
        SET current_students = current_students + 1
        WHERE id = v_project_id;
        
        SET p_success = TRUE;
        SET p_message = 'Application accepted';
    END IF;
END //

DELIMITER ;

-- =====================================================
-- Triggers
-- =====================================================

DELIMITER //

-- Auto update project current_students khi thêm team member
DROP TRIGGER IF EXISTS after_team_member_insert //

CREATE TRIGGER after_team_member_insert
AFTER INSERT ON team_members
FOR EACH ROW
BEGIN
    UPDATE research_projects 
    SET current_students = (
        SELECT COUNT(*) 
        FROM team_members 
        WHERE project_id = NEW.project_id AND is_active = TRUE
    )
    WHERE id = NEW.project_id;
END //

-- Auto update application count khi có đơn mới
DROP TRIGGER IF EXISTS after_application_insert //

CREATE TRIGGER after_application_insert
AFTER INSERT ON applications
FOR EACH ROW
BEGIN
    UPDATE research_projects 
    SET application_count = application_count + 1
    WHERE id = NEW.project_id;
END //

DELIMITER ;

-- =====================================================
-- Sample Data
-- =====================================================

-- Tạo lecturer mẫu nếu chưa có
INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone) VALUES
('sample_lecturer_demo', 'lecturer.demo@victoria.ai', 'TS. Nguyễn Văn Demo', 'lecturer', TRUE, '0987654321')
ON DUPLICATE KEY UPDATE display_name = display_name;

SET @lecturer_user_id = LAST_INSERT_ID();

-- Tạo lecturer profile nếu chưa có
INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone, available_for_mentoring) VALUES
(@lecturer_user_id, 'GV_DEMO', 'Đại học Bách Khoa TP.HCM', 'Khoa Khoa học và Kỹ thuật Máy tính', 'phd',
 'Artificial Intelligence, Machine Learning, Computer Vision', '0987654321', TRUE)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Lấy lecturer_user_id để dùng cho projects
SET @lecturer_user_id = (SELECT id FROM users WHERE email = 'lecturer.demo@victoria.ai');

-- Insert sample projects
INSERT INTO research_projects (lecturer_id, title, description, requirements, duration, max_students, tags, requirements_major, status, published_at) VALUES
(@lecturer_user_id, 
 'Nghiên cứu ứng dụng AI trong chẩn đoán Y tế', 
 'Phát triển mô hình Deep Learning để phân tích hình ảnh X-Ray và CT Scan, hỗ trợ bác sĩ trong việc chẩn đoán sớm các bệnh lý.',
 'Yêu cầu: Biết Python, có kiến thức về Deep Learning, có khả năng nghiên cứu tài liệu tiếng Anh',
 '6 tháng',
 3,
 'AI,Computer Vision,Healthcare,Deep Learning',
 'Khoa học máy tính,Kỹ thuật y sinh',
 'open',
 CURRENT_TIMESTAMP),

(@lecturer_user_id,
 'Phát triển Chatbot hỗ trợ học tập với NLP',
 'Xây dựng hệ thống chatbot thông minh sử dụng Natural Language Processing để hỗ trợ sinh viên trong quá trình học tập.',
 'Yêu cầu: Python, kinh nghiệm với NLP, biết về Transformers/BERT',
 '4 tháng',
 2,
 'NLP,Chatbot,AI,Education',
 'Khoa học máy tính,Công nghệ thông tin',
 'open',
 CURRENT_TIMESTAMP),

(@lecturer_user_id,
 'Phân tích dữ liệu lớn với Machine Learning',
 'Nghiên cứu các kỹ thuật Machine Learning để phân tích và dự đoán từ big data trong lĩnh vực thương mại điện tử.',
 'Yêu cầu: Python, SQL, Pandas, Scikit-learn, có hiểu biết về ML algorithms',
 '5 tháng',
 3,
 'Machine Learning,Big Data,E-commerce,Data Analysis',
 'Khoa học máy tính,Khoa học dữ liệu',
 'open',
 CURRENT_TIMESTAMP);

-- Verify insert
SELECT 'Sample projects inserted!' AS Status;
SELECT CONCAT('Created ', ROW_COUNT(), ' projects for lecturer ID: ', @lecturer_user_id) AS Result;

-- =====================================================
-- Verification
-- =====================================================
SELECT 'Projects tables created successfully!' AS Status;

SELECT 
    'research_projects' AS TableName, 
    COUNT(*) AS RecordCount 
FROM research_projects
UNION ALL
SELECT 'applications', COUNT(*) FROM applications
UNION ALL
SELECT 'team_members', COUNT(*) FROM team_members
UNION ALL
SELECT 'saved_projects', COUNT(*) FROM saved_projects;

-- Show sample projects
SELECT id, title, status, max_students, current_students, tags
FROM research_projects;

