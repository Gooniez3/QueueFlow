package com.queueflow.api.request;

public record JoinQueueRequest(
        Long serviceId,
        Integer partySize
) {
    public JoinQueueRequest(Long serviceId) {
        this(serviceId, null);
    }
}