CREATE TABLE staff_mutation_idempotency (
    id BIGSERIAL PRIMARY KEY,
    queue_id BIGINT NOT NULL,
    staff_user_id BIGINT NOT NULL,
    queue_entry_id BIGINT NOT NULL,
    operation VARCHAR(30) NOT NULL,
    idempotency_key VARCHAR(36) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_staff_mutation_queue
        FOREIGN KEY (queue_id)
        REFERENCES queue(id),

    CONSTRAINT fk_staff_mutation_staff_user
        FOREIGN KEY (staff_user_id)
        REFERENCES user_account(id),

    CONSTRAINT fk_staff_mutation_queue_entry
        FOREIGN KEY (queue_entry_id)
        REFERENCES queue_entry(id),

    CONSTRAINT uq_staff_mutation_queue_staff_key
        UNIQUE (
            queue_id,
            staff_user_id,
            idempotency_key
        )
);

CREATE INDEX idx_staff_mutation_expires_at
    ON staff_mutation_idempotency(expires_at);
