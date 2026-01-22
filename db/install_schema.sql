-- Angling Ireland - Complete Database Schema
-- Version: 1.0
-- For MySQL 8.0+ / MariaDB 10.5+

SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- USERS
-- =========================
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_picture_url VARCHAR(500) NULL,
  avatar_url VARCHAR(500) NULL,
  dob DATE NULL,
  phone VARCHAR(30) NULL,
  town VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
  role ENUM('user', 'super_admin') NOT NULL DEFAULT 'user',
  is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_junior TINYINT(1) NOT NULL DEFAULT 0,
  parent_id BIGINT UNSIGNED NULL,
  medical_notes TEXT NULL,
  guardian_consent TINYINT(1) NOT NULL DEFAULT 0,
  consent_date TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_parent (parent_id),
  INDEX idx_users_junior (is_junior),
  CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUBS
-- =========================
CREATE TABLE IF NOT EXISTS clubs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  entity_type ENUM('club', 'syndicate', 'fishery', 'guide', 'charter') NOT NULL DEFAULT 'club',
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
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  trial_start_date DATE NULL,
  trial_end_date DATE NULL,
  access_until DATE NULL,
  billing_status ENUM('trial', 'active', 'past_due', 'canceled', 'expired', 'free') NOT NULL DEFAULT 'free',
  subscription_plan ENUM('free', 'monthly') NOT NULL DEFAULT 'free',
  stripe_customer_id VARCHAR(80) NULL,
  stripe_subscription_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clubs_slug (slug),
  INDEX idx_clubs_entity_type (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB ADMINS
-- =========================
CREATE TABLE IF NOT EXISTS club_admins (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  admin_role ENUM('owner', 'admin') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_club_user (club_id, user_id),
  CONSTRAINT fk_club_admins_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_admins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB MEMBERS
-- =========================
CREATE TABLE IF NOT EXISTS club_members (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  parent_user_id BIGINT UNSIGNED NULL,
  membership_status ENUM('pending', 'active', 'suspended', 'expired') NOT NULL DEFAULT 'pending',
  committee_role ENUM('member', 'chairperson', 'secretary', 'treasurer', 'pro', 'safety_officer', 'child_liaison_officer', 'cwo', 'competition_secretary', 'committee') NOT NULL DEFAULT 'member',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_club_member (club_id, user_id),
  CONSTRAINT fk_club_members_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_club_members_parent FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_club_members_status (membership_status),
  INDEX idx_club_members_role (committee_role),
  INDEX idx_club_members_parent (parent_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB PROFILE SETTINGS
-- =========================
CREATE TABLE IF NOT EXISTS club_profile_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL UNIQUE,
  tagline VARCHAR(255) NULL,
  hero_title VARCHAR(255) NULL,
  hero_tagline VARCHAR(500) NULL,
  hero_image_url VARCHAR(500) NULL,
  why_join_text TEXT NULL,
  contact_email VARCHAR(190) NULL,
  contact_phone VARCHAR(30) NULL,
  facebook_url VARCHAR(500) NULL,
  instagram_url VARCHAR(500) NULL,
  twitter_url VARCHAR(500) NULL,
  website_url VARCHAR(500) NULL,
  primary_color VARCHAR(7) NULL DEFAULT '#1e3a5f',
  secondary_color VARCHAR(7) NULL DEFAULT '#2d5a87',
  show_members_count TINYINT(1) NOT NULL DEFAULT 1,
  show_catches TINYINT(1) NOT NULL DEFAULT 1,
  show_competitions TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_settings_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB MEMBERSHIP FEES
-- =========================
CREATE TABLE IF NOT EXISTS club_membership_fees (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  fee_name VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  billing_period ENUM('yearly', 'monthly', 'once') NOT NULL DEFAULT 'yearly',
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fees_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB PERKS
-- =========================
CREATE TABLE IF NOT EXISTS club_perks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_perks_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB GALLERY
-- =========================
CREATE TABLE IF NOT EXISTS club_gallery (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  caption VARCHAR(255) NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SPONSORS
-- =========================
CREATE TABLE IF NOT EXISTS sponsors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NULL,
  competition_id BIGINT UNSIGNED NULL,
  name VARCHAR(190) NOT NULL,
  company VARCHAR(190) NULL,
  logo_url VARCHAR(500) NULL,
  website_url VARCHAR(500) NULL,
  description TEXT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sponsors_club (club_id),
  INDEX idx_sponsors_competition (competition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB NEWS
-- =========================
CREATE TABLE IF NOT EXISTS club_news (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  author_id BIGINT UNSIGNED NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- COMPETITIONS
-- =========================
CREATE TABLE IF NOT EXISTS competitions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  season_id BIGINT UNSIGNED NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  competition_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  venue VARCHAR(255) NULL,
  entry_fee DECIMAL(10,2) NULL,
  max_participants INT NULL,
  status ENUM('upcoming', 'in_progress', 'completed', 'canceled') NOT NULL DEFAULT 'upcoming',
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_competitions_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_competitions_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_competitions_date (competition_date),
  INDEX idx_competitions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- COMPETITION RESULTS
-- =========================
CREATE TABLE IF NOT EXISTS competition_results (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  competition_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  angler_name VARCHAR(120) NULL,
  position INT NULL,
  weight_kg DECIMAL(10,3) NULL,
  fish_count INT NULL,
  longest_fish_cm DECIMAL(10,2) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_results_competition FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
  CONSTRAINT fk_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_results_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- COMPETITION SEASONS
-- =========================
CREATE TABLE IF NOT EXISTS competition_seasons (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_seasons_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SEASON STANDINGS
-- =========================
CREATE TABLE IF NOT EXISTS season_standings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  total_points INT NOT NULL DEFAULT 0,
  competitions_entered INT NOT NULL DEFAULT 0,
  best_position INT NULL,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_standings_season FOREIGN KEY (season_id) REFERENCES competition_seasons(id) ON DELETE CASCADE,
  CONSTRAINT fk_standings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_season_user (season_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB LEADERBOARDS
-- =========================
CREATE TABLE IF NOT EXISTS club_leaderboards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  metric_type ENUM('competition_points', 'total_catches', 'total_weight', 'biggest_fish', 'species_count') NOT NULL DEFAULT 'competition_points',
  time_scope ENUM('all_time', 'this_year', 'this_season', 'custom') NOT NULL DEFAULT 'this_year',
  start_date DATE NULL,
  end_date DATE NULL,
  season_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_leaderboards_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_leaderboards_season FOREIGN KEY (season_id) REFERENCES competition_seasons(id) ON DELETE SET NULL,
  CONSTRAINT fk_leaderboards_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_leaderboards_club (club_id),
  INDEX idx_leaderboards_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- LEADERBOARD ENTRIES (cached rankings)
-- =========================
CREATE TABLE IF NOT EXISTS leaderboard_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  leaderboard_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(12,3) NOT NULL DEFAULT 0,
  rank_position INT NULL,
  competitions_count INT NOT NULL DEFAULT 0,
  catches_count INT NOT NULL DEFAULT 0,
  calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entries_leaderboard FOREIGN KEY (leaderboard_id) REFERENCES club_leaderboards(id) ON DELETE CASCADE,
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_leaderboard_user (leaderboard_id, user_id),
  INDEX idx_entries_rank (leaderboard_id, rank_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- FISH SPECIES
-- =========================
CREATE TABLE IF NOT EXISTS fish_species (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  scientific_name VARCHAR(190) NULL,
  category ENUM('coarse', 'game', 'sea', 'other') NOT NULL DEFAULT 'other',
  specimen_weight_kg DECIMAL(10,3) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CATCH LOGS
-- =========================
CREATE TABLE IF NOT EXISTS catch_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NULL,
  species_id BIGINT UNSIGNED NULL,
  species VARCHAR(120) NULL,
  weight_kg DECIMAL(10,3) NULL,
  length_cm DECIMAL(10,2) NULL,
  catch_date DATE NOT NULL,
  location VARCHAR(255) NULL,
  method VARCHAR(120) NULL,
  bait VARCHAR(120) NULL,
  weather VARCHAR(120) NULL,
  notes TEXT NULL,
  photo_url VARCHAR(500) NULL,
  is_personal_best TINYINT(1) NOT NULL DEFAULT 0,
  is_club_record TINYINT(1) NOT NULL DEFAULT 0,
  is_catch_of_month TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_catch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_catch_species FOREIGN KEY (species_id) REFERENCES fish_species(id) ON DELETE SET NULL,
  INDEX idx_catch_date (catch_date),
  INDEX idx_catch_species (species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- PERSONAL BESTS
-- =========================
CREATE TABLE IF NOT EXISTS personal_bests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  species_id BIGINT UNSIGNED NULL,
  species VARCHAR(120) NULL,
  weight_kg DECIMAL(10,3) NULL,
  length_cm DECIMAL(10,2) NULL,
  catch_log_id BIGINT UNSIGNED NULL,
  achieved_at DATE NOT NULL,
  CONSTRAINT fk_pb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_species (user_id, species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB RECORDS
-- =========================
CREATE TABLE IF NOT EXISTS club_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  species_id BIGINT UNSIGNED NULL,
  species VARCHAR(120) NULL,
  weight_kg DECIMAL(10,3) NULL,
  length_cm DECIMAL(10,2) NULL,
  catch_log_id BIGINT UNSIGNED NULL,
  achieved_at DATE NOT NULL,
  CONSTRAINT fk_record_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_record_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY unique_club_species (club_id, species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MEETINGS
-- =========================
CREATE TABLE IF NOT EXISTS meetings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  meeting_type ENUM('committee', 'agm', 'egm', 'general', 'other') NOT NULL DEFAULT 'committee',
  meeting_date DATE NOT NULL,
  meeting_time TIME NULL,
  location VARCHAR(255) NULL,
  description TEXT NULL,
  status ENUM('scheduled', 'in_progress', 'completed', 'canceled') NOT NULL DEFAULT 'scheduled',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_meetings_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_meetings_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_meetings_date (meeting_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MEETING MINUTES
-- =========================
CREATE TABLE IF NOT EXISTS meeting_minutes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_minutes_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
  CONSTRAINT fk_minutes_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MEETING DECISIONS
-- =========================
CREATE TABLE IF NOT EXISTS meeting_decisions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  meeting_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  decision_date DATE NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'deferred') NOT NULL DEFAULT 'pending',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_decisions_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_decisions_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
  CONSTRAINT fk_decisions_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MEETING TASKS
-- =========================
CREATE TABLE IF NOT EXISTS meeting_tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  meeting_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_by BIGINT UNSIGNED NULL,
  due_date DATE NULL,
  priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  status ENUM('pending', 'in_progress', 'completed', 'canceled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
  CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_tasks_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tasks_status (status),
  INDEX idx_tasks_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MEETING NOTES
-- =========================
CREATE TABLE IF NOT EXISTS meeting_notes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notes_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB FINANCES
-- =========================
CREATE TABLE IF NOT EXISTS club_finances (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  type ENUM('income', 'expense') NOT NULL,
  category VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description TEXT NULL,
  transaction_date DATE NOT NULL,
  receipt_url VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_finances_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_finances_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_finances_date (transaction_date),
  INDEX idx_finances_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB ACCOUNTS
-- =========================
CREATE TABLE IF NOT EXISTS club_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  account_name VARCHAR(120) NOT NULL,
  account_type ENUM('bank', 'cash', 'paypal', 'stripe', 'other') NOT NULL DEFAULT 'bank',
  balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes TEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_accounts_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- NOTIFICATIONS
-- =========================
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  link VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_read (is_read),
  INDEX idx_notifications_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- MESSAGES (Club Direct Messages)
-- =========================
CREATE TABLE IF NOT EXISTS club_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NULL,
  subject VARCHAR(255) NULL,
  message TEXT NOT NULL,
  is_announcement TINYINT(1) NOT NULL DEFAULT 0,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_messages_recipient (recipient_id),
  INDEX idx_messages_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SUBSCRIPTION PLANS (Legacy - Free Forever)
-- =========================
CREATE TABLE IF NOT EXISTS subscription_plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  features TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- CLUB SUBSCRIPTIONS (Legacy - Free Forever)
-- =========================
CREATE TABLE IF NOT EXISTS club_subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NULL,
  status ENUM('active', 'canceled', 'expired', 'free') NOT NULL DEFAULT 'free',
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATE NULL,
  CONSTRAINT fk_subscriptions_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SCHEMA MIGRATIONS TRACKER
-- =========================
CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  migration VARCHAR(255) NOT NULL UNIQUE,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SEED DATA: Free Plan
-- =========================
INSERT INTO subscription_plans (name, price, features, is_active) VALUES 
('Free Forever', 0.00, 'All features included for Irish angling clubs', 1);

-- =========================
-- SEED DATA: Common Fish Species (Ireland)
-- =========================
INSERT INTO fish_species (name, category, specimen_weight_kg) VALUES
('Pike', 'coarse', 13.608),
('Perch', 'coarse', 1.361),
('Bream', 'coarse', 2.722),
('Roach', 'coarse', 0.907),
('Rudd', 'coarse', 0.907),
('Tench', 'coarse', 3.175),
('Carp', 'coarse', 9.072),
('Eel', 'coarse', 1.814),
('Brown Trout', 'game', 2.268),
('Sea Trout', 'game', 2.722),
('Salmon', 'game', 9.072),
('Rainbow Trout', 'game', 3.629),
('Bass', 'sea', 4.536),
('Cod', 'sea', 9.072),
('Pollack', 'sea', 5.443),
('Mackerel', 'sea', 0.907),
('Ray', 'sea', 9.072),
('Conger Eel', 'sea', 18.144),
('Flounder', 'sea', 0.907),
('Plaice', 'sea', 1.361);

SET FOREIGN_KEY_CHECKS = 1;
