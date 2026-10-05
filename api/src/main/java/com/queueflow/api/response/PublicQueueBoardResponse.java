package com.queueflow.api.response;

import com.queueflow.api.entity.QueueStatus;

import java.util.List;

public record PublicQueueBoardResponse(
        Long queueId,
        String name,
        QueueStatus status,
        String nowServing,
        String calling,
        long waitingCount,
        List<String> upcomingTicketNumbers
) {
}