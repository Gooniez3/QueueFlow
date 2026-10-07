package com.queueflow.api.realtime;

import org.junit.jupiter.api.Test;
import org.mockito.ArgumentCaptor;
import org.springframework.test.util.ReflectionTestUtils;
import org.springframework.web.servlet.mvc.method.annotation.ResponseBodyEmitter;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

import java.time.OffsetDateTime;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

import static org.assertj.core.api.Assertions.assertThat;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.verify;

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

        broadcaster.broadcast(
                createEvent()
        );
    }

    @Test
    void shouldRouteQueueUpdateToMatchingQueueSubscriber()
            throws Exception {

        QueueRealtimeBroadcaster broadcaster =
                new QueueRealtimeBroadcaster();

        SseEmitter matchingEmitter =
                mock(SseEmitter.class);

        SseEmitter unrelatedEmitter =
                mock(SseEmitter.class);

        Map<Long, List<SseEmitter>> emittersByQueue =
                new ConcurrentHashMap<>();

        emittersByQueue.put(
                30L,
                List.of(matchingEmitter)
        );

        emittersByQueue.put(
                999L,
                List.of(unrelatedEmitter)
        );

        ReflectionTestUtils.setField(
                broadcaster,
                "emittersByQueue",
                emittersByQueue
        );

        broadcaster.broadcast(
                createEvent()
        );

        ArgumentCaptor<SseEmitter.SseEventBuilder> captor =
                ArgumentCaptor.forClass(
                        SseEmitter.SseEventBuilder.class
                );

        verify(matchingEmitter)
                .send(captor.capture());

        assertEventName(
                captor.getValue(),
                "queue-update"
        );

        verify(unrelatedEmitter, never())
                .send(
                        any(SseEmitter.SseEventBuilder.class)
                );
    }

    @Test
    void shouldRouteBranchUpdateToMatchingBranchSubscriber()
            throws Exception {

        QueueRealtimeBroadcaster broadcaster =
                new QueueRealtimeBroadcaster();

        SseEmitter matchingEmitter =
                mock(SseEmitter.class);

        SseEmitter unrelatedEmitter =
                mock(SseEmitter.class);

        Map<Long, List<SseEmitter>> emittersByBranch =
                new ConcurrentHashMap<>();

        emittersByBranch.put(
                20L,
                List.of(matchingEmitter)
        );

        emittersByBranch.put(
                999L,
                List.of(unrelatedEmitter)
        );

        ReflectionTestUtils.setField(
                broadcaster,
                "emittersByBranch",
                emittersByBranch
        );

        broadcaster.broadcast(
                createEvent()
        );

        ArgumentCaptor<SseEmitter.SseEventBuilder> captor =
                ArgumentCaptor.forClass(
                        SseEmitter.SseEventBuilder.class
                );

        verify(matchingEmitter)
                .send(captor.capture());

        assertEventName(
                captor.getValue(),
                "branch-update"
        );

        verify(unrelatedEmitter, never())
                .send(
                        any(SseEmitter.SseEventBuilder.class)
                );
    }

    @Test
    void shouldNotRouteEventToUnrelatedQueueOrBranchSubscribers()
            throws Exception {

        QueueRealtimeBroadcaster broadcaster =
                new QueueRealtimeBroadcaster();

        SseEmitter unrelatedQueueEmitter =
                mock(SseEmitter.class);

        SseEmitter unrelatedBranchEmitter =
                mock(SseEmitter.class);

        Map<Long, List<SseEmitter>> emittersByQueue =
                new ConcurrentHashMap<>();

        Map<Long, List<SseEmitter>> emittersByBranch =
                new ConcurrentHashMap<>();

        emittersByQueue.put(
                999L,
                List.of(unrelatedQueueEmitter)
        );

        emittersByBranch.put(
                888L,
                List.of(unrelatedBranchEmitter)
        );

        ReflectionTestUtils.setField(
                broadcaster,
                "emittersByQueue",
                emittersByQueue
        );

        ReflectionTestUtils.setField(
                broadcaster,
                "emittersByBranch",
                emittersByBranch
        );

        broadcaster.broadcast(
                createEvent()
        );

        verify(unrelatedQueueEmitter, never())
                .send(
                        any(SseEmitter.SseEventBuilder.class)
                );

        verify(unrelatedBranchEmitter, never())
                .send(
                        any(SseEmitter.SseEventBuilder.class)
                );
    }

    private void assertEventName(
            SseEmitter.SseEventBuilder builder,
            String expectedEventName
    ) {

        Set<ResponseBodyEmitter.DataWithMediaType> pieces =
                builder.build();

        assertThat(pieces)
                .anySatisfy(
                        piece ->
                                assertThat(
                                        piece.getData()
                                                .toString()
                                ).contains(
                                        "event:"
                                                + expectedEventName
                                )
                );
    }

    private QueueRealtimeEvent createEvent() {
        return new QueueRealtimeEvent(
                QueueRealtimeEventType.JOIN,
                10L,
                20L,
                30L,
                40L,
                OffsetDateTime.now()
        );
    }
}
