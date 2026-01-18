-- Add sponsors table for club sponsors/supporters

CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255) NULL,
    logo VARCHAR(500) NULL,
    website VARCHAR(500) NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
);

CREATE INDEX idx_sponsors_club ON sponsors(club_id);
CREATE INDEX idx_sponsors_active ON sponsors(is_active);
