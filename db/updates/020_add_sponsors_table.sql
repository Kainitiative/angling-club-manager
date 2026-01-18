-- Add sponsors table for club sponsors/supporters

CREATE TABLE IF NOT EXISTS sponsors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NULL,
    logo VARCHAR(500) NULL,
    website VARCHAR(500) NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sponsors_club (club_id),
    INDEX idx_sponsors_active (is_active)
);
