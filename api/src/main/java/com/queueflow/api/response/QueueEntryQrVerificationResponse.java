package com.queueflow.api.response;

import com.queueflow.api.entity.QueueEntryStatus;

public record QueueEntryQrVerificationResponse(
        Long entryId,
        Long queueId,
        Long businessId,
        Long branchId,
        String branchName,
        Long serviceId,
        String serviceName,
        String ticketNumber,
        QueueEntryStatus status
) {
}
