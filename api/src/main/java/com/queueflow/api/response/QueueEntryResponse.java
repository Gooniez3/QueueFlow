package com.queueflow.api.response;

import com.queueflow.api.entity.QueueEntryStatus;

import java.time.OffsetDateTime;

public record QueueEntryResponse(
        Long id,
        Long queueId,
        Long serviceId,
        Long userId,
        Integer ticketSequence,
        String ticketNumber,
        QueueEntryStatus status,
        OffsetDateTime joinedAt,
        String guestToken
) {
}