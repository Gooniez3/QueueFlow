package com.queueflow.api.repository;

import com.queueflow.api.entity.StaffMutationIdempotency;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface StaffMutationIdempotencyRepository
        extends JpaRepository<StaffMutationIdempotency, Long> {

    Optional<StaffMutationIdempotency>
    findByQueueIdAndStaffUserIdAndIdempotencyKey(
            Long queueId,
            Long staffUserId,
            String idempotencyKey
    );
}
