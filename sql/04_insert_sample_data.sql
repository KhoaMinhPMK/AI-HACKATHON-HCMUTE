-- =============================================
-- Victoria AI - Sample Data (Optional)
-- For testing purposes only
-- =============================================

USE victoria_ai;

-- =============================================
-- Sample User Data
-- =============================================

-- Sample user 1: Google auth
INSERT INTO users (firebase_uid, email, display_name, photo_url, email_verified, auth_provider, metadata) VALUES
('sample_firebase_uid_001', 'test@example.com', 'Test User', 'https://via.placeholder.com/150', 1, 'google', 
 JSON_OBJECT('theme', 'dark', 'language', 'vi', 'onboardingCompleted', true));

-- Sample user 2: Email/password auth
INSERT INTO users (firebase_uid, email, display_name, email_verified, auth_provider) VALUES
('sample_firebase_uid_002', 'demo@victoria-ai.com', 'Demo User', 1, 'password');

-- =============================================
-- Sample User Preferences
-- =============================================
INSERT INTO user_preferences (user_id, theme, language, notifications_enabled) 
SELECT id, 'dark', 'vi', 1 FROM users WHERE email = 'test@example.com';

INSERT INTO user_preferences (user_id, theme, language, notifications_enabled) 
SELECT id, 'light', 'en', 0 FROM users WHERE email = 'demo@victoria-ai.com';

-- =============================================
-- Sample Activity Logs
-- =============================================
INSERT INTO activity_logs (user_id, action, ip_address, user_agent) 
SELECT id, 'login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' 
FROM users WHERE email = 'test@example.com';

INSERT INTO activity_logs (user_id, action, ip_address, metadata) 
SELECT id, 'register', '192.168.1.100', JSON_OBJECT('source', 'google_oauth', 'referrer', 'direct')
FROM users WHERE email = 'test@example.com';

-- =============================================
-- Sample Chat History
-- =============================================
INSERT INTO chat_history (user_id, session_id, role, message, tokens_used, model)
SELECT 
    id, 
    'session_001', 
    'user', 
    'Xin chào Victoria AI!', 
    5,
    NULL
FROM users WHERE email = 'test@example.com';

INSERT INTO chat_history (user_id, session_id, role, message, tokens_used, model)
SELECT 
    id, 
    'session_001', 
    'ai', 
    'Xin chào! Tôi là Victoria AI. Tôi có thể giúp gì cho bạn hôm nay?', 
    15,
    'gpt-4'
FROM users WHERE email = 'test@example.com';

-- Display inserted data counts
SELECT 'Sample data inserted successfully!' AS Status;

SELECT 
    'users' AS TableName, 
    COUNT(*) AS RecordCount 
FROM users
UNION ALL
SELECT 'user_preferences', COUNT(*) FROM user_preferences
UNION ALL
SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL
SELECT 'chat_history', COUNT(*) FROM chat_history
UNION ALL
SELECT 'auth_tokens', COUNT(*) FROM auth_tokens;

-- Display sample users
SELECT 
    id,
    firebase_uid,
    email,
    display_name,
    auth_provider,
    created_at
FROM users;
