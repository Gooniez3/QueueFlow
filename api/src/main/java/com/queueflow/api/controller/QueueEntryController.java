package com.queueflow.api.controller;

import com.queueflow.api.request.JoinQueueRequest;
import com.queueflow.api.response.QueueEntryResponse;
import com.queueflow.api.response.QueuePositionResponse;
import com.queueflow.api.response.QueueEntryQrCredentialResponse;
import com.queueflow.api.service.QueueEntryQrService;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.QueueService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/entries")
public class QueueEntryController {

    private final QueueService queueService;
    private final QueueEntryQrService queueEntryQrService;

    public QueueEntryController(
            QueueService queueService,
            QueueEntryQrService queueEntryQrService
    ) {
        this.queueService = queueService;
        this.queueEntryQrService = queueEntryQrService;
    }

    @PostMapping
    public ResponseEntity<QueueEntryResponse> joinQueue(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey,
            @RequestBody JoinQueueRequest request
    ) {

        Long userId =
                principal == null
                        ? null
                        : principal.userId();

        QueueEntryResponse response =
                queueService.joinQueue(
                        queueId,
                        userId,
                        idempotencyKey,
                        request
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping("/{entryId}/position")
    public ResponseEntity<QueuePositionResponse> getQueuePosition(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "X-Guest-Token",
                    required = false
            ) String guestToken
    ) {

        Long userId =
                principal == null
                        ? null
                        : principal.userId();

        QueuePositionResponse response =
                queueService.getQueuePosition(
                        queueId,
                        entryId,
                        userId,
                        guestToken
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/{entryId}/cancel")
    public ResponseEntity<QueueEntryResponse> cancelQueueEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "X-Guest-Token",
                    required = false
            ) String guestToken
    ) {

        Long userId =
                principal == null
                        ? null
                        : principal.userId();

        QueueEntryResponse response =
                queueService.cancelQueueEntry(
                        queueId,
                        entryId,
                        userId,
                        guestToken
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/{entryId}/qr-credential")
    public ResponseEntity<QueueEntryQrCredentialResponse> issueQrCredential(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "X-Guest-Token",
                    required = false
            ) String guestToken
    ) {

        Long userId =
                principal == null
                        ? null
                        : principal.userId();

        QueueEntryQrCredentialResponse response =
                queueEntryQrService.issueCredential(
                        queueId,
                        entryId,
                        userId,
                        guestToken
                );

        return ResponseEntity.ok(response);
    }
}
