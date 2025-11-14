-- =============================================
-- Victoria AI - Additional Indexes for Performance
-- =============================================

USE victoria_ai;

-- =============================================
-- Composite Indexes for Common Queries
-- =============================================

-- Users table - search by email + provider
CREATE INDEX idx_email_provider ON users(email, auth_provider);

-- Users table - active users with recent login
CREATE INDEX idx_active_recent ON users(is_active, last_login);

-- Activity logs - user activities in date range
CREATE INDEX idx_user_date_action ON activity_logs(user_id, created_at, action);

-- Chat history - recent messages by user
CREATE INDEX idx_user_recent_chat ON chat_history(user_id, created_at);

-- Auth tokens - find valid tokens
CREATE INDEX idx_valid_tokens ON auth_tokens(user_id, expires_at);

-- =============================================
-- Full-text Search Indexes (Optional)
-- For searching chat messages
-- =============================================
-- Uncomment if you need full-text search on chat messages
-- ALTER TABLE chat_history ADD FULLTEXT INDEX idx_fulltext_message (message);

-- Display success message
SELECT 'Indexes created successfully!' AS Status;

-- Show indexes for each table
SHOW INDEX FROM users;
SHOW INDEX FROM auth_tokens;
SHOW INDEX FROM activity_logs;
SHOW INDEX FROM chat_history;
