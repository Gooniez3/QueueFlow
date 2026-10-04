package com.queueflow.api.controller;

import com.queueflow.api.response.PublicQueueResolveResponse;
import com.queueflow.api.service.PublicQueueResolveService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/v1/public/queues")
public class PublicQueueResolveController {

    private final PublicQueueResolveService publicQueueResolveService;

    public PublicQueueResolveController(
            PublicQueueResolveService publicQueueResolveService
    ) {
        this.publicQueueResolveService =
                publicQueueResolveService;
    }

    @GetMapping("/resolve/{publicCode}")
    public ResponseEntity<PublicQueueResolveResponse> resolve(
            @PathVariable String publicCode
    ) {
        return ResponseEntity.ok(
                publicQueueResolveService.resolve(publicCode)
        );
    }
}