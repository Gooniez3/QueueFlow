package com.queueflow.api.response;

import com.queueflow.api.entity.BusinessCategory;
import java.time.OffsetDateTime;

public record BusinessResponse(
        Long id,
        String publicCode,
        String name,
        String description,
        BusinessCategory category,
        OffsetDateTime createdAt
) {
}
