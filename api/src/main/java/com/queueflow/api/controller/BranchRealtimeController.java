package com.queueflow.api.controller;

import com.queueflow.api.realtime.QueueRealtimeBroadcaster;
import com.queueflow.api.repository.BranchRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.server.ResponseStatusException;
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter;

@RestController
@RequestMapping(
        "/api/v1/businesses/{businessId}/branches/{branchId}/events"
)
public class BranchRealtimeController {

    private final QueueRealtimeBroadcaster broadcaster;
    private final BranchRepository branchRepository;

    public BranchRealtimeController(
            QueueRealtimeBroadcaster broadcaster,
            BranchRepository branchRepository
    ) {
        this.broadcaster = broadcaster;
        this.branchRepository = branchRepository;
    }

    @GetMapping(
            produces = MediaType.TEXT_EVENT_STREAM_VALUE
    )
    public SseEmitter subscribe(
            @PathVariable Long businessId,
            @PathVariable Long branchId
    ) {
        boolean exists =
                branchRepository
                        .findById(branchId)
                        .filter(
                                branch ->
                                        branch.getBusiness()
                                                .getId()
                                                .equals(businessId)
                        )
                        .isPresent();

        if (!exists) {
            throw new ResponseStatusException(
                    HttpStatus.NOT_FOUND,
                    "Branch not found"
            );
        }

        return broadcaster.subscribeBranch(branchId);
    }
}