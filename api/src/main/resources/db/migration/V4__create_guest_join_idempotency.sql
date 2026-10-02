CREATE TABLE guest_join_idempotency (
    id BIGSERIAL PRIMARY KEY,

    queue_id BIGINT NOT NULL,
    queue_entry_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,

    idempotency_key VARCHAR(36) NOT NULL,

    encrypted_guest_token TEXT NOT NULL,

    created_at TIMESTAMPTZ NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_guest_join_idempotency_queue
        FOREIGN KEY (queue_id)
        REFERENCES queue(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_guest_join_idempotency_queue_entry
        FOREIGN KEY (queue_entry_id)
        REFERENCES queue_entry(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_guest_join_idempotency_service
        FOREIGN KEY (service_id)
        REFERENCES service(id),

    CONSTRAINT uq_guest_join_idempotency_queue_key
        UNIQUE (queue_id, idempotency_key)
);

CREATE INDEX idx_guest_join_idempotency_expires_at
    ON guest_join_idempotency(expires_at);