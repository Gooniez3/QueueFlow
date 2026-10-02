package com.queueflow.api.response;

import com.queueflow.api.entity.QueueStatus;

import java.time.LocalDate;

public record PublicQueueResponse(
        Long id,
        Long branchId,
        Long serviceId,
        String name,
        LocalDate businessDate,
        String ticketPrefix,
        QueueStatus status
) {
}