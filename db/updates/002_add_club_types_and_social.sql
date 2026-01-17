ALTER TABLE clubs ADD COLUMN club_type VARCHAR(50) NOT NULL DEFAULT 'angling_club';
-- New types: 'angling_club', 'syndicate', 'commercial_fishery', 'angling_guide', 'charter_boat'

ALTER TABLE clubs ADD COLUMN tagline VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN website_url VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN social_twitter VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN social_instagram VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN social_facebook VARCHAR(255) NULL;
ALTER TABLE clubs ADD COLUMN social_youtube VARCHAR(255) NULL;
