package com.queueflow.api.request;

import com.queueflow.api.entity.PartySizeMode;
import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
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
        Boolean active,

        PartySizeMode partySizeMode,

        @Min(value = 1, message = "Maximum party size must be at least 1")
        @Max(value = 100, message = "Maximum party size must not exceed 100")
        Integer maxPartySize

) {
    public UpdateServiceRequest(
            String name,
            String description,
            Integer durationMinutes,
            Boolean active
    ) {
        this(name, description, durationMinutes, active, null, null);
    }
}