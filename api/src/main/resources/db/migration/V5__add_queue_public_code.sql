ALTER TABLE queue
    ADD COLUMN public_code VARCHAR(36);

UPDATE queue
SET public_code = gen_random_uuid()::text
WHERE public_code IS NULL;

ALTER TABLE queue
    ALTER COLUMN public_code SET NOT NULL;

ALTER TABLE queue
    ADD CONSTRAINT uq_queue_public_code UNIQUE (public_code);