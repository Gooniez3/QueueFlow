package com.queueflow.api.response;

import com.queueflow.api.entity.BusinessCategory;

import java.math.BigDecimal;
import java.util.List;

public record PublicDiscoveryResponse(
        Long businessId,
        String businessName,
        String businessDescription,
        BusinessCategory category,
        Long branchId,
        String branchName,
        String address,
        BigDecimal latitude,
        BigDecimal longitude,
        Double distanceKm,
        List<PublicDiscoveryServiceResponse> services
) {
}
