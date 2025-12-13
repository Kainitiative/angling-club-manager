-- Angling Club Manager MVP
-- Schema version: 0.1
-- Database: angling_club_manager
-- Adapted for PostgreSQL

-- =========================
-- USERS (site accounts)
-- =========================
DROP TABLE IF EXISTS club_admins CASCADE;
DROP TABLE IF EXISTS clubs CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  city VARCHAR(120) NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'super_admin')),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);


-- =========================
-- CLUBS
-- =========================
CREATE TABLE clubs (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  contact_email VARCHAR(190) NULL,
  logo_url VARCHAR(500) NULL,
  about_text TEXT NULL,
  location_text VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  is_public SMALLINT NOT NULL DEFAULT 1,
  trial_start_date DATE NOT NULL,
  trial_end_date DATE NOT NULL,
  access_until DATE NOT NULL,
  billing_status VARCHAR(20) NOT NULL DEFAULT 'trial' CHECK (billing_status IN ('trial', 'active', 'past_due', 'canceled', 'expired')),
  subscription_plan VARCHAR(20) NOT NULL DEFAULT 'monthly' CHECK (subscription_plan IN ('monthly')),
  stripe_customer_id VARCHAR(80) NULL,
  stripe_subscription_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);


-- =========================
-- CLUB ADMINS (2 max enforced in app logic for MVP)
-- =========================
CREATE TABLE club_admins (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  admin_role VARCHAR(20) NOT NULL DEFAULT 'admin' CHECK (admin_role IN ('owner', 'admin')),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (club_id, user_id)
);

CREATE INDEX idx_club_admins_club ON club_admins(club_id);
CREATE INDEX idx_club_admins_user ON club_admins(user_id);
