package com.queueflow.api.realtime;

import org.springframework.stereotype.Component;
import org.springframework.transaction.event.TransactionPhase;
import org.springframework.transaction.event.TransactionalEventListener;

@Component
public class QueueRealtimeEventListener {

    private final QueueRealtimeBroadcaster broadcaster;

    public QueueRealtimeEventListener(
            QueueRealtimeBroadcaster broadcaster
    ) {
        this.broadcaster = broadcaster;
    }

    @TransactionalEventListener(
            phase = TransactionPhase.AFTER_COMMIT
    )
    public void onQueueRealtimeEvent(
            QueueRealtimeEvent event
    ) {
        broadcaster.broadcast(event);
    }
}