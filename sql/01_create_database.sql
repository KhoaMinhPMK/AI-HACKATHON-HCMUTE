-- =============================================
-- Victoria AI - Database Creation Script
-- =============================================
-- VPS: https://pma.bkuteam.site
-- User: root | Pass: 123456
-- =============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS victoria_ai
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Use the database
USE victoria_ai;

-- Display success message
SELECT 'Database victoria_ai created successfully!' AS Status;
