-- Search Cache Table
-- Store search results for faster retrieval

CREATE TABLE IF NOT EXISTS search_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(32) NOT NULL UNIQUE,
    query VARCHAR(500) NOT NULL,
    results LONGTEXT NOT NULL,
    source VARCHAR(50) DEFAULT 'semantic_scholar',
    created_at DATETIME NOT NULL,
    last_accessed DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    hit_count INT DEFAULT 1,
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires (expires_at),
    INDEX idx_query (query(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto cleanup old cache (optional cron job)
-- DELETE FROM search_cache WHERE expires_at < NOW();

-- View cache statistics
-- SELECT 
--     query, 
--     hit_count, 
--     created_at, 
--     last_accessed,
--     TIMESTAMPDIFF(SECOND, created_at, last_accessed) as cache_duration_seconds
-- FROM search_cache 
-- ORDER BY hit_count DESC 
-- LIMIT 20;
