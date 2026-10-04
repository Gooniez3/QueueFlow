package com.queueflow.api.service;

import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.response.PublicQueueBoardResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
public class PublicQueueBoardService {

    private final QueueRepository queueRepository;
    private final QueueEntryRepository queueEntryRepository;

    public PublicQueueBoardService(
            QueueRepository queueRepository,
            QueueEntryRepository queueEntryRepository
    ) {
        this.queueRepository = queueRepository;
        this.queueEntryRepository = queueEntryRepository;
    }

    @Transactional(readOnly = true)
    public PublicQueueBoardResponse getBoard(Long queueId) {

        Queue queue = queueRepository
                .findById(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: " + queueId
                        )
                );

        List<QueueEntry> servingEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queueId,
                                QueueEntryStatus.SERVING
                        );

        List<QueueEntry> calledEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queueId,
                                QueueEntryStatus.CALLED
                        );

        List<QueueEntry> waitingEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queueId,
                                QueueEntryStatus.WAITING
                        );

        String nowServing =
                servingEntries.isEmpty()
                        ? null
                        : formatTicketNumber(
                                queue,
                                servingEntries.get(0)
                        );

        String calling =
                calledEntries.isEmpty()
                        ? null
                        : formatTicketNumber(
                                queue,
                                calledEntries.get(0)
                        );

        List<String> upcomingTicketNumbers =
                waitingEntries.stream()
                        .limit(5)
                        .map(entry ->
                                formatTicketNumber(
                                        queue,
                                        entry
                                )
                        )
                        .toList();

        return new PublicQueueBoardResponse(
                queue.getId(),
                queue.getName(),
                queue.getStatus(),
                nowServing,
                calling,
                waitingEntries.size(),
                upcomingTicketNumbers
        );
    }

    private String formatTicketNumber(
            Queue queue,
            QueueEntry entry
    ) {
        return queue.getTicketPrefix()
                + String.format(
                        "%03d",
                        entry.getTicketSequence()
                );
    }
}