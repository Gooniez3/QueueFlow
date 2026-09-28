package com.queueflow.api.response;

public record HealthResponse(
        String status,
        String application
) {
}