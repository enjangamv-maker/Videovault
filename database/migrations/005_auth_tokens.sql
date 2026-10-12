SET NAMES utf8mb4;
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS verification_sent_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS reset_sent_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE users
    ADD INDEX IF NOT EXISTS idx_verification_token (verification_token),
    ADD INDEX IF NOT EXISTS idx_reset_token (reset_token);
