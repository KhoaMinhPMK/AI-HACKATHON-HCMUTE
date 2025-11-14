-- =============================================
-- Victoria AI - Tables Creation Script
-- =============================================

USE victoria_ai;

-- =============================================
-- Table: users
-- Stores user account information synced from Firebase
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL UNIQUE COMMENT 'Firebase User ID',
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) DEFAULT NULL,
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
    INDEX idx_created_at (created_at),
    INDEX idx_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: auth_tokens
-- Stores Firebase tokens and OAuth tokens
-- =============================================
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

-- =============================================
-- Table: activity_logs
-- Logs user activities for analytics and security
-- =============================================
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

-- =============================================
-- Table: chat_history
-- Stores chat messages between user and AI
-- =============================================
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

-- =============================================
-- Table: user_preferences
-- Stores detailed user settings and preferences
-- =============================================
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

-- Display success message
SELECT 'All tables created successfully!' AS Status;

-- Show tables
SHOW TABLES;
