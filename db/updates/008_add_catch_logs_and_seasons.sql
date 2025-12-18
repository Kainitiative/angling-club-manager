-- Catch logs - members log their catches
CREATE TABLE IF NOT EXISTS catch_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  user_id INT NOT NULL,
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
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Fish species reference table
CREATE TABLE IF NOT EXISTS fish_species (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NULL,
  display_order INT DEFAULT 0
);

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

-- Competition seasons/leagues
CREATE TABLE IF NOT EXISTS competition_seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  scoring_type ENUM('total_weight', 'total_points', 'best_n') DEFAULT 'total_points',
  best_n_count INT DEFAULT 5,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

-- Link competitions to seasons
ALTER TABLE competitions ADD COLUMN season_id INT NULL;
ALTER TABLE competitions ADD COLUMN points_multiplier DECIMAL(3,2) DEFAULT 1.00;

-- Member season standings (cached/calculated)
CREATE TABLE IF NOT EXISTS season_standings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  user_id INT NOT NULL,
  total_points DECIMAL(10,2) DEFAULT 0,
  total_weight_kg DECIMAL(10,3) DEFAULT 0,
  competitions_entered INT DEFAULT 0,
  wins INT DEFAULT 0,
  podiums INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (season_id) REFERENCES competition_seasons(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_season_user (season_id, user_id)
);

-- Personal bests tracking per species per member
CREATE TABLE IF NOT EXISTS personal_bests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  user_id INT NOT NULL,
  species VARCHAR(100) NOT NULL,
  weight_kg DECIMAL(6,3) NOT NULL,
  catch_log_id INT NULL,
  achieved_date DATE NOT NULL,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (catch_log_id) REFERENCES catch_logs(id) ON DELETE SET NULL,
  UNIQUE KEY unique_pb (club_id, user_id, species)
);

-- Club records per species
CREATE TABLE IF NOT EXISTS club_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  species VARCHAR(100) NOT NULL,
  weight_kg DECIMAL(6,3) NOT NULL,
  user_id INT NOT NULL,
  catch_log_id INT NULL,
  achieved_date DATE NOT NULL,
  FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (catch_log_id) REFERENCES catch_logs(id) ON DELETE SET NULL,
  UNIQUE KEY unique_record (club_id, species)
);
