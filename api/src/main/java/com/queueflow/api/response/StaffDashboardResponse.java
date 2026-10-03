package com.queueflow.api.response;

import java.time.LocalDate;
import java.util.List;

public record StaffDashboardResponse(
        Long businessId,
        Long branchId,
        LocalDate businessDate,
        List<StaffDashboardQueueResponse> queues
) {
}
