-- Angling Club Manager
-- PostgreSQL Schema
-- Generated from MySQL schema with migrations

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS club_accounts CASCADE;
DROP TABLE IF EXISTS club_transactions CASCADE;
DROP TABLE IF EXISTS meeting_tasks CASCADE;
DROP TABLE IF EXISTS meeting_notes CASCADE;
DROP TABLE IF EXISTS meeting_decisions CASCADE;
DROP TABLE IF EXISTS meeting_minutes CASCADE;
DROP TABLE IF EXISTS meetings CASCADE;
DROP TABLE IF EXISTS club_policies CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS messages CASCADE;
DROP TABLE IF EXISTS club_news CASCADE;
DROP TABLE IF EXISTS season_entries CASCADE;
DROP TABLE IF EXISTS competition_seasons CASCADE;
DROP TABLE IF EXISTS catches CASCADE;
DROP TABLE IF EXISTS fish_species CASCADE;
DROP TABLE IF EXISTS club_profile_settings CASCADE;
DROP TABLE IF EXISTS competition_results CASCADE;
DROP TABLE IF EXISTS competitions CASCADE;
DROP TABLE IF EXISTS club_members CASCADE;
DROP TABLE IF EXISTS club_admins CASCADE;
DROP TABLE IF EXISTS clubs CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS schema_migrations CASCADE;

-- Schema migrations tracking
CREATE TABLE schema_migrations (
  id SERIAL PRIMARY KEY,
  filename VARCHAR(255) NOT NULL UNIQUE,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- USERS
-- =========================
CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_picture_url VARCHAR(500) NULL,
  dob DATE NULL,
  phone VARCHAR(30) NULL,
  town VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  gender VARCHAR(30) NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
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
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  town VARCHAR(120) NULL,
  county VARCHAR(120) NULL,
  postcode VARCHAR(20) NULL,
  country VARCHAR(120) NULL DEFAULT 'Ireland',
  location_text VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  fishing_styles TEXT NULL,
  is_public SMALLINT NOT NULL DEFAULT 1,
  trial_start_date DATE NOT NULL DEFAULT CURRENT_DATE,
  trial_end_date DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '3 months'),
  access_until DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '100 years'),
  billing_status VARCHAR(30) NOT NULL DEFAULT 'trial',
  subscription_plan VARCHAR(30) NOT NULL DEFAULT 'free',
  stripe_customer_id VARCHAR(80) NULL,
  stripe_subscription_id VARCHAR(80) NULL,
  primary_color VARCHAR(20) NULL DEFAULT '#2d5a87',
  secondary_color VARCHAR(20) NULL DEFAULT '#1e3a5f',
  welcome_message TEXT NULL,
  show_gallery SMALLINT NOT NULL DEFAULT 1,
  show_catches SMALLINT NOT NULL DEFAULT 1,
  show_competitions SMALLINT NOT NULL DEFAULT 1,
  show_news SMALLINT NOT NULL DEFAULT 1,
  custom_css TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

-- =========================
-- CLUB ADMINS
-- =========================
CREATE TABLE club_admins (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  admin_role VARCHAR(20) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (club_id, user_id)
);

CREATE INDEX idx_club_admins_club ON club_admins(club_id);
CREATE INDEX idx_club_admins_user ON club_admins(user_id);

-- =========================
-- CLUB MEMBERS
-- =========================
CREATE TABLE club_members (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  membership_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  committee_role VARCHAR(50) NULL DEFAULT 'member',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  UNIQUE (club_id, user_id)
);

CREATE INDEX idx_club_members_club ON club_members(club_id);
CREATE INDEX idx_club_members_user ON club_members(user_id);
CREATE INDEX idx_club_members_status ON club_members(membership_status);

