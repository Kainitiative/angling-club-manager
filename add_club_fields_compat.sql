-- Compatible with older MySQL/MariaDB (no ADD COLUMN IF NOT EXISTS)
-- Adds missing columns to `clubs` only if they do not exist.

SET @db := DATABASE();

-- address_line1
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN address_line1 VARCHAR(255) NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='address_line1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- address_line2
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN address_line2 VARCHAR(255) NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='address_line2'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- town
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN town VARCHAR(120) NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='town'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- county
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN county VARCHAR(120) NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='county'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- postcode
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN postcode VARCHAR(20) NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='postcode'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- country (default 'United Kingdom' to match your script)
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    "ALTER TABLE clubs ADD COLUMN country VARCHAR(120) NULL DEFAULT 'United Kingdom'",
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='country'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fishing_styles
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE clubs ADD COLUMN fishing_styles TEXT NULL',
    'SELECT 1'
  )
  FROM information_schema.columns
  WHERE table_schema=@db AND table_name='clubs' AND column_name='fishing_styles'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
