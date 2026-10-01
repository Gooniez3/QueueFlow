package com.queueflow.api.response;

import java.math.BigDecimal;
import java.time.OffsetDateTime;

public record BranchResponse(
        Long id,
        Long businessId,
        String name,
        String address,
        BigDecimal latitude,
        BigDecimal longitude,
        OffsetDateTime createdAt
) {
}