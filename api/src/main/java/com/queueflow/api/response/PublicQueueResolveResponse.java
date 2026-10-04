package com.queueflow.api.response;

import com.queueflow.api.entity.QueueStatus;

public record PublicQueueResolveResponse(
        String publicCode,
        Long businessId,
        String businessName,
        Long branchId,
        String branchName,
        Long serviceId,
        String serviceName,
        Long queueId,
        String queueName,
        QueueStatus queueStatus
) {
}