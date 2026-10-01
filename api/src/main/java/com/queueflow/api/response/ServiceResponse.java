package com.queueflow.api.response;

import java.time.OffsetDateTime;

public record ServiceResponse(
        Long id,
        Long branchId,
        String name,
        String description,
        Integer durationMinutes,
        boolean active,
        OffsetDateTime createdAt
) {
}