CREATE TABLE business (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL
);

CREATE TABLE branch (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(9,6),
    longitude DECIMAL(9,6),
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_branch_business
        FOREIGN KEY (business_id)
        REFERENCES business(id),

    CONSTRAINT chk_branch_latitude
        CHECK (latitude IS NULL OR latitude BETWEEN -90 AND 90),

    CONSTRAINT chk_branch_longitude
        CHECK (longitude IS NULL OR longitude BETWEEN -180 AND 180)
);

CREATE TABLE service (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    duration_minutes INTEGER NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_service_branch
        FOREIGN KEY (branch_id)
        REFERENCES branch(id),

    CONSTRAINT chk_service_duration_positive
        CHECK (duration_minutes > 0)
);

CREATE TABLE queue (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    service_id BIGINT,
    name VARCHAR(150) NOT NULL,
    business_date DATE NOT NULL,
    ticket_prefix VARCHAR(10) NOT NULL,
    next_ticket_sequence INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(30) NOT NULL,
    opened_at TIMESTAMPTZ NOT NULL,
    closed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_queue_branch
        FOREIGN KEY (branch_id)
        REFERENCES branch(id),

    CONSTRAINT fk_queue_service
        FOREIGN KEY (service_id)
        REFERENCES service(id),

    CONSTRAINT chk_queue_next_ticket_sequence_positive
        CHECK (next_ticket_sequence > 0),

    CONSTRAINT chk_queue_status
        CHECK (status IN ('OPEN', 'PAUSED', 'CLOSED'))
);

CREATE UNIQUE INDEX uq_queue_shared_branch_business_date
    ON queue (branch_id, business_date)
    WHERE service_id IS NULL;

CREATE UNIQUE INDEX uq_queue_service_business_date
    ON queue (service_id, business_date)
    WHERE service_id IS NOT NULL;


CREATE TABLE user_account (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL
);

CREATE TABLE staff_membership (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    business_id BIGINT NOT NULL,
    branch_id BIGINT,
    role VARCHAR(50) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_staff_membership_user
        FOREIGN KEY (user_id)
        REFERENCES user_account(id),
    
    CONSTRAINT fk_staff_membership_business
        FOREIGN KEY (business_id)
        REFERENCES business(id),
    
    CONSTRAINT fk_staff_membership_branch
        FOREIGN KEY (branch_id)
        REFERENCES branch(id),

    CONSTRAINT chk_staff_membership_role
        CHECK (role IN ('STAFF', 'MANAGER', 'OWNER'))   
);

CREATE TABLE counter(
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    name VARCHAR(100) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_counter_branch
        FOREIGN KEY (branch_id)
        REFERENCES branch(id)
);

CREATE TABLE queue_entry (
    id BIGSERIAL PRIMARY KEY,
    queue_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    user_id BIGINT,
    counter_id BIGINT,
    ticket_sequence INTEGER NOT NULL,
    guest_token_hash VARCHAR(255),
    status VARCHAR(30) NOT NULL,
    joined_at TIMESTAMPTZ NOT NULL,
    called_at TIMESTAMPTZ,
    serving_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    cancelled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    CONSTRAINT fk_queue_entry_queue
        FOREIGN KEY (queue_id)
        REFERENCES queue(id),

    CONSTRAINT fk_queue_entry_service
        FOREIGN KEY (service_id)
        REFERENCES service(id),

    CONSTRAINT fk_queue_entry_user
        FOREIGN KEY (user_id)
        REFERENCES user_account(id),

    CONSTRAINT fk_queue_entry_counter
        FOREIGN KEY (counter_id)
        REFERENCES counter(id),

    CONSTRAINT uq_queue_entry_ticket_sequence
        UNIQUE (queue_id, ticket_sequence),

    CONSTRAINT chk_queue_entry_ticket_sequence_positive
        CHECK (ticket_sequence > 0),

    CONSTRAINT chk_queue_entry_status
        CHECK (status IN (
            'WAITING',
            'CALLED',
            'SERVING',
            'COMPLETED',
            'CANCELLED',
            'SKIPPED'
        ))
);