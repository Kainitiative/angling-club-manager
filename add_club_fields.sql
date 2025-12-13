-- Run this to add new club fields to existing clubs table

ALTER TABLE clubs
  ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(255) NULL AFTER about_text,
  ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(255) NULL AFTER address_line1,
  ADD COLUMN IF NOT EXISTS town VARCHAR(120) NULL AFTER address_line2,
  ADD COLUMN IF NOT EXISTS county VARCHAR(120) NULL AFTER town,
  ADD COLUMN IF NOT EXISTS postcode VARCHAR(20) NULL AFTER county,
  ADD COLUMN IF NOT EXISTS country VARCHAR(120) NULL DEFAULT 'United Kingdom' AFTER postcode,
  ADD COLUMN IF NOT EXISTS fishing_styles TEXT NULL AFTER city;
