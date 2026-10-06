package com.queueflow.api.response;

import com.queueflow.api.entity.QueueStatus;

import java.util.List;

public record StaffDashboardQueueResponse(
        Long queueId,
        String publicCode,
        String name,
        QueueStatus status,
        String ticketPrefix,
        StaffDashboardServiceResponse service,
        StaffDashboardCountsResponse counts,
        QueueStaffEntryResponse serving,
        QueueStaffEntryResponse called,
        List<QueueStaffEntryResponse> waiting
) {
}
