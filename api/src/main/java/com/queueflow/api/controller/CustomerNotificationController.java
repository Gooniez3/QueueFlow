package com.queueflow.api.controller;

import com.queueflow.api.response.CustomerNotificationResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.CustomerNotificationService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/entries/{entryId}/notifications")
public class CustomerNotificationController {

    private final CustomerNotificationService notificationService;

    public CustomerNotificationController(
            CustomerNotificationService notificationService
    ) {
        this.notificationService = notificationService;
    }

    @GetMapping
    public ResponseEntity<List<CustomerNotificationResponse>> getNotifications(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "X-Guest-Token",
                    required = false
            ) String guestToken
    ) {
        Long userId = principal == null ? null : principal.userId();

        return ResponseEntity.ok(
                notificationService.getNotifications(
                        queueId, entryId, userId, guestToken
                )
        );
    }

    @PostMapping("/{notificationId}/read")
    public ResponseEntity<CustomerNotificationResponse> markAsRead(
            @PathVariable Long queueId,
            @PathVariable Long entryId,
            @PathVariable Long notificationId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @RequestHeader(
                    value = "X-Guest-Token",
                    required = false
            ) String guestToken
    ) {
        Long userId = principal == null ? null : principal.userId();

        return ResponseEntity.ok(
                notificationService.markNotificationAsRead(
                        queueId,
                        entryId,
                        notificationId,
                        userId,
                        guestToken
                )
        );
    }
}