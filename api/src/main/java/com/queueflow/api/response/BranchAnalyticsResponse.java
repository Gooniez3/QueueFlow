package com.queueflow.api.response;

import java.time.LocalDate;

public record BranchAnalyticsResponse(
        Long businessId,
        Long branchId,
        LocalDate from,
        LocalDate to,
        long totalEntries,
        long completedEntries,
        long cancelledEntries,
        long skippedEntries,
        long waitingEntries,
        long calledEntries,
        long servingEntries,
        double completionRatePercent,
        double cancellationRatePercent,
        Double averageWaitMinutes,
        Double averageServiceMinutes
) {
}
