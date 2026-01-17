ALTER TABLE users ADD COLUMN parent_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE users ADD COLUMN is_junior SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN medical_notes TEXT NULL;
ALTER TABLE users ADD COLUMN guardian_consent SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN consent_date TIMESTAMP NULL;

CREATE INDEX idx_users_parent ON users(parent_id);
CREATE INDEX idx_users_is_junior ON users(is_junior);
