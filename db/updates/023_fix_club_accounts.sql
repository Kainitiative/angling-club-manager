-- Fix club_accounts table - add missing columns
-- Run this SQL in phpMyAdmin

-- Add account_name column (if table uses 'name')
ALTER TABLE club_accounts ADD COLUMN account_name VARCHAR(120) NULL AFTER club_id;

-- Copy data from name to account_name
UPDATE club_accounts SET account_name = name WHERE account_name IS NULL AND name IS NOT NULL;

-- Make account_name not null after data copy
ALTER TABLE club_accounts MODIFY account_name VARCHAR(120) NOT NULL;

-- Add account_type column
ALTER TABLE club_accounts ADD COLUMN account_type ENUM('bank', 'cash', 'paypal', 'stripe', 'other') NOT NULL DEFAULT 'bank' AFTER account_name;

-- Add notes column
ALTER TABLE club_accounts ADD COLUMN notes TEXT NULL AFTER balance;
