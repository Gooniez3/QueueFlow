package com.queueflow.api.response;

import java.time.LocalDate;

public record DailyTrendResponse(
        LocalDate date,
        long totalEntries,
        long completedEntries,
        long cancelledEntries,
        long skippedEntries
) {
}