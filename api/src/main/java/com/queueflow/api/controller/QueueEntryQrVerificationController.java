package com.queueflow.api.controller;

import com.queueflow.api.request.VerifyQueueEntryQrRequest;
import com.queueflow.api.response.QueueEntryQrVerificationResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.QueueEntryQrService;
import jakarta.validation.Valid;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/staff/queue-entry-qr")
public class QueueEntryQrVerificationController {

    private final QueueEntryQrService queueEntryQrService;

    public QueueEntryQrVerificationController(
            QueueEntryQrService queueEntryQrService
    ) {
        this.queueEntryQrService =
                queueEntryQrService;
    }

    @PostMapping("/verify")
    public ResponseEntity<QueueEntryQrVerificationResponse> verify(
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody VerifyQueueEntryQrRequest request
    ) {

        QueueEntryQrVerificationResponse response =
                queueEntryQrService.verifyCredential(
                        request.credential(),
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }
}
