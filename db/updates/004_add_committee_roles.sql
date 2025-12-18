-- Add committee_role column to club_members table
-- Roles: member (default), chairperson, secretary, treasurer, pro, safety_officer, child_liaison_officer
-- Note: If column already exists, this will error - that's OK, just means it's already added

ALTER TABLE club_members ADD COLUMN committee_role VARCHAR(50) DEFAULT 'member';
