package com.queueflow.api.response;

public record PreQueueCheckInResponse(
        PreQueueReservationResponse reservation,
        QueueEntryResponse queueEntry
) {
}