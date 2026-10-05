package com.queueflow.api.response;

public record PublicDiscoveryServiceResponse(
        Long serviceId,
        String name,
        String description,
        Integer durationMinutes
) {
}