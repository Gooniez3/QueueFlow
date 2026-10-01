CREATE TABLE auth_session (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMPTZ,

    CONSTRAINT fk_auth_session_user
        FOREIGN KEY (user_id)
        REFERENCES user_account(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_auth_session_token_hash
        UNIQUE (token_hash)
);

CREATE INDEX idx_auth_session_user_id
    ON auth_session(user_id);

CREATE INDEX idx_auth_session_expires_at
    ON auth_session(expires_at);