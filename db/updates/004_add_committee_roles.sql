-- Add committee_role column to club_members table
-- Roles: member (default), chairperson, secretary, treasurer, pro, safety_officer, child_liaison_officer

ALTER TABLE club_members 
ADD COLUMN IF NOT EXISTS committee_role VARCHAR(50) DEFAULT 'member';

-- Create index for faster lookups by role
CREATE INDEX IF NOT EXISTS idx_club_members_role ON club_members(club_id, committee_role);
