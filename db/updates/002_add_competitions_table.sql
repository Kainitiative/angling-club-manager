-- Competitions table for club events
CREATE TABLE IF NOT EXISTS competitions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  venue_name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  competition_date DATE NOT NULL,
  start_time TIME NULL,
  
  -- Address fields
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  town VARCHAR(120) NULL,
  county VARCHAR(120) NULL,
  postcode VARCHAR(20) NULL,
  country VARCHAR(120) NOT NULL DEFAULT 'United Kingdom',
  
  -- Google Maps coordinates
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  
  -- Visibility: open = anyone can see, private = club members only
  visibility ENUM('open', 'private') NOT NULL DEFAULT 'open',
  
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_competitions_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_competitions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_competitions_club ON competitions(club_id);
CREATE INDEX idx_competitions_date ON competitions(competition_date);
CREATE INDEX idx_competitions_country ON competitions(country);
CREATE INDEX idx_competitions_visibility ON competitions(visibility);
CREATE INDEX idx_competitions_coords ON competitions(latitude, longitude);
