package com.queueflow.api.realtime;

import java.time.OffsetDateTime;

public record BranchRealtimeNotification(
        Long branchId,
        Long queueId,
        QueueRealtimeEventType type,
        OffsetDateTime occurredAt
) {
}