package com.queueflow.api.realtime;

import com.queueflow.api.service.CustomerNotificationService;
import org.springframework.stereotype.Component;
import org.springframework.transaction.event.TransactionPhase;
import org.springframework.transaction.event.TransactionalEventListener;

@Component
public class QueueRealtimeEventListener {

    private final QueueRealtimeBroadcaster broadcaster;
    private final CustomerNotificationService notificationService;

    public QueueRealtimeEventListener(
            QueueRealtimeBroadcaster broadcaster,
            CustomerNotificationService notificationService
    ) {
        this.broadcaster = broadcaster;
        this.notificationService = notificationService;
    }

    @TransactionalEventListener(
            phase = TransactionPhase.AFTER_COMMIT
    )
    public void onQueueRealtimeEvent(QueueRealtimeEvent event) {
        broadcaster.broadcast(event);
    }

    @TransactionalEventListener(
            phase = TransactionPhase.AFTER_COMMIT
    )
    public void onCustomerNotificationEvent(QueueRealtimeEvent event) {
        try {
            notificationService.createForQueueEvent(event);
        } catch (Exception exception) {
            // Notification failures must not interrupt queue operations.
            org.slf4j.LoggerFactory
                    .getLogger(QueueRealtimeEventListener.class)
                    .error(
                            "Failed to create customer notification for entry {}",
                            event.entryId(),
                            exception
                    );
        }
    }
}