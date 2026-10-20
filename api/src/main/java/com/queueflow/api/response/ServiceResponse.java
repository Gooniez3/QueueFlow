package com.queueflow.api.response;

import com.queueflow.api.entity.PartySizeMode;
import java.time.OffsetDateTime;

public record ServiceResponse(
        Long id,
        Long branchId,
        String name,
        String description,
        Integer durationMinutes,
        boolean active,
        OffsetDateTime createdAt,
        PartySizeMode partySizeMode,
        Integer maxPartySize
) {
    public ServiceResponse(
            Long id,
            Long branchId,
            String name,
            String description,
            Integer durationMinutes,
            boolean active,
            OffsetDateTime createdAt
    ) {
        this(
                id,
                branchId,
                name,
                description,
                durationMinutes,
                active,
                createdAt,
                PartySizeMode.DISABLED,
                20
        );
    }
}