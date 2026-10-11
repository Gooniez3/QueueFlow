CREATE TABLE customer_notification (
    id BIGSERIAL PRIMARY KEY,

    queue_entry_id BIGINT NOT NULL,

    type VARCHAR(40) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,

    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMPTZ,

    CONSTRAINT fk_customer_notification_queue_entry
        FOREIGN KEY (queue_entry_id)
        REFERENCES queue_entry(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_customer_notification_type
        CHECK (type IN (
            'TICKET_CALLED',
            'TICKET_RECALLED'
        ))
);

CREATE INDEX idx_customer_notification_entry_created
    ON customer_notification (queue_entry_id, created_at DESC, id DESC);

CREATE INDEX idx_customer_notification_entry_unread
    ON customer_notification (queue_entry_id, created_at DESC)
    WHERE is_read = FALSE;
