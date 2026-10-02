package com.queueflow.api.response;

import com.queueflow.api.entity.QueueStatus;

import java.time.LocalDate;
import java.time.OffsetDateTime;

public record QueueResponse(
        Long id,
        Long branchId,
        Long serviceId,
        String name,
        LocalDate businessDate,
        String ticketPrefix,
        Integer nextTicketSequence,
        QueueStatus status,
        OffsetDateTime openedAt,
        OffsetDateTime closedAt,
        OffsetDateTime createdAt,
        OffsetDateTime updatedAt
) {
}