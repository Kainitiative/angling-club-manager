-- Angling Club Manager MVP
-- Schema version: 0.1
-- Database: angling_club_manager
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET foreign_key_checks = 0;

-- Tables will be added next:
-- users
-- clubs
-- club_admins
-- members
-- meetings
-- finances (transactions)
-- competitions
-- applications
-- ownership_transfer_tokens / admin_invites (later)

-- =========================
-- USERS (site accounts)
-- =========================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,

  -- For "Clubs Near You"
  city VARCHAR(120) NULL,

  role ENUM('user','super_admin') NOT NULL DEFAULT 'user',

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- CLUBS
-- =========================
DROP TABLE IF EXISTS clubs;
CREATE TABLE clubs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,

  contact_email VARCHAR(190) NULL,
  logo_url VARCHAR(500) NULL,
  about_text TEXT NULL,
  location_text VARCHAR(255) NULL,

  -- For "Clubs Near You"
  city VARCHAR(120) NULL,

  is_public TINYINT(1) NOT NULL DEFAULT 1,

  -- Billing / Trial
  trial_start_date DATE NOT NULL,
  trial_end_date DATE NOT NULL,
  access_until DATE NOT NULL,

  billing_status ENUM('trial','active','past_due','canceled','expired') NOT NULL DEFAULT 'trial',
  subscription_plan ENUM('monthly') NOT NULL DEFAULT 'monthly',

  stripe_customer_id VARCHAR(80) NULL,
  stripe_subscription_id VARCHAR(80) NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_clubs_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- CLUB ADMINS (2 max enforced in app logic for MVP)
-- =========================
DROP TABLE IF EXISTS club_admins;
CREATE TABLE club_admins (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,

  -- One owner per club
  admin_role ENUM('owner','admin') NOT NULL DEFAULT 'admin',

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_club_admins_club_user (club_id, user_id),

  KEY idx_club_admins_club (club_id),
  KEY idx_club_admins_user (user_id),

  CONSTRAINT fk_club_admins_club
    FOREIGN KEY (club_id) REFERENCES clubs(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_club_admins_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
