package com.queueflow.api.request;

import jakarta.validation.constraints.NotBlank;

public record VerifyQueueEntryQrRequest(

        @NotBlank(message = "QR credential is required")
        String credential

) {
}
