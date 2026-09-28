package com.queueflow.api.service;

import com.queueflow.api.response.HealthResponse;
import org.springframework.stereotype.Service;
import org.springframework.beans.factory.annotation.Value;

@Service
public class HealthService {

    private final String apiVersion;

    public HealthService(@Value("${queueflow.api.version}") String apiVersion) {
        this.apiVersion = apiVersion;
    }

    public HealthResponse getHealthStatus() {
        return new HealthResponse(
                "UP",
                "QueueFlow API",
                apiVersion
        );
    }
}