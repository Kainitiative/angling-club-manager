CREATE TABLE meetings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  meeting_date DATE NOT NULL,
  meeting_time TIME NULL,
  location VARCHAR(255) NULL,
  meeting_type ENUM('committee', 'agm', 'egm', 'general', 'other') DEFAULT 'committee',
  status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_club_date (club_id, meeting_date)
);

CREATE TABLE meeting_minutes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  attendees TEXT NULL,
  apologies TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_meeting_minutes (meeting_id),
  INDEX idx_club (club_id)
);

CREATE TABLE meeting_decisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  decision_date DATE NOT NULL,
  proposed_by VARCHAR(255) NULL,
  seconded_by VARCHAR(255) NULL,
  votes_for INT UNSIGNED DEFAULT 0,
  votes_against INT UNSIGNED DEFAULT 0,
  votes_abstain INT UNSIGNED DEFAULT 0,
  status ENUM('proposed', 'approved', 'rejected', 'deferred') DEFAULT 'approved',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_club_date (club_id, decision_date),
  INDEX idx_meeting (meeting_id)
);

CREATE TABLE meeting_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NULL,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  is_private TINYINT(1) DEFAULT 1,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_club (club_id),
  INDEX idx_meeting (meeting_id)
);

CREATE TABLE meeting_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meeting_id BIGINT UNSIGNED NULL,
  club_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_by BIGINT UNSIGNED NOT NULL,
  due_date DATE NULL,
  priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_club_status (club_id, status),
  INDEX idx_assigned_to (assigned_to),
  INDEX idx_meeting (meeting_id),
  INDEX idx_due_date (due_date)
);
