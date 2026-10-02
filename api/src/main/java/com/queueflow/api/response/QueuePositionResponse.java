package com.queueflow.api.response;

import com.queueflow.api.entity.QueueEntryStatus;

public record QueuePositionResponse(
        Long entryId,
        Long queueId,
        Long serviceId,
        Integer ticketSequence,
        String ticketNumber,
        QueueEntryStatus status,
        Integer peopleAhead,
        Integer estimatedWaitMinutes
) {
}