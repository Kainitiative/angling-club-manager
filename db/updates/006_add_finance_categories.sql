-- Add category column to club_finances table
ALTER TABLE club_finances ADD COLUMN category VARCHAR(50) DEFAULT 'other';

CREATE INDEX idx_club_finances_category ON club_finances(category);
