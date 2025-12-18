-- Add catch of the month flag (admin-selected)
ALTER TABLE catch_logs ADD COLUMN is_catch_of_month TINYINT(1) DEFAULT 0;
