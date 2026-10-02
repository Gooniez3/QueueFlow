package com.queueflow.api.controller;

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
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueStaffEntryResponse response =
                queueService.callNext(
                        queueId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/start")
    public ResponseEntity<QueueStaffEntryResponse> startServing(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueStaffEntryResponse response =
                queueService.startServing(
                        queueId,
                        entryId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/complete")
    public ResponseEntity<QueueStaffEntryResponse> completeEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueStaffEntryResponse response =
                queueService.completeEntry(
                        queueId,
                        entryId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }

    @PostMapping("/entries/{entryId}/skip")
    public ResponseEntity<QueueStaffEntryResponse> skipEntry(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        QueueStaffEntryResponse response =
                queueService.skipEntry(
                        queueId,
                        entryId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }
}