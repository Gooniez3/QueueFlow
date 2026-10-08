ALTER TABLE pre_queue_reservation
    DROP CONSTRAINT fk_pre_queue_reservation_session;

ALTER TABLE pre_queue_reservation
    ADD CONSTRAINT fk_pre_queue_reservation_session
        FOREIGN KEY (service_session_id)
        REFERENCES service_session(id)
        ON DELETE CASCADE;


ALTER TABLE service_session
    DROP CONSTRAINT fk_service_session_service;

ALTER TABLE service_session
    ADD CONSTRAINT fk_service_session_service
        FOREIGN KEY (service_id)
        REFERENCES service(id)
        ON DELETE CASCADE;
