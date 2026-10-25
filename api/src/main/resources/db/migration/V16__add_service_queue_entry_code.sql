ALTER TABLE service
    ADD COLUMN queue_entry_code VARCHAR(14);

LOCK TABLE service IN ACCESS EXCLUSIVE MODE;

DO $$
DECLARE
    current_service_id BIGINT;
    candidate_code VARCHAR(14);
BEGIN
    FOR current_service_id IN
        SELECT id
        FROM service
        WHERE queue_entry_code IS NULL
        ORDER BY id
    LOOP
        LOOP
            candidate_code := 'QF' || translate(
                substr(replace(gen_random_uuid()::text, '-', ''), 1, 12),
                '0123456789abcdef',
                'ABCDEFGHJKLMNPQR'
            );

            EXIT WHEN NOT EXISTS (
                SELECT 1
                FROM service
                WHERE queue_entry_code = candidate_code
            );
        END LOOP;

        UPDATE service
        SET queue_entry_code = candidate_code
        WHERE id = current_service_id;
    END LOOP;
END
$$;

ALTER TABLE service
    ALTER COLUMN queue_entry_code SET NOT NULL;

ALTER TABLE service
    ADD CONSTRAINT uq_service_queue_entry_code UNIQUE (queue_entry_code);

ALTER TABLE service
    ADD CONSTRAINT chk_service_queue_entry_code
        CHECK (queue_entry_code ~ '^QF[A-HJ-NP-Z2-9]{12}$');
