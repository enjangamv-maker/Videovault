SET NAMES utf8mb4;

-- FULLTEXT untuk search (aman kalau sudah ada)
ALTER TABLE videos ADD FULLTEXT idx_videos_ft (title, description);

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NULL,
    action VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    payload MEDIUMTEXT,
    last_activity INT UNSIGNED,
    INDEX idx_last (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
