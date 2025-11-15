-- =====================================================
-- Victoria AI - Research Monitoring System
-- Tables for tracking, insights, and AI reports
-- =====================================================

USE victoria_ai;

-- =====================================================
-- Table: search_logs (Track mọi tìm kiếm)
-- =====================================================
CREATE TABLE IF NOT EXISTS search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query TEXT NOT NULL COMMENT 'Nội dung search',
    search_type ENUM('papers', 'projects', 'mentors', 'general') DEFAULT 'papers',
    results_count INT DEFAULT 0,
    clicked_results JSON COMMENT 'Array of clicked item IDs',
    filters_used JSON COMMENT 'Filters applied',
    time_spent INT DEFAULT 0 COMMENT 'Seconds spent on results page',
    session_id VARCHAR(64),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id),
    INDEX idx_type (search_type),
    FULLTEXT INDEX idx_query (query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: paper_interactions (Track tương tác với papers)
-- =====================================================
CREATE TABLE IF NOT EXISTS paper_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    paper_id VARCHAR(255) NOT NULL COMMENT 'arXiv ID, DOI, hoặc custom ID',
    paper_title TEXT,
    paper_authors TEXT,
    paper_year INT,
    paper_source VARCHAR(50) COMMENT 'arxiv, semantic_scholar, pubmed',
    
    interaction_type ENUM('view', 'save', 'cite', 'download', 'note') NOT NULL,
    time_spent INT DEFAULT 0 COMMENT 'Seconds spent reading',
    notes TEXT COMMENT 'User notes',
    
    search_query TEXT COMMENT 'Query that led to this paper',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_paper (paper_id),
    INDEX idx_type (interaction_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: student_insights (AI generated insights)
-- =====================================================
CREATE TABLE IF NOT EXISTS student_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    
    insight_type ENUM('daily', 'weekly', 'on_demand', 'milestone') DEFAULT 'daily',
    
    research_direction TEXT COMMENT 'AI phân tích hướng nghiên cứu',
    main_topics JSON COMMENT 'Chủ đề chính đang tìm hiểu',
    search_patterns JSON COMMENT 'Patterns từ search history',
    
    knowledge_gaps JSON COMMENT 'Gaps phát hiện bởi AI',
    recommended_papers JSON COMMENT 'Papers AI gợi ý nên đọc',
    warnings JSON COMMENT 'Cảnh báo về hướng sai hoặc lỗi phổ biến',
    
    progress_score INT DEFAULT 0 COMMENT '0-100',
    coherence_score INT DEFAULT 0 COMMENT 'Search có focused không',
    
    ai_model VARCHAR(50) COMMENT 'gpt-5, claude-opus-4.1',
    tokens_used INT DEFAULT 0,
    
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP COMMENT 'Insight expire sau X ngày',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_generated (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: supervisor_reports (Reports cho giảng viên)
-- =====================================================
CREATE TABLE IF NOT EXISTS supervisor_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    student_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    
    report_type ENUM('progress', 'onboarding', 'milestone', 'final') DEFAULT 'progress',
    
    time_period_start DATE,
    time_period_end DATE,
    
    summary TEXT COMMENT 'Tóm tắt tổng quan',
    research_focus TEXT COMMENT 'Student đang focus vào gì',
    
    activity_stats JSON COMMENT '{searches: 45, papers: 28, time: 45000}',
    search_patterns JSON COMMENT 'Patterns phát hiện',
    
    strengths JSON COMMENT 'Điểm mạnh của student',
    concerns JSON COMMENT 'Điểm cần lưu ý',
    knowledge_gaps JSON COMMENT 'Gaps cần fill',
    
    warnings JSON COMMENT 'Cảnh báo quan trọng',
    must_read_papers JSON COMMENT 'Papers bắt buộc phải đọc',
    
    recommendations JSON COMMENT 'Gợi ý cho giảng viên',
    next_steps TEXT COMMENT 'Bước tiếp theo đề xuất',
    
    overall_score INT COMMENT '0-100',
    progress_level ENUM('excellent', 'good', 'average', 'needs_attention', 'concerning'),
    
    ai_model VARCHAR(50),
    tokens_used INT DEFAULT 0,
    
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at TIMESTAMP NULL,
    
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE SET NULL,
    
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_student (student_id),
    INDEX idx_project (project_id),
    INDEX idx_generated (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: team_activity_feed (Real-time activity log)
-- =====================================================
CREATE TABLE IF NOT EXISTS team_activity_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    
    activity_type ENUM('search', 'paper_view', 'paper_save', 'paper_cite', 'note_add', 'milestone', 'comment') NOT NULL,
    activity_title VARCHAR(255),
    activity_data JSON COMMENT 'Chi tiết activity',
    
    is_public BOOLEAN DEFAULT TRUE COMMENT 'Hiển thị cho team không',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_type (activity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: saved_papers (Papers đã save)
-- =====================================================
CREATE TABLE IF NOT EXISTS saved_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    paper_id VARCHAR(255) NOT NULL,
    paper_title TEXT,
    paper_authors TEXT,
    paper_year INT,
    paper_url TEXT,
    paper_thumbnail TEXT,
    
    folder VARCHAR(100) DEFAULT 'default' COMMENT 'Organize vào folders',
    tags TEXT COMMENT 'User tags',
    notes TEXT COMMENT 'User notes',
    
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, paper_id),
    INDEX idx_user (user_id),
    INDEX idx_folder (folder),
    INDEX idx_saved (saved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Views
-- =====================================================

-- View: Student activity summary (last 7 days)
CREATE OR REPLACE VIEW v_student_activity_7d AS
SELECT 
    u.id as user_id,
    u.display_name,
    u.email,
    COUNT(DISTINCT sl.id) as searches_count,
    COUNT(DISTINCT pi.id) as papers_viewed,
    SUM(pi.time_spent) as total_time_spent,
    COUNT(DISTINCT CASE WHEN pi.interaction_type = 'save' THEN pi.id END) as papers_saved
FROM users u
LEFT JOIN search_logs sl ON u.id = sl.user_id 
    AND sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
LEFT JOIN paper_interactions pi ON u.id = pi.user_id 
    AND pi.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
WHERE u.role = 'student'
GROUP BY u.id;

-- View: Team activity feed (for lecturers)
CREATE OR REPLACE VIEW v_team_feed AS
SELECT 
    taf.*,
    u.display_name as user_name,
    sp.student_id,
    rp.title as project_title
FROM team_activity_feed taf
INNER JOIN users u ON taf.user_id = u.id
LEFT JOIN student_profiles sp ON u.id = sp.user_id
INNER JOIN research_projects rp ON taf.project_id = rp.id
WHERE taf.is_public = TRUE
ORDER BY taf.created_at DESC;

-- =====================================================
-- Stored Procedures
-- =====================================================

DELIMITER //

-- Get student activity summary
DROP PROCEDURE IF EXISTS get_student_summary //

CREATE PROCEDURE get_student_summary(
    IN p_student_id INT,
    IN p_days INT
)
BEGIN
    SELECT 
        COUNT(DISTINCT sl.id) as total_searches,
        COUNT(DISTINCT pi.id) as total_papers,
        SUM(pi.time_spent) as total_time,
        COUNT(DISTINCT CASE WHEN pi.interaction_type = 'save' THEN pi.id END) as papers_saved
    FROM users u
    LEFT JOIN search_logs sl ON u.id = sl.user_id 
        AND sl.created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    LEFT JOIN paper_interactions pi ON u.id = pi.user_id 
        AND pi.created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    WHERE u.id = p_student_id;
END //

DELIMITER ;

-- =====================================================
-- Sample Data
-- =====================================================

-- Sample search logs
SET @student_id = (SELECT id FROM users WHERE role = 'student' LIMIT 1);

INSERT INTO search_logs (user_id, query, search_type, results_count, session_id) VALUES
(@student_id, 'Machine Learning trong Y tế', 'papers', 15, UUID()),
(@student_id, 'Deep Learning for Medical Images', 'papers', 20, UUID()),
(@student_id, 'Computer Vision X-Ray', 'papers', 12, UUID());

-- =====================================================
-- Verification
-- =====================================================
SELECT 'Monitoring tables created!' AS Status;

SHOW TABLES LIKE '%search%';
SHOW TABLES LIKE '%insight%';
SHOW TABLES LIKE '%report%';

