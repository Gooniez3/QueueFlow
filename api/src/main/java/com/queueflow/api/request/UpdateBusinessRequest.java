package com.queueflow.api.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public record UpdateBusinessRequest(

        @NotBlank(message = "Business name is required")
        @Size(max = 150, message = "Business name must not exceed 150 characters")
        String name,

        String description,

        @Size(max = 100, message = "Business category must not exceed 100 characters")
        String category
) {
}