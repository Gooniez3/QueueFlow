ALTER TABLE service
    ADD COLUMN party_size_mode VARCHAR(20) NOT NULL DEFAULT 'DISABLED',
    ADD COLUMN max_party_size INTEGER NOT NULL DEFAULT 20;

ALTER TABLE service
    ADD CONSTRAINT chk_service_party_size_mode
        CHECK (party_size_mode IN ('DISABLED', 'OPTIONAL', 'REQUIRED')),
    ADD CONSTRAINT chk_service_max_party_size
        CHECK (max_party_size BETWEEN 1 AND 100);

ALTER TABLE queue_entry
    ADD COLUMN party_size INTEGER;

ALTER TABLE queue_entry
    ADD CONSTRAINT chk_queue_entry_party_size
        CHECK (party_size IS NULL OR party_size BETWEEN 1 AND 100);
