package com.queueflow.api.response;

public record QueueEntryCodeResolveResponse(
        String entryCode,
        Long businessId,
        Long branchId,
        Long serviceId
) {
}
