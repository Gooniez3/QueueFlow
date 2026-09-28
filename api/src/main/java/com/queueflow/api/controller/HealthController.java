package com.queueflow.api.controller;

import com.queueflow.api.response.HealthResponse;
import com.queueflow.api.service.HealthService;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class HealthController {

    private final HealthService healthService;

    public HealthController(HealthService healthService) {
        this.healthService = healthService;
    }

    @GetMapping("/api/v1/health")
    public HealthResponse health() {
        return healthService.getHealthStatus();
    }
}