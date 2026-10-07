package com.queueflow.api.realtime;

import org.junit.jupiter.api.Test;
import org.springframework.transaction.event.TransactionPhase;
import org.springframework.transaction.event.TransactionalEventListener;

import java.lang.reflect.Method;

import static org.assertj.core.api.Assertions.assertThat;

class QueueRealtimeEventListenerTest {

    @Test
    void shouldBroadcastOnlyAfterTransactionCommit()
            throws Exception {

        Method method =
                QueueRealtimeEventListener.class
                        .getDeclaredMethod(
                                "onQueueRealtimeEvent",
                                QueueRealtimeEvent.class
                        );

        TransactionalEventListener annotation =
                method.getAnnotation(
                        TransactionalEventListener.class
                );

        assertThat(annotation)
                .isNotNull();

        assertThat(annotation.phase())
                .isEqualTo(
                        TransactionPhase.AFTER_COMMIT
                );
    }
}