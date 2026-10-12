package com.queueflow.api.response;

public record BusinessBranchAnalyticsResponse(
        Long branchId,
        long totalEntries,
        long completedEntries,
        long cancelledEntries,
        long skippedEntries
) {
}