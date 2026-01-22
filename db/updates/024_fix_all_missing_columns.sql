-- Fix all missing columns for MySQL production database

-- Add is_pinned column to club_news if missing
ALTER TABLE club_news ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) NOT NULL DEFAULT 0;

-- Add display_order column to fish_species if missing  
ALTER TABLE fish_species ADD COLUMN IF NOT EXISTS display_order INT NOT NULL DEFAULT 0;

-- Add hero columns to clubs if missing
ALTER TABLE clubs ADD COLUMN IF NOT EXISTS hero_title VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN IF NOT EXISTS hero_tagline VARCHAR(500) NULL;

-- Add fee_name column to membership_fees if it exists
-- Note: Run this only if membership_fees table exists
-- ALTER TABLE membership_fees ADD COLUMN IF NOT EXISTS fee_name VARCHAR(100) NULL;

-- Create club_messages table if it doesn't exist (for MySQL)
CREATE TABLE IF NOT EXISTS club_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NULL,
  subject VARCHAR(255) NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_msg_club (club_id),
  INDEX idx_msg_sender (sender_id),
  INDEX idx_msg_recipient (recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
