package com.queueflow.api.controller;


import com.queueflow.api.realtime.QueueRealtimeBroadcaster;
import com.queueflow.api.repository.QueueRepository;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/events")
public class QueueRealtimeController {

    private final QueueRealtimeBroadcaster broadcaster;
    private final QueueRepository queueRepository;

    public QueueRealtimeController(
            QueueRealtimeBroadcaster broadcaster,
            QueueRepository queueRepository
    ) {
        this.broadcaster = broadcaster;
        this.queueRepository = queueRepository;
    }

    @GetMapping(
            produces = MediaType.TEXT_EVENT_STREAM_VALUE
    )
    public SseEmitter subscribe(
            @PathVariable Long queueId
    ) {
        if (!queueRepository.existsById(queueId)) {
           throw new org.springframework.web.server.ResponseStatusException(
                 org.springframework.http.HttpStatus.NOT_FOUND,
                "Queue not found with id: " + queueId
        );
    }

        return broadcaster.subscribe(queueId);
    }
}