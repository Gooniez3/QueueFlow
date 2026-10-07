CREATE TABLE queue_entry_qr_credential (
    id BIGSERIAL PRIMARY KEY,

    queue_entry_id BIGINT NOT NULL,

    credential_hash VARCHAR(64) NOT NULL,

    expires_at TIMESTAMPTZ NOT NULL,

    revoked_at TIMESTAMPTZ,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_qr_credential_queue_entry
        FOREIGN KEY (queue_entry_id)
        REFERENCES queue_entry(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_qr_credential_hash
        UNIQUE (credential_hash)
);

CREATE INDEX idx_qr_credential_queue_entry
    ON queue_entry_qr_credential(queue_entry_id);

CREATE INDEX idx_qr_credential_expires_at
    ON queue_entry_qr_credential(expires_at);
