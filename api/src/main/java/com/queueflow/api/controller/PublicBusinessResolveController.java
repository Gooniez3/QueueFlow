package com.queueflow.api.controller;

import com.queueflow.api.response.PublicBusinessResolveResponse;
import com.queueflow.api.service.PublicBusinessResolveService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/v1/public/businesses")
public class PublicBusinessResolveController {

    private final PublicBusinessResolveService resolveService;

    public PublicBusinessResolveController(
            PublicBusinessResolveService resolveService
    ) {
        this.resolveService = resolveService;
    }

    @GetMapping("/resolve/{publicCode}")
    public ResponseEntity<PublicBusinessResolveResponse> resolve(
            @PathVariable String publicCode
    ) {
        return ResponseEntity.ok(resolveService.resolve(publicCode));
    }
}