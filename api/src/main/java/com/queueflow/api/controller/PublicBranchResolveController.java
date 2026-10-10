package com.queueflow.api.controller;

import com.queueflow.api.response.PublicBranchResolveResponse;
import com.queueflow.api.service.PublicBranchResolveService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/v1/public/branches")
public class PublicBranchResolveController {

    private final PublicBranchResolveService resolveService;

    public PublicBranchResolveController(
            PublicBranchResolveService resolveService
    ) {
        this.resolveService = resolveService;
    }

    @GetMapping("/resolve/{publicCode}")
    public ResponseEntity<PublicBranchResolveResponse> resolve(
            @PathVariable String publicCode
    ) {
        return ResponseEntity.ok(resolveService.resolve(publicCode));
    }
}