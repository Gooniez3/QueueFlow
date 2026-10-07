package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.realtime.QueueRealtimeBroadcaster;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BusinessAuthorizationService;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
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
    private final BusinessAuthorizationService businessAuthorizationService;

    public BranchRealtimeController(
            QueueRealtimeBroadcaster broadcaster,
            BranchRepository branchRepository,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.broadcaster = broadcaster;
        this.branchRepository = branchRepository;
        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @GetMapping(
            produces = MediaType.TEXT_EVENT_STREAM_VALUE
    )
    public SseEmitter subscribe(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        Branch branch =
                branchRepository
                        .findById(branchId)
                        .filter(
                                value ->
                                        value.getBusiness()
                                                .getId()
                                                .equals(businessId)
                        )
                        .orElseThrow(() ->
                                new ResponseStatusException(
                                        HttpStatus.NOT_FOUND,
                                        "Branch not found"
                                )
                        );

        businessAuthorizationService.requireBranchAccess(
                principal.userId(),
                businessId,
                branch.getId()
        );

        return broadcaster.subscribeBranch(branchId);
    }
}