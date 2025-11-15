-- =====================================================
-- Insert Sample Projects - MANUAL VERSION
-- Chạy file này NẾU file gốc báo lỗi lecturer_id null
-- =====================================================

USE victoria_ai;

-- Bước 1: Kiểm tra xem có lecturer nào không
SELECT id, email, display_name, role FROM users WHERE role = 'lecturer';

-- Bước 2: Nếu KHÔNG có lecturer nào, tạo mới
-- TÌM ID CỦA USER CÓ ROLE = LECTURER
-- Nếu không có, chạy đoạn này để tạo:

INSERT INTO users (firebase_uid, email, display_name, role, profile_completed, phone, auth_provider) VALUES
('lecturer_demo_001', 'demo.lecturer@victoria.ai', 'TS. Nguyễn Văn A', 'lecturer', TRUE, '0987654321', 'password');

INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone) VALUES
(LAST_INSERT_ID(), 'GV001', 'Đại học Bách Khoa TP.HCM', 'Khoa KHMT', 'phd', 'AI, Machine Learning, Computer Vision', '0987654321');

-- Bước 3: Lấy ID lecturer (thay @YOUR_LECTURER_ID bằng ID thật)
-- Xem kết quả query bước 1, lấy ID
-- VD: Nếu lecturer có id = 2, dùng 2 thay cho @YOUR_LECTURER_ID

-- CÁCH 1: Tự động lấy ID lecturer đầu tiên
SET @lecturer_id = (SELECT id FROM users WHERE role = 'lecturer' LIMIT 1);

-- CÁCH 2: Hoặc hard-code (thay 2 bằng ID thật của bạn)
-- SET @lecturer_id = 2;

-- Verify có lecturer_id chưa
SELECT @lecturer_id AS lecturer_id_to_use;

-- Nếu NULL, nghĩa là chưa có lecturer → Tạo ở bước 2 trước!

-- Bước 4: Insert projects
INSERT INTO research_projects (
    lecturer_id, 
    title, 
    description, 
    requirements, 
    duration, 
    max_students, 
    tags, 
    requirements_major, 
    status, 
    published_at
) VALUES
-- Project 1
(
    @lecturer_id, 
    'Nghiên cứu ứng dụng AI trong chẩn đoán Y tế', 
    'Phát triển mô hình Deep Learning để phân tích hình ảnh X-Ray và CT Scan, hỗ trợ bác sĩ trong việc chẩn đoán sớm các bệnh lý.',
    'Yêu cầu: Biết Python, có kiến thức về Deep Learning, có khả năng nghiên cứu tài liệu tiếng Anh',
    '6 tháng',
    3,
    'AI,Computer Vision,Healthcare,Deep Learning',
    'Khoa học máy tính,Kỹ thuật y sinh',
    'open',
    CURRENT_TIMESTAMP
),

-- Project 2
(
    @lecturer_id,
    'Phát triển Chatbot hỗ trợ học tập với NLP',
    'Xây dựng hệ thống chatbot thông minh sử dụng Natural Language Processing để hỗ trợ sinh viên trong quá trình học tập.',
    'Yêu cầu: Python, kinh nghiệm với NLP, biết về Transformers/BERT',
    '4 tháng',
    2,
    'NLP,Chatbot,AI,Education',
    'Khoa học máy tính,Công nghệ thông tin',
    'open',
    CURRENT_TIMESTAMP
),

-- Project 3
(
    @lecturer_id,
    'Phân tích dữ liệu lớn với Machine Learning',
    'Nghiên cứu các kỹ thuật Machine Learning để phân tích và dự đoán từ big data trong lĩnh vực thương mại điện tử.',
    'Yêu cầu: Python, SQL, Pandas, Scikit-learn, có hiểu biết về ML algorithms',
    '5 tháng',
    3,
    'Machine Learning,Big Data,E-commerce,Data Analysis',
    'Khoa học máy tính,Khoa học dữ liệu',
    'open',
    CURRENT_TIMESTAMP
);

-- Bước 5: Verify
SELECT '✅ Sample projects inserted!' AS Status;

SELECT 
    id, 
    title, 
    status, 
    max_students, 
    current_students, 
    tags,
    published_at
FROM research_projects
ORDER BY id DESC
LIMIT 5;

