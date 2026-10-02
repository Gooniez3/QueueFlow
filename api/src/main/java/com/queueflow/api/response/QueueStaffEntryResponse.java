package com.queueflow.api.response;

import com.queueflow.api.entity.QueueEntryStatus;

import java.time.OffsetDateTime;

public record QueueStaffEntryResponse(
        Long entryId,
        Long queueId,
        Long serviceId,
        Long userId,
        Long counterId,
        Integer ticketSequence,
        String ticketNumber,
        QueueEntryStatus status,
        OffsetDateTime joinedAt,
        OffsetDateTime calledAt,
        OffsetDateTime servingAt,
        OffsetDateTime completedAt,
        OffsetDateTime cancelledAt
) {
}