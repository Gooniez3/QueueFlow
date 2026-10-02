package com.queueflow.api.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

public record CreateQueueRequest(

        Long serviceId,

        @NotBlank(message = "Queue name is required")
        @Size(
                max = 150,
                message = "Queue name must not exceed 150 characters"
        )
        String name,

        @NotBlank(message = "Ticket prefix is required")
        @Size(
                max = 10,
                message = "Ticket prefix must not exceed 10 characters"
        )
        String ticketPrefix
) {
}