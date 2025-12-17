-- Competition results table for storing scores
-- Run this migration after 002_add_competitions_table.sql

CREATE TABLE IF NOT EXISTS competition_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  competition_id INT UNSIGNED NOT NULL,
  competitor_name VARCHAR(190) NOT NULL,
  fish_count INT UNSIGNED NOT NULL DEFAULT 0,
  total_weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  total_score DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  position INT UNSIGNED NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
  UNIQUE KEY unique_competitor_per_comp (competition_id, competitor_name),
  INDEX idx_competition_results_competition (competition_id),
  INDEX idx_competition_results_score (total_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
