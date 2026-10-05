package com.queueflow.api.controller;

import com.queueflow.api.response.QueueResponse;
import com.queueflow.api.response.QueueStaffEntryResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.QueueService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/staff")
public class QueueStaffController {

    private final QueueService queueService;

    public QueueStaffController(
            QueueService queueService
    ) {
        this.queueService = queueService;
    }

    @PostMapping("/call-next")
    public ResponseEntity<QueueStaffEntryResponse> callNext(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey
    ) {

        QueueStaffEntryResponse response =
                queueService.callNext(
                        queueId,
                        principal.userId(),
                        idempotencyKey
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/recall")
    public ResponseEntity<QueueStaffEntryResponse> recallEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey
    ) {

        QueueStaffEntryResponse response =
                queueService.recallEntry(
                        queueId,
                        entryId,
                        principal.userId(),
                        idempotencyKey
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/start")
    public ResponseEntity<QueueStaffEntryResponse> startServing(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey
    ) {

        QueueStaffEntryResponse response =
                queueService.startServing(
                        queueId,
                        entryId,
                        principal.userId(),
                        idempotencyKey
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/complete")
    public ResponseEntity<QueueStaffEntryResponse> completeEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey
    ) {

        QueueStaffEntryResponse response =
                queueService.completeEntry(
                        queueId,
                        entryId,
                        principal.userId(),
                        idempotencyKey
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/skip")
    public ResponseEntity<QueueStaffEntryResponse> skipEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "Idempotency-Key",
                    required = false
            ) String idempotencyKey
    ) {

        QueueStaffEntryResponse response =
                queueService.skipEntry(
                        queueId,
                        entryId,
                        principal.userId(),
                        idempotencyKey
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/pause")
    public ResponseEntity<QueueResponse> pauseQueue(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueResponse response =
                queueService.pauseQueue(
                        queueId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/resume")
    public ResponseEntity<QueueResponse> resumeQueue(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueResponse response =
                queueService.resumeQueue(
                        queueId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/close")
    public ResponseEntity<QueueResponse> closeQueue(
            @PathVariable Long queueId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueResponse response =
                queueService.closeQueue(
                        queueId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }
}
