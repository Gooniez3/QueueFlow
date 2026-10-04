package com.queueflow.api.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Positive;
import jakarta.validation.constraints.Size;

public record UpdateServiceRequest(

        @NotBlank(message = "Service name is required")
        @Size(
                max = 150,
                message = "Service name must not exceed 150 characters"
        )
        String name,

        String description,

        @NotNull(message = "Duration is required")
        @Positive(message = "Duration must be greater than 0")
        Integer durationMinutes,

        @NotNull(message = "Active status is required")
        Boolean active
) {
}
