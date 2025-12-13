-- Angling Club Manager MVP
-- Schema version: 0.4
-- Database: angling_club_manager
-- MySQL/MariaDB

-- =========================
-- USERS (site accounts)
-- =========================
DROP TABLE IF EXISTS club_members;
DROP TABLE IF EXISTS club_admins;
DROP TABLE IF EXISTS clubs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_picture_url VARCHAR(500) NULL,
  dob DATE NULL,
  phone VARCHAR(30) NULL,
  town VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
  role ENUM('user', 'super_admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- CLUBS
-- =========================
CREATE TABLE clubs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  contact_email VARCHAR(190) NULL,
  logo_url VARCHAR(500) NULL,
  about_text TEXT NULL,
  
  -- Address fields
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  town VARCHAR(120) NULL,
  county VARCHAR(120) NULL,
  postcode VARCHAR(20) NULL,
  country VARCHAR(120) NULL DEFAULT 'United Kingdom',
  
  -- Legacy location fields
  location_text VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  
  -- Fishing styles (stored as JSON array)
  fishing_styles TEXT NULL,
  
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  trial_start_date DATE NOT NULL,
  trial_end_date DATE NOT NULL,
  access_until DATE NOT NULL,
  billing_status ENUM('trial', 'active', 'past_due', 'canceled', 'expired') NOT NULL DEFAULT 'trial',
  subscription_plan ENUM('monthly') NOT NULL DEFAULT 'monthly',
  stripe_customer_id VARCHAR(80) NULL,
  stripe_subscription_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- CLUB ADMINS (2 max enforced in app logic for MVP)
-- =========================
CREATE TABLE club_admins (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  admin_role ENUM('owner', 'admin') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_club_user (club_id, user_id),
  CONSTRAINT fk_club_admins_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_admins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_club_admins_club ON club_admins(club_id);
CREATE INDEX idx_club_admins_user ON club_admins(user_id);


-- =========================
-- CLUB MEMBERS (users who join clubs)
-- =========================
CREATE TABLE club_members (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  membership_status ENUM('pending', 'active', 'suspended', 'expired') NOT NULL DEFAULT 'pending',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_club_member (club_id, user_id),
  CONSTRAINT fk_club_members_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_club_members_club ON club_members(club_id);
CREATE INDEX idx_club_members_user ON club_members(user_id);
CREATE INDEX idx_club_members_status ON club_members(membership_status);
