package com.queueflow.api.repository;

import com.queueflow.api.entity.GuestJoinIdempotency;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface GuestJoinIdempotencyRepository
        extends JpaRepository<GuestJoinIdempotency, Long> {

    Optional<GuestJoinIdempotency> findByQueueIdAndIdempotencyKey(
            Long queueId,
            String idempotencyKey
    );
}