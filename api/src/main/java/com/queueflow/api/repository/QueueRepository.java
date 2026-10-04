package com.queueflow.api.repository;

import com.queueflow.api.entity.Queue;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDate;
import java.util.List;
import java.util.Optional;

public interface QueueRepository extends JpaRepository<Queue, Long> {

    List<Queue> findByBranchIdAndBusinessDate(
            Long branchId,
            LocalDate businessDate
    );

    Optional<Queue> findByBranchIdAndServiceIsNullAndBusinessDate(
            Long branchId,
            LocalDate businessDate
    );

    Optional<Queue> findByBranchIdAndServiceIdAndBusinessDate(
            Long branchId,
            Long serviceId,
            LocalDate businessDate
    );

    Optional<Queue> findByPublicCode(
            String publicCode
    );

    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("""
            select q
            from Queue q
            where q.id = :queueId
            """)
    Optional<Queue> findByIdForUpdate(
            @Param("queueId") Long queueId
    );
}