package com.queueflow.api.response;

public record PeakHourResponse(
        int hour,
        long totalEntries
) {
}