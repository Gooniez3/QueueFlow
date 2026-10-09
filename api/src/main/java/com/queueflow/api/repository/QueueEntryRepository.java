package com.queueflow.api.repository;

import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import org.springframework.data.jpa.repository.JpaRepository;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.Collection;
import java.util.List;
import java.util.Optional;

public interface QueueEntryRepository
        extends JpaRepository<QueueEntry, Long> {

    List<QueueEntry> findByQueueIdOrderByTicketSequenceAsc(
            Long queueId
    );

    List<QueueEntry> findByQueueIdAndStatusOrderByTicketSequenceAsc(
            Long queueId,
            QueueEntryStatus status
    );

    Optional<QueueEntry> findFirstByQueueIdAndStatusOrderByTicketSequenceAsc(
            Long queueId,
            QueueEntryStatus status
    );

    long countByQueueIdAndStatusAndTicketSequenceLessThan(
            Long queueId,
            QueueEntryStatus status,
            Integer ticketSequence
    );

    List<QueueEntry> findByQueueIdAndStatusInAndTicketSequenceLessThanOrderByTicketSequenceAsc(
            Long queueId,
            Collection<QueueEntryStatus> statuses,
            Integer ticketSequence
    );

    boolean existsByQueueIdAndStatus(
            Long queueId,
            QueueEntryStatus status
    );

    boolean existsByQueueIdAndUserIdAndStatusIn(
            Long queueId,
            Long userId,
            Collection<QueueEntryStatus> statuses
    );

    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("SELECT e FROM QueueEntry e WHERE e.id = :entryId")
    Optional<QueueEntry> findByIdForUpdate(@Param("entryId") Long entryId);
}