package com.queueflow.api.realtime;

import java.time.OffsetDateTime;

public record QueueRealtimeNotification(
        Long queueId,
        QueueRealtimeEventType type,
        OffsetDateTime occurredAt
) {
}