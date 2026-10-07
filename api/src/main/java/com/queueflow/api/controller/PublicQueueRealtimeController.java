package com.queueflow.api.controller;

import com.queueflow.api.entity.Queue;
import com.queueflow.api.realtime.QueueRealtimeBroadcaster;
import com.queueflow.api.repository.QueueRepository;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.server.ResponseStatusException;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

import static org.springframework.http.HttpStatus.NOT_FOUND;

@RestController
@RequestMapping("/api/v1/public/queues")
public class PublicQueueRealtimeController {

    private final QueueRealtimeBroadcaster broadcaster;
    private final QueueRepository queueRepository;

    public PublicQueueRealtimeController(
            QueueRealtimeBroadcaster broadcaster,
            QueueRepository queueRepository
    ) {
        this.broadcaster = broadcaster;
        this.queueRepository = queueRepository;
    }

    @GetMapping(
            value = "/{publicCode}/events",
            produces = MediaType.TEXT_EVENT_STREAM_VALUE
    )
    public SseEmitter subscribe(
            @PathVariable String publicCode
    ) {
        Queue queue =
                queueRepository.findByPublicCode(publicCode)
                        .orElseThrow(() ->
                                new ResponseStatusException(
                                        NOT_FOUND,
                                        "Queue not found for public code"
                                )
                        );

        return broadcaster.subscribe(queue.getId());
    }
}