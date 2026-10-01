package com.queueflow.api.repository;

import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import org.springframework.data.jpa.repository.JpaRepository;

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

    boolean existsByQueueIdAndUserIdAndStatusIn(
            Long queueId,
            Long userId,
            Collection<QueueEntryStatus> statuses
    );
}