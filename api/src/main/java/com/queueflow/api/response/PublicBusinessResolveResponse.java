package com.queueflow.api.response;

public record PublicBusinessResolveResponse(
        String publicCode,
        Long businessId,
        String businessName
) {
}