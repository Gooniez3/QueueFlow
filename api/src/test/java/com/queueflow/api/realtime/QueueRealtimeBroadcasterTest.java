package com.queueflow.api.realtime;

import org.junit.jupiter.api.Test;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

import java.time.OffsetDateTime;

import static org.assertj.core.api.Assertions.assertThat;

class QueueRealtimeBroadcasterTest {

    @Test
    void shouldCreateEmitterForSubscription() {
        QueueRealtimeBroadcaster broadcaster =
                new QueueRealtimeBroadcaster();

        SseEmitter emitter =
                broadcaster.subscribe(1L);

        assertThat(emitter).isNotNull();
    }

    @Test
    void shouldBroadcastWithoutSubscribers() {
        QueueRealtimeBroadcaster broadcaster =
                new QueueRealtimeBroadcaster();

        QueueRealtimeEvent event =
                new QueueRealtimeEvent(
                        QueueRealtimeEventType.JOIN,
                        10L,
                        20L,
                        30L,
                        40L,
                        OffsetDateTime.now()
                );

        broadcaster.broadcast(event);
    }
}