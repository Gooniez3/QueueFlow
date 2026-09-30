package com.queueflow.api.response;

import java.time.OffsetDateTime;

public record BusinessResponse(
        Long id,
        String name,
        String description,
        OffsetDateTime createdAt
) {
}