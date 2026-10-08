ALTER TABLE service_session
    ADD COLUMN check_in_start_time TIME,
    ADD COLUMN check_in_end_time TIME;

UPDATE service_session
SET check_in_start_time = start_time,
    check_in_end_time = end_time;

ALTER TABLE service_session
    ALTER COLUMN check_in_start_time SET NOT NULL,
    ALTER COLUMN check_in_end_time SET NOT NULL;

ALTER TABLE service_session
    ADD CONSTRAINT chk_service_session_checkin_window
        CHECK (
            check_in_end_time >= check_in_start_time
        );