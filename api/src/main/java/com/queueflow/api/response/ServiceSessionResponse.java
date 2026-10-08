package com.queueflow.api.response;

import java.time.LocalDate;
import java.time.LocalTime;

public record ServiceSessionResponse(
        Long id,
        Long serviceId,
        LocalDate localDate,
        LocalTime startTime,
        LocalTime endTime,
        Integer capacity,
        long reservedCount,
        int remainingCapacity,
        boolean bookingOpen
) {
}