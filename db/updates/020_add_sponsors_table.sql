-- Add sponsors table for club and competition sponsors/supporters
-- Supports both club-level sponsors (club_id set) and competition-level sponsors (competition_id set)

CREATE TABLE IF NOT EXISTS sponsors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NULL,
    competition_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NULL,
    logo_url VARCHAR(500) NULL,
    website VARCHAR(500) NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sponsors_club (club_id),
    INDEX idx_sponsors_competition (competition_id),
    INDEX idx_sponsors_active (is_active)
);
