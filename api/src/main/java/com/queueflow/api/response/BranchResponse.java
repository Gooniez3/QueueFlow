package com.queueflow.api.response;

import java.math.BigDecimal;
import java.time.OffsetDateTime;

public record BranchResponse(
        Long id,
        String publicCode,
        Long businessId,
        String name,
        String address,
        BigDecimal latitude,
        BigDecimal longitude,
        String timezone,
        OffsetDateTime createdAt
) {
}