package com.queueflow.api.controller;

import com.queueflow.api.request.CreateQueueRequest;
import com.queueflow.api.response.QueueResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BusinessAuthorizationService;
import com.queueflow.api.service.QueueService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping(
        "/api/v1/businesses/{businessId}/branches/{branchId}/queues"
)
public class QueueController {

    private final QueueService queueService;
    private final BusinessAuthorizationService
            businessAuthorizationService;

    public QueueController(
            QueueService queueService,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.queueService = queueService;
        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @PostMapping
    public ResponseEntity<QueueResponse> createQueue(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody CreateQueueRequest request
    ) {

        businessAuthorizationService.requireMembership(
                principal.userId(),
                businessId
        );

        QueueResponse response =
                queueService.createQueue(
                        businessId,
                        branchId,
                        request
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }
}