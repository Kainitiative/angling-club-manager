-- Run this to add missing columns to existing users table
-- Use this if you already have data and don't want to recreate the table

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS profile_picture_url VARCHAR(500) NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS dob DATE NULL AFTER profile_picture_url,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER dob,
  ADD COLUMN IF NOT EXISTS town VARCHAR(120) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS country VARCHAR(120) NULL AFTER city,
  ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER country;
