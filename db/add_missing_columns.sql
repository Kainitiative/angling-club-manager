-- Run these one at a time if you get "duplicate column" errors (means column already exists)

-- club_members columns
ALTER TABLE club_members ADD COLUMN committee_role VARCHAR(50) NULL;
ALTER TABLE club_members ADD COLUMN parent_user_id INT UNSIGNED NULL;

-- users columns  
ALTER TABLE users ADD COLUMN is_junior TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN parent_id INT UNSIGNED NULL;
ALTER TABLE users ADD COLUMN date_of_birth DATE NULL;
ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0;

-- clubs columns
ALTER TABLE clubs ADD COLUMN account_type VARCHAR(50) DEFAULT 'angling_club';
ALTER TABLE clubs ADD COLUMN tagline VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN about_text TEXT NULL;
ALTER TABLE clubs ADD COLUMN logo_url VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN website VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN social_facebook VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN social_instagram VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN social_twitter VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN social_youtube VARCHAR(500) NULL;
ALTER TABLE clubs ADD COLUMN primary_color VARCHAR(7) NULL;
ALTER TABLE clubs ADD COLUMN secondary_color VARCHAR(7) NULL;
ALTER TABLE clubs ADD COLUMN is_public TINYINT(1) DEFAULT 1;
