ALTER TABLE business
    ADD COLUMN category VARCHAR(100);

UPDATE business
SET category = 'OTHER'
WHERE category IS NULL;

ALTER TABLE business
    ALTER COLUMN category SET NOT NULL;