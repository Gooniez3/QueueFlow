package com.queueflow.api.service;

import com.queueflow.api.response.HealthResponse;
import org.springframework.stereotype.Service;

@Service
public class HealthService {

    public HealthResponse getHealthStatus() {
        return new HealthResponse("UP", "QueueFlow API");
    }
}