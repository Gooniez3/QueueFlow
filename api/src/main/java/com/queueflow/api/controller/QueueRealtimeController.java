package com.queueflow.api.controller;

import com.queueflow.api.entity.Queue;
import com.queueflow.api.realtime.QueueRealtimeBroadcaster;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BusinessAuthorizationService;
import org.springframework.http.MediaType;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.server.ResponseStatusException;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

import static org.springframework.http.HttpStatus.NOT_FOUND;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/events")
public class QueueRealtimeController {

    private final QueueRealtimeBroadcaster broadcaster;
    private final QueueRepository queueRepository;
    private final BusinessAuthorizationService businessAuthorizationService;

    public QueueRealtimeController(
            QueueRealtimeBroadcaster broadcaster,
            QueueRepository queueRepository,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.broadcaster = broadcaster;
        this.queueRepository = queueRepository;
        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @GetMapping(
            produces = MediaType.TEXT_EVENT_STREAM_VALUE
    )
    public SseEmitter subscribe(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        Queue queue =
                queueRepository.findByIdWithBranchAndBusiness(queueId)
                        .orElseThrow(() ->
                                new ResponseStatusException(
                                        NOT_FOUND,
                                        "Queue not found with id: " + queueId
                                )
                        );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService.requireBranchAccess(
                principal.userId(),
                businessId,
                branchId
        );

        return broadcaster.subscribe(queueId);
    }
}