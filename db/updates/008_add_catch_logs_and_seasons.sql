-- Fish species reference table
CREATE TABLE IF NOT EXISTS fish_species (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NULL,
  display_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common Irish fish species
INSERT INTO fish_species (name, category, display_order) VALUES
('Pike', 'Coarse', 1),
('Perch', 'Coarse', 2),
('Bream', 'Coarse', 3),
('Roach', 'Coarse', 4),
('Rudd', 'Coarse', 5),
('Tench', 'Coarse', 6),
('Carp', 'Coarse', 7),
('Eel', 'Coarse', 8),
('Brown Trout', 'Game', 10),
('Rainbow Trout', 'Game', 11),
('Salmon', 'Game', 12),
('Sea Trout', 'Game', 13),
('Bass', 'Sea', 20),
('Pollack', 'Sea', 21),
('Cod', 'Sea', 22),
('Mackerel', 'Sea', 23),
('Wrasse', 'Sea', 24),
('Ray', 'Sea', 25),
('Flounder', 'Sea', 26),
('Other', 'Other', 99);

-- Catch logs - members log their catches
CREATE TABLE IF NOT EXISTS catch_logs (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id bigint unsigned NOT NULL,
  user_id bigint unsigned NOT NULL,
  species VARCHAR(100) NOT NULL,
  weight_kg DECIMAL(6,3) NULL,
  length_cm DECIMAL(6,2) NULL,
  location_description VARCHAR(255) NULL,
  catch_date DATE NOT NULL,
  photo_url VARCHAR(500) NULL,
  notes TEXT NULL,
  is_personal_best TINYINT(1) DEFAULT 0,
  is_club_record TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_catch_logs_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_catch_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Competition seasons/leagues
CREATE TABLE IF NOT EXISTS competition_seasons (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id bigint unsigned NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  scoring_type ENUM('total_weight', 'total_points', 'best_n') DEFAULT 'total_points',
  best_n_count INT DEFAULT 5,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_seasons_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link competitions to seasons
ALTER TABLE competitions ADD COLUMN season_id bigint unsigned NULL;
ALTER TABLE competitions ADD COLUMN points_multiplier DECIMAL(3,2) DEFAULT 1.00;

-- Member season standings (cached/calculated)
CREATE TABLE IF NOT EXISTS season_standings (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_id bigint unsigned NOT NULL,
  user_id bigint unsigned NOT NULL,
  total_points DECIMAL(10,2) DEFAULT 0,
  total_weight_kg DECIMAL(10,3) DEFAULT 0,
  competitions_entered INT DEFAULT 0,
  wins INT DEFAULT 0,
  podiums INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_standings_season FOREIGN KEY (season_id) REFERENCES competition_seasons(id) ON DELETE CASCADE,
  CONSTRAINT fk_standings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_season_user (season_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personal bests tracking per species per member
CREATE TABLE IF NOT EXISTS personal_bests (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id bigint unsigned NOT NULL,
  user_id bigint unsigned NOT NULL,
  species VARCHAR(100) NOT NULL,
  weight_kg DECIMAL(6,3) NOT NULL,
  catch_log_id bigint unsigned NULL,
  achieved_date DATE NOT NULL,
  CONSTRAINT fk_pb_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_pb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pb_catch FOREIGN KEY (catch_log_id) REFERENCES catch_logs(id) ON DELETE SET NULL,
  UNIQUE KEY unique_pb (club_id, user_id, species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club records per species
CREATE TABLE IF NOT EXISTS club_records (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id bigint unsigned NOT NULL,
  species VARCHAR(100) NOT NULL,
  weight_kg DECIMAL(6,3) NOT NULL,
  user_id bigint unsigned NOT NULL,
  catch_log_id bigint unsigned NULL,
  achieved_date DATE NOT NULL,
  CONSTRAINT fk_records_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_records_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_records_catch FOREIGN KEY (catch_log_id) REFERENCES catch_logs(id) ON DELETE SET NULL,
  UNIQUE KEY unique_record (club_id, species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
