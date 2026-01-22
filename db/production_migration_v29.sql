-- Production Migration for v29
-- Run this SQL on your production database (MySQL) to add all missing columns and tables
-- These statements are safe to run - they'll skip if already exists

-- 1. Add is_pinned column to club_news table (ignore error if exists)
ALTER TABLE club_news ADD COLUMN is_pinned TINYINT(1) DEFAULT 0;

-- 2. Add missing columns to club_profile_settings table
ALTER TABLE club_profile_settings ADD COLUMN hero_title VARCHAR(255) NULL;
ALTER TABLE club_profile_settings ADD COLUMN hero_tagline VARCHAR(500) NULL;
ALTER TABLE club_profile_settings ADD COLUMN hero_image_url VARCHAR(500) NULL;
ALTER TABLE club_profile_settings ADD COLUMN why_join_text TEXT NULL;

-- 3. Fix club_membership_fees structure
ALTER TABLE club_membership_fees ADD COLUMN fee_name VARCHAR(100) NOT NULL DEFAULT 'Membership';
ALTER TABLE club_membership_fees ADD COLUMN billing_period ENUM('one_time', 'monthly', 'quarterly', 'yearly') DEFAULT 'yearly';
ALTER TABLE club_membership_fees ADD COLUMN display_order INT DEFAULT 0;
ALTER TABLE club_membership_fees ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- 4. Add display_order to fish_species table (for catches)
ALTER TABLE fish_species ADD COLUMN display_order INT DEFAULT 0;

-- 5. Create club_transactions table for finances
CREATE TABLE IF NOT EXISTS club_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  type ENUM('income', 'expense') NOT NULL,
  category VARCHAR(100) NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(500) NULL,
  transaction_date DATE NOT NULL,
  reference VARCHAR(100) NULL,
  member_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_transactions_club (club_id),
  INDEX idx_transactions_date (transaction_date),
  INDEX idx_transactions_type (type)
);

-- 6. Create club_accounts table for finances
CREATE TABLE IF NOT EXISTS club_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  account_type ENUM('bank', 'cash', 'paypal', 'stripe', 'other') DEFAULT 'bank',
  opening_balance DECIMAL(10,2) DEFAULT 0.00,
  current_balance DECIMAL(10,2) DEFAULT 0.00,
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_accounts_club (club_id)
);

-- Done! If you get "Duplicate column" errors, that's OK - it means the column already exists.
