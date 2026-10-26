package com.queueflow.api.controller;

import com.queueflow.api.response.QueueEntryCodeResolveResponse;
import com.queueflow.api.service.QueueEntryCodeResolveService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/v1/public/services")
public class QueueEntryCodeResolveController {

    private final QueueEntryCodeResolveService resolveService;

    public QueueEntryCodeResolveController(
            QueueEntryCodeResolveService resolveService
    ) {
        this.resolveService = resolveService;
    }

    @GetMapping("/resolve-entry-code/{entryCode}")
    public ResponseEntity<QueueEntryCodeResolveResponse> resolve(
            @PathVariable String entryCode
    ) {
        return ResponseEntity.ok(resolveService.resolve(entryCode));
    }
}
