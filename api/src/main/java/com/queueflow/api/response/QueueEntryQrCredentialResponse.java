package com.queueflow.api.response;

import java.time.OffsetDateTime;

public record QueueEntryQrCredentialResponse(
        String credential,
        OffsetDateTime expiresAt
) {
}
