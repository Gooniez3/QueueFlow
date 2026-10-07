package com.queueflow.api.realtime;

import java.time.OffsetDateTime;

public record QueueRealtimeEvent(
        QueueRealtimeEventType type,
        Long businessId,
        Long branchId,
        Long queueId,
        Long entryId,
        OffsetDateTime occurredAt
) {
}