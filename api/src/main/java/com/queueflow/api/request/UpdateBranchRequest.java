package com.queueflow.api.request;

import jakarta.validation.constraints.DecimalMax;
import jakarta.validation.constraints.DecimalMin;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

import java.math.BigDecimal;

public record UpdateBranchRequest(

        @NotBlank(message = "Branch name is required")
        @Size(
                max = 150,
                message = "Branch name must not exceed 150 characters"
        )
        String name,

        @NotBlank(message = "Address is required")
        String address,

        @DecimalMin(
                value = "-90.0",
                message = "Latitude must be at least -90"
        )
        @DecimalMax(
                value = "90.0",
                message = "Latitude must not exceed 90"
        )
        BigDecimal latitude,

        @DecimalMin(
                value = "-180.0",
                message = "Longitude must be at least -180"
        )
        @DecimalMax(
                value = "180.0",
                message = "Longitude must not exceed 180"
        )
        BigDecimal longitude,

        @Size(
                max = 100,
                message = "Timezone must not exceed 100 characters"
        )
        String timezone
) {
}
