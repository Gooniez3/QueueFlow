package com.queueflow.api.response;

public record StaffDashboardServiceResponse(
        Long id,
        String name,
        Integer durationMinutes
) {
}
