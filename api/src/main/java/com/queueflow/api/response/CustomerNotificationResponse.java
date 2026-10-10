package com.queueflow.api.response;

import com.queueflow.api.entity.CustomerNotificationType;
import java.time.OffsetDateTime;

public record CustomerNotificationResponse(
        Long id,
        CustomerNotificationType type,
        String title,
        String message,
        boolean read,
        OffsetDateTime createdAt,
        OffsetDateTime readAt
) {
}