package com.queueflow.api.response;

import com.queueflow.api.entity.PreQueueReservationStatus;

import java.time.LocalDate;
import java.time.LocalTime;
import java.time.OffsetDateTime;

public record PreQueueReservationResponse(
        Long id,
        String reservationCode,
        Long serviceSessionId,
        Long serviceId,
        Long branchId,
        LocalDate localDate,
        LocalTime startTime,
        LocalTime endTime,
        PreQueueReservationStatus status,
        OffsetDateTime createdAt,
        OffsetDateTime cancelledAt,
        OffsetDateTime checkedInAt,
        Long queueEntryId,
        String guestToken
) {
}