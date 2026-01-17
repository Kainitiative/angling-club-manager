ALTER TABLE club_members ADD COLUMN parent_user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL;
CREATE INDEX idx_club_members_parent ON club_members(parent_user_id);
