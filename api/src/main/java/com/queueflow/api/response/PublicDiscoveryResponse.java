package com.queueflow.api.response;

import java.math.BigDecimal;
import java.util.List;

public record PublicDiscoveryResponse(
        Long businessId,
        String businessName,
        String businessDescription,
        String category,
        Long branchId,
        String branchName,
        String address,
        BigDecimal latitude,
        BigDecimal longitude,
        Double distanceKm,
        List<PublicDiscoveryServiceResponse> services
) {
}