package com.queueflow.api.response;

import com.queueflow.api.entity.QueueEntryStatus;

public record QueuePositionResponse(
        Long entryId,
        Long queueId,
        String queueName,
        Long businessId,
        String businessName,
        Long branchId,
        String branchName,
        Long serviceId,
        String serviceName,
        Integer ticketSequence,
        String ticketNumber,
        QueueEntryStatus status,
        Integer peopleAhead,
        Integer estimatedWaitMinutes
) {
}