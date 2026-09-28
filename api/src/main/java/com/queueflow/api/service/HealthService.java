package com.queueflow.api.service;
import org.springframework.stereotype.Service;

@Service
public class HealthService {
    public String gethealthStatus() {
        return "QueueFlow API is running";
    }
    
}
