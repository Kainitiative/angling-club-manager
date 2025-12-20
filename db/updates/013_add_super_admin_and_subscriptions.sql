ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0;

CREATE TABLE subscription_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE,
  price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
  description TEXT NULL,
  features TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE club_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NULL,
  status ENUM('trial', 'active', 'cancelled', 'expired', 'suspended') DEFAULT 'trial',
  trial_started_at TIMESTAMP NULL,
  trial_ends_at TIMESTAMP NULL,
  paid_until TIMESTAMP NULL,
  stripe_customer_id VARCHAR(255) NULL,
  stripe_subscription_id VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_club (club_id)
);

CREATE TABLE billing_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'EUR',
  status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  stripe_payment_id VARCHAR(255) NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO subscription_plans (name, slug, price_monthly, description, features) VALUES
('Free', 'free', 0.00, 'Basic membership for users and club members', '["View club page", "View catch log", "Log catches", "View leaderboards"]'),
('Club Admin', 'club_admin', 10.00, 'Full club management features', '["All Free features", "Create and manage club", "Manage members", "Manage competitions", "Club news", "Policies & constitution"]'),
('Club Pro', 'club_pro', 15.00, 'Advanced club management with financial tools', '["All Club Admin features", "Financial management", "Expense tracking", "Financial reports", "Secretary features"]');
