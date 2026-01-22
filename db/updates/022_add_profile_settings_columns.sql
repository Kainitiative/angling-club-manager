-- Add missing columns to club_profile_settings
ALTER TABLE club_profile_settings 
  ADD COLUMN IF NOT EXISTS hero_title VARCHAR(255) NULL AFTER tagline,
  ADD COLUMN IF NOT EXISTS hero_tagline VARCHAR(500) NULL AFTER hero_title,
  ADD COLUMN IF NOT EXISTS hero_image_url VARCHAR(500) NULL AFTER hero_tagline,
  ADD COLUMN IF NOT EXISTS why_join_text TEXT NULL AFTER hero_image_url;

-- Fix club_membership_fees table
ALTER TABLE club_membership_fees 
  ADD COLUMN IF NOT EXISTS billing_period ENUM('yearly', 'monthly', 'once') NOT NULL DEFAULT 'yearly' AFTER amount;

-- Rename name to fee_name if it exists (safe approach)
-- Note: If you get an error on this line, it means fee_name already exists which is fine
-- ALTER TABLE club_membership_fees CHANGE name fee_name VARCHAR(120) NOT NULL;

-- Add is_pinned to club_news if not exists
ALTER TABLE club_news ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_published;
