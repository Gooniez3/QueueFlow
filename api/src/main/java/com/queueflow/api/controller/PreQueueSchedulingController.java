package com.queueflow.api.controller;

import com.queueflow.api.request.CreatePreQueueReservationRequest;
import com.queueflow.api.response.PreQueueReservationResponse;
import com.queueflow.api.response.ServiceSessionResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.PreQueueSchedulingService;
import com.queueflow.api.response.PreQueueCheckInResponse;
import com.queueflow.api.request.ReschedulePreQueueReservationRequest;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.util.List;

@RestController
public class PreQueueSchedulingController {

    private final PreQueueSchedulingService preQueueSchedulingService;

    public PreQueueSchedulingController(
            PreQueueSchedulingService preQueueSchedulingService
    ) {
        this.preQueueSchedulingService =
                preQueueSchedulingService;
    }

    @GetMapping(
            "/api/v1/branches/{branchId}/services/{serviceId}/slots"
    )
    public ResponseEntity<List<ServiceSessionResponse>> getAvailableSlots(
            @PathVariable Long branchId,
            @PathVariable Long serviceId,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate date
    ) {

        return ResponseEntity.ok(
                preQueueSchedulingService.getAvailableSlots(
                        branchId,
                        serviceId,
                        date
                )
        );
    }

    @PostMapping("/api/v1/pre-queue/reservations")
    public ResponseEntity<PreQueueReservationResponse> createReservation(
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestBody CreatePreQueueReservationRequest request
    ) {

        Long userId =
                principal == null
                        ? null
                        : principal.userId();

        PreQueueReservationResponse response =
                preQueueSchedulingService.createReservation(
                        request.serviceSessionId(),
                        userId
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping("/api/v1/pre-queue/reservations/{reservationId}")
    public ResponseEntity<PreQueueReservationResponse> getReservation(
        @PathVariable Long reservationId,
        @AuthenticationPrincipal AuthUserPrincipal principal,
        @RequestHeader(
                value = "X-Guest-Token",
                required = false
        ) String guestToken
  ) {

    Long userId =
            principal == null
                    ? null
                    : principal.userId();

    PreQueueReservationResponse response =
            preQueueSchedulingService.getReservation(
                    reservationId,
                    userId,
                    guestToken
            );

    return ResponseEntity.ok(response);
 }

    @PostMapping("/api/v1/pre-queue/reservations/{reservationId}/cancel")
    public ResponseEntity<PreQueueReservationResponse> cancelReservation(
        @PathVariable Long reservationId,
        @AuthenticationPrincipal AuthUserPrincipal principal,
        @RequestHeader(
                value = "X-Guest-Token",
                required = false
        ) String guestToken
  ) {

    Long userId =
            principal == null
                    ? null
                    : principal.userId();

    PreQueueReservationResponse response =
            preQueueSchedulingService.cancelReservation(
                    reservationId,
                    userId,
                    guestToken
            );

    return ResponseEntity.ok(response);
  }

    @PatchMapping("/api/v1/pre-queue/reservations/{reservationId}/reschedule")
    public ResponseEntity<PreQueueReservationResponse> rescheduleReservation(
        @PathVariable Long reservationId,
        @AuthenticationPrincipal AuthUserPrincipal principal,
        @RequestHeader(
                value = "X-Guest-Token",
                required = false
        ) String guestToken,
        @RequestBody ReschedulePreQueueReservationRequest request
  ) {

    Long userId =
            principal == null
                    ? null
                    : principal.userId();

    PreQueueReservationResponse response =
            preQueueSchedulingService.rescheduleReservation(
                    reservationId,
                    request.serviceSessionId(),
                    userId,
                    guestToken
            );

    return ResponseEntity.ok(response);
  }

    @PostMapping("/api/v1/pre-queue/reservations/{reservationId}/check-in")
    public ResponseEntity<PreQueueCheckInResponse> checkInReservation(
        @PathVariable Long reservationId,
        @AuthenticationPrincipal AuthUserPrincipal principal,
        @RequestHeader(
                value = "X-Guest-Token",
                required = false
        ) String guestToken
 ) {

    Long userId =
            principal == null
                    ? null
                    : principal.userId();

    PreQueueCheckInResponse response =
            preQueueSchedulingService.checkInReservation(
                    reservationId,
                    userId,
                    guestToken
            );

    return ResponseEntity.ok(response);
   }
}