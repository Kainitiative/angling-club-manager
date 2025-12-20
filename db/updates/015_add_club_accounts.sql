CREATE TABLE club_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  account_type ENUM('bank', 'cash', 'paypal', 'other') DEFAULT 'bank',
  balance DECIMAL(12, 2) DEFAULT 0.00,
  currency VARCHAR(3) DEFAULT 'EUR',
  notes TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_club (club_id),
  INDEX idx_club_active (club_id, is_active)
);
