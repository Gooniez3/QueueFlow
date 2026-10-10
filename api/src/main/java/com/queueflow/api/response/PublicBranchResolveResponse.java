package com.queueflow.api.response;

public record PublicBranchResolveResponse(
        String publicCode,
        Long businessId,
        String businessName,
        Long branchId,
        String branchName
) {
}