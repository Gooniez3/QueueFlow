ALTER TABLE business
    ADD COLUMN public_code VARCHAR(36);

ALTER TABLE branch
    ADD COLUMN public_code VARCHAR(36);

UPDATE business
SET public_code = 'biz_' || replace(gen_random_uuid()::text, '-', '')
WHERE public_code IS NULL;

UPDATE branch
SET public_code = 'br_' || replace(gen_random_uuid()::text, '-', '')
WHERE public_code IS NULL;

ALTER TABLE business
    ALTER COLUMN public_code SET NOT NULL;

ALTER TABLE branch
    ALTER COLUMN public_code SET NOT NULL;

ALTER TABLE business
    ADD CONSTRAINT uq_business_public_code UNIQUE (public_code);

ALTER TABLE branch
    ADD CONSTRAINT uq_branch_public_code UNIQUE (public_code);