package com.queueflow.api.realtime;

import org.springframework.stereotype.Service;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

import java.io.IOException;
import java.time.OffsetDateTime;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CopyOnWriteArrayList;

@Service
public class QueueRealtimeBroadcaster {

    private static final long SSE_TIMEOUT_MILLIS =
            30L * 60L * 1000L;

    private final Map<Long, List<SseEmitter>> emittersByQueue =
            new ConcurrentHashMap<>();

    private final Map<Long, List<SseEmitter>> emittersByBranch =
            new ConcurrentHashMap<>();

    public SseEmitter subscribe(Long queueId) {
        SseEmitter emitter =
                new SseEmitter(SSE_TIMEOUT_MILLIS);

        emittersByQueue
                .computeIfAbsent(
                        queueId,
                        ignored -> new CopyOnWriteArrayList<>()
                )
                .add(emitter);

        Runnable cleanup =
                () -> removeQueueEmitter(
                        queueId,
                        emitter
                );

        emitter.onCompletion(cleanup);
        emitter.onTimeout(cleanup);
        emitter.onError(
                ignored -> cleanup.run()
        );

        try {
            emitter.send(
                    SseEmitter.event()
                            .name("connected")
                            .data(
                                    new QueueRealtimeNotification(
                                            queueId,
                                            null,
                                            OffsetDateTime.now()
                                    )
                            )
            );
        } catch (IOException | IllegalStateException exception) {
            removeQueueEmitter(
                    queueId,
                    emitter
            );
        }

        return emitter;
    }

    public SseEmitter subscribeBranch(Long branchId) {
        SseEmitter emitter =
                new SseEmitter(SSE_TIMEOUT_MILLIS);

        emittersByBranch
                .computeIfAbsent(
                        branchId,
                        ignored -> new CopyOnWriteArrayList<>()
                )
                .add(emitter);

        Runnable cleanup =
                () -> removeBranchEmitter(
                        branchId,
                        emitter
                );

        emitter.onCompletion(cleanup);
        emitter.onTimeout(cleanup);
        emitter.onError(
                ignored -> cleanup.run()
        );

        try {
            emitter.send(
                    SseEmitter.event()
                            .name("connected")
                            .data(
                                    new BranchRealtimeNotification(
                                            branchId,
                                            null,
                                            null,
                                            OffsetDateTime.now()
                                    )
                            )
            );
        } catch (IOException | IllegalStateException exception) {
            removeBranchEmitter(
                    branchId,
                    emitter
            );
        }

        return emitter;
    }

    public void broadcast(
            QueueRealtimeEvent event
    ) {
        broadcastToQueue(event);
        broadcastToBranch(event);
    }

    private void broadcastToQueue(
            QueueRealtimeEvent event
    ) {
        List<SseEmitter> emitters =
                emittersByQueue.get(event.queueId());

        if (emitters == null) {
            return;
        }

        for (SseEmitter emitter : emitters) {
            try {
                emitter.send(
                        SseEmitter.event()
                                .name("queue-update")
                                .data(
                                        new QueueRealtimeNotification(
                                                event.queueId(),
                                                event.type(),
                                                event.occurredAt()
                                        )
                                )
                );
            } catch (IOException | IllegalStateException exception) {
                removeQueueEmitter(
                        event.queueId(),
                        emitter
                );
            }
        }
    }

    private void broadcastToBranch(
            QueueRealtimeEvent event
    ) {
        List<SseEmitter> emitters =
                emittersByBranch.get(event.branchId());

        if (emitters == null) {
            return;
        }

        for (SseEmitter emitter : emitters) {
            try {
                emitter.send(
                        SseEmitter.event()
                                .name("branch-update")
                                .data(
                                        new BranchRealtimeNotification(
                                                event.branchId(),
                                                event.queueId(),
                                                event.type(),
                                                event.occurredAt()
                                        )
                                )
                );
            } catch (IOException | IllegalStateException exception) {
                removeBranchEmitter(
                        event.branchId(),
                        emitter
                );
            }
        }
    }

    private void removeQueueEmitter(
            Long queueId,
            SseEmitter emitter
    ) {
        List<SseEmitter> emitters =
                emittersByQueue.get(queueId);

        if (emitters == null) {
            return;
        }

        emitters.remove(emitter);

        if (emitters.isEmpty()) {
            emittersByQueue.remove(
                    queueId,
                    emitters
            );
        }
    }

    private void removeBranchEmitter(
            Long branchId,
            SseEmitter emitter
    ) {
        List<SseEmitter> emitters =
                emittersByBranch.get(branchId);

        if (emitters == null) {
            return;
        }

        emitters.remove(emitter);

        if (emitters.isEmpty()) {
            emittersByBranch.remove(
                    branchId,
                    emitters
            );
        }
    }
}