-- =========================
-- COMPETITIONS
-- =========================
CREATE TABLE competitions (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  competition_date DATE NOT NULL,
  location VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_competitions_club ON competitions(club_id);
CREATE INDEX idx_competitions_date ON competitions(competition_date);

-- =========================
-- COMPETITION RESULTS
-- =========================
CREATE TABLE competition_results (
  id BIGSERIAL PRIMARY KEY,
  competition_id BIGINT NOT NULL REFERENCES competitions(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  position INTEGER NULL,
  points DECIMAL(10,2) NULL DEFAULT 0,
  total_weight DECIMAL(10,3) NULL,
  fish_count INTEGER NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (competition_id, user_id)
);

CREATE INDEX idx_results_competition ON competition_results(competition_id);
CREATE INDEX idx_results_user ON competition_results(user_id);

-- =========================
-- CLUB FINANCES
-- =========================
CREATE TABLE club_transactions (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  transaction_type VARCHAR(20) NOT NULL,
  category VARCHAR(50) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT NULL,
  transaction_date DATE NOT NULL,
  created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_transactions_club ON club_transactions(club_id);
CREATE INDEX idx_transactions_date ON club_transactions(transaction_date);

-- =========================
-- CLUB PROFILE SETTINGS
-- =========================
CREATE TABLE club_profile_settings (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL UNIQUE REFERENCES clubs(id) ON DELETE CASCADE,
  hero_image_url VARCHAR(500) NULL,
  gallery_images TEXT NULL,
  social_facebook VARCHAR(255) NULL,
  social_twitter VARCHAR(255) NULL,
  social_instagram VARCHAR(255) NULL,
  social_youtube VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

-- =========================
-- FISH SPECIES
-- =========================
CREATE TABLE fish_species (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  category VARCHAR(50) NOT NULL DEFAULT 'other',
  is_active SMALLINT NOT NULL DEFAULT 1
);

INSERT INTO fish_species (name, category) VALUES
('Pike', 'coarse'), ('Perch', 'coarse'), ('Bream', 'coarse'), ('Roach', 'coarse'),
('Rudd', 'coarse'), ('Tench', 'coarse'), ('Carp', 'coarse'), ('Eel', 'coarse'),
('Brown Trout', 'game'), ('Rainbow Trout', 'game'), ('Salmon', 'game'), ('Sea Trout', 'game'),
('Bass', 'sea'), ('Pollack', 'sea'), ('Cod', 'sea'), ('Mackerel', 'sea'),
('Wrasse', 'sea'), ('Ray', 'sea'), ('Flounder', 'sea'),
('Other', 'other');

-- =========================
-- CATCHES
-- =========================
CREATE TABLE catches (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  species_id BIGINT NULL REFERENCES fish_species(id) ON DELETE SET NULL,
  weight_kg DECIMAL(8,3) NULL,
  length_cm DECIMAL(8,2) NULL,
  location_name VARCHAR(255) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  photo_url VARCHAR(500) NULL,
  notes TEXT NULL,
  catch_date DATE NOT NULL,
  is_personal_best SMALLINT NOT NULL DEFAULT 0,
  is_club_record SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_catches_club ON catches(club_id);
CREATE INDEX idx_catches_user ON catches(user_id);
CREATE INDEX idx_catches_date ON catches(catch_date);

-- =========================
-- COMPETITION SEASONS
-- =========================
CREATE TABLE competition_seasons (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  points_system VARCHAR(50) NOT NULL DEFAULT 'standard',
  is_active SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE season_entries (
  id BIGSERIAL PRIMARY KEY,
  season_id BIGINT NOT NULL REFERENCES competition_seasons(id) ON DELETE CASCADE,
  competition_id BIGINT NOT NULL REFERENCES competitions(id) ON DELETE CASCADE,
  UNIQUE (season_id, competition_id)
);

-- =========================
-- CLUB NEWS
-- =========================
CREATE TABLE club_news (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  author_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  is_pinned SMALLINT NOT NULL DEFAULT 0,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_news_club ON club_news(club_id);
CREATE INDEX idx_news_status ON club_news(status);

-- =========================
-- NOTIFICATIONS
-- =========================
CREATE TABLE notifications (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  link VARCHAR(500) NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);

-- =========================
-- MESSAGES
-- =========================
CREATE TABLE messages (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  sender_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  recipient_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
  message_type VARCHAR(20) NOT NULL DEFAULT 'direct',
  subject VARCHAR(255) NULL,
  content TEXT NOT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_messages_club ON messages(club_id);
CREATE INDEX idx_messages_recipient ON messages(recipient_id);

-- =========================
-- CLUB POLICIES
-- =========================
CREATE TABLE club_policies (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  policy_type VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NULL,
  updated_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  UNIQUE (club_id, policy_type)
);

-- =========================
-- MEETINGS
-- =========================
CREATE TABLE meetings (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  meeting_date DATE NOT NULL,
  meeting_time TIME NULL,
  location VARCHAR(255) NULL,
  meeting_type VARCHAR(50) NOT NULL DEFAULT 'committee',
  status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_meetings_club ON meetings(club_id);
CREATE INDEX idx_meetings_date ON meetings(meeting_date);

CREATE TABLE meeting_minutes (
  id BIGSERIAL PRIMARY KEY,
  meeting_id BIGINT NOT NULL REFERENCES meetings(id) ON DELETE CASCADE,
  content TEXT NULL,
  attendees TEXT NULL,
  apologies TEXT NULL,
  created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE TABLE meeting_decisions (
  id BIGSERIAL PRIMARY KEY,
  meeting_id BIGINT NOT NULL REFERENCES meetings(id) ON DELETE CASCADE,
  motion TEXT NOT NULL,
  proposed_by VARCHAR(255) NULL,
  seconded_by VARCHAR(255) NULL,
  vote_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE meeting_notes (
  id BIGSERIAL PRIMARY KEY,
  meeting_id BIGINT NOT NULL REFERENCES meetings(id) ON DELETE CASCADE,
  note TEXT NOT NULL,
  created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE meeting_tasks (
  id BIGSERIAL PRIMARY KEY,
  meeting_id BIGINT NULL REFERENCES meetings(id) ON DELETE SET NULL,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  assigned_to BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'medium',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  due_date DATE NULL,
  created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_tasks_assigned ON meeting_tasks(assigned_to);
CREATE INDEX idx_tasks_status ON meeting_tasks(status);

-- =========================
-- CLUB ACCOUNTS (Bank accounts, cash floats, etc.)
-- =========================
CREATE TABLE club_accounts (
  id BIGSERIAL PRIMARY KEY,
  club_id BIGINT NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
  account_name VARCHAR(100) NOT NULL,
  account_type VARCHAR(50) NOT NULL DEFAULT 'bank',
  current_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active SMALLINT NOT NULL DEFAULT 1,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE INDEX idx_accounts_club ON club_accounts(club_id);
