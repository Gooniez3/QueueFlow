package com.queueflow.api.response;

public record ServiceAnalyticsResponse(
        Long serviceId,
        long totalEntries,
        long completedEntries,
        long cancelledEntries,
        long skippedEntries,
        Double averageWaitMinutes,
        Double averageServiceMinutes
) {
}