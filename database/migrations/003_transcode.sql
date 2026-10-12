SET NAMES utf8mb4;

ALTER TABLE videos
    ADD COLUMN IF NOT EXISTS transcode_status ENUM('none','pending','processing','done','failed') DEFAULT 'none',
    ADD COLUMN IF NOT EXISTS resolutions JSON NULL,
    ADD COLUMN IF NOT EXISTS source_file VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS auto_thumbnail TINYINT(1) DEFAULT 0;

ALTER TABLE videos ADD INDEX IF NOT EXISTS idx_transcode (transcode_status);
