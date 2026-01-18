-- Comprehensive database fix script for MySQL
-- Run this to ensure all tables exist with correct structure

-- Drop and recreate sponsors table with correct columns
DROP TABLE IF EXISTS sponsors;

CREATE TABLE sponsors (
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

-- Create messages table if not exists
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_announcement TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_club (club_id),
    INDEX idx_messages_recipient (recipient_id)
);

-- Create notifications table if not exists
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    club_id INT UNSIGNED NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    link VARCHAR(500) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
);

-- Create meetings table if not exists
CREATE TABLE IF NOT EXISTS meetings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    meeting_date DATE NOT NULL,
    meeting_time TIME NULL,
    location VARCHAR(255) NULL,
    meeting_type VARCHAR(50) DEFAULT 'committee',
    status VARCHAR(20) DEFAULT 'scheduled',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meetings_club (club_id),
    INDEX idx_meetings_date (meeting_date)
);

-- Create meeting_minutes table if not exists
CREATE TABLE IF NOT EXISTS meeting_minutes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_minutes_meeting (meeting_id)
);

-- Create meeting_decisions table if not exists
CREATE TABLE IF NOT EXISTS meeting_decisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT UNSIGNED NOT NULL,
    decision_text TEXT NOT NULL,
    assigned_to INT UNSIGNED NULL,
    due_date DATE NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_decisions_meeting (meeting_id)
);

-- Create club_policies table if not exists
CREATE TABLE IF NOT EXISTS club_policies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    policy_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_policies_club (club_id)
);

-- Create catch_logs table if not exists
CREATE TABLE IF NOT EXISTS catch_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    species VARCHAR(100) NOT NULL,
    weight_kg DECIMAL(10,3) NULL,
    length_cm DECIMAL(10,2) NULL,
    catch_date DATE NOT NULL,
    location VARCHAR(255) NULL,
    method VARCHAR(100) NULL,
    bait VARCHAR(100) NULL,
    notes TEXT NULL,
    photo_url VARCHAR(500) NULL,
    is_released TINYINT(1) DEFAULT 0,
    is_personal_best TINYINT(1) DEFAULT 0,
    is_club_record TINYINT(1) DEFAULT 0,
    is_catch_of_month TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_catch_club (club_id),
    INDEX idx_catch_user (user_id),
    INDEX idx_catch_date (catch_date)
);

-- Create personal_bests table if not exists
CREATE TABLE IF NOT EXISTS personal_bests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    species VARCHAR(100) NOT NULL,
    weight_kg DECIMAL(10,3) NULL,
    catch_log_id INT UNSIGNED NULL,
    achieved_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pb (club_id, user_id, species),
    INDEX idx_pb_user (user_id)
);

-- Create club_records table if not exists
CREATE TABLE IF NOT EXISTS club_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    species VARCHAR(100) NOT NULL,
    weight_kg DECIMAL(10,3) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    catch_log_id INT UNSIGNED NULL,
    achieved_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_record (club_id, species),
    INDEX idx_record_club (club_id)
);

-- Create competition_seasons table if not exists
CREATE TABLE IF NOT EXISTS competition_seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_season_club (club_id)
);

-- Create club_finances table if not exists
CREATE TABLE IF NOT EXISTS club_finances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    transaction_date DATE NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_finance_club (club_id),
    INDEX idx_finance_date (transaction_date)
);

-- Create club_accounts table if not exists
CREATE TABLE IF NOT EXISTS club_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_club (club_id)
);

-- Create subscription_plans table if not exists
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    features TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create club_subscriptions table if not exists
CREATE TABLE IF NOT EXISTS club_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sub_club (club_id)
);

-- Create club_news table if not exists
CREATE TABLE IF NOT EXISTS club_news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_news_club (club_id)
);

SELECT 'All tables created successfully!' as status;
