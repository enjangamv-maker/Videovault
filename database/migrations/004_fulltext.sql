SET NAMES utf8mb4;
SET @exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'videos' 
               AND INDEX_NAME = 'idx_videos_fulltext');
SET @sql = IF(@exists = 0,
    'ALTER TABLE videos ADD FULLTEXT INDEX idx_videos_fulltext (title, description)',
    'SELECT "sudah ada" AS s');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
