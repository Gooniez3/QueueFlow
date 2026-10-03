package com.queueflow.api.response;

public record StaffDashboardCountsResponse(
        long waiting,
        long called,
        long serving
) {
}
