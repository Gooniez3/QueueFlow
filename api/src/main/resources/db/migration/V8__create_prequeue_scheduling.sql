CREATE TABLE service_session (
    id BIGSERIAL PRIMARY KEY,
    service_id BIGINT NOT NULL,
    local_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    capacity INTEGER NOT NULL,
    booking_open BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_service_session_service
        FOREIGN KEY (service_id)
        REFERENCES service(id),

    CONSTRAINT chk_service_session_capacity_positive
        CHECK (capacity > 0),

    CONSTRAINT chk_service_session_time_range
        CHECK (end_time > start_time),

    CONSTRAINT uq_service_session_service_date_start
        UNIQUE (
            service_id,
            local_date,
            start_time
        )
);


CREATE TABLE pre_queue_reservation (
    id BIGSERIAL PRIMARY KEY,
    service_session_id BIGINT NOT NULL,
    user_id BIGINT,
    queue_entry_id BIGINT,
    reservation_code VARCHAR(36) NOT NULL,
    guest_token_hash VARCHAR(255),
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    cancelled_at TIMESTAMPTZ,
    checked_in_at TIMESTAMPTZ,

    CONSTRAINT fk_pre_queue_reservation_session
        FOREIGN KEY (service_session_id)
        REFERENCES service_session(id),

    CONSTRAINT fk_pre_queue_reservation_user
        FOREIGN KEY (user_id)
        REFERENCES user_account(id),

    CONSTRAINT fk_pre_queue_reservation_queue_entry
        FOREIGN KEY (queue_entry_id)
        REFERENCES queue_entry(id),

    CONSTRAINT uq_pre_queue_reservation_code
        UNIQUE (reservation_code),

    CONSTRAINT uq_pre_queue_reservation_queue_entry
        UNIQUE (queue_entry_id),

    CONSTRAINT chk_pre_queue_reservation_status
        CHECK (
            status IN (
                'RESERVED',
                'CHECKED_IN',
                'CANCELLED',
                'EXPIRED'
            )
        ),

    CONSTRAINT chk_pre_queue_reservation_owner
        CHECK (
            (user_id IS NOT NULL AND guest_token_hash IS NULL)
            OR
            (user_id IS NULL AND guest_token_hash IS NOT NULL)
        )
);


CREATE INDEX idx_service_session_availability
    ON service_session (
        service_id,
        local_date,
        booking_open
    );


CREATE INDEX idx_pre_queue_reservation_session_status
    ON pre_queue_reservation (
        service_session_id,
        status
    );