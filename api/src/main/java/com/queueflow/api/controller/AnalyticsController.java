package com.queueflow.api.controller;

import com.queueflow.api.response.BranchAnalyticsResponse;
import com.queueflow.api.response.DailyTrendResponse;
import com.queueflow.api.response.ServiceAnalyticsResponse;
import com.queueflow.api.response.PeakHourResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.AnalyticsService;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.security.authentication.AuthenticationCredentialsNotFoundException;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.util.List;

@RestController
@RequestMapping(
        "/api/v1/businesses/{businessId}/branches/{branchId}/analytics"
)
public class AnalyticsController {

    private final AnalyticsService analyticsService;

    public AnalyticsController(AnalyticsService analyticsService) {
        this.analyticsService = analyticsService;
    }

    @GetMapping
    public ResponseEntity<BranchAnalyticsResponse> getBranchAnalytics(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate from,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate to,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        if (principal == null) {
            throw new AuthenticationCredentialsNotFoundException(
                    "Authentication is required"
            );
        }

        return ResponseEntity.ok(
                analyticsService.getBranchAnalytics(
                        businessId,
                        branchId,
                        principal.userId(),
                        from,
                        to
                )
        );
    }

    @GetMapping("/daily")
    public ResponseEntity<List<DailyTrendResponse>> getDailyTrends(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate from,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate to,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        if (principal == null) {
            throw new AuthenticationCredentialsNotFoundException(
                    "Authentication is required"
            );
        }

        return ResponseEntity.ok(
                analyticsService.getDailyTrends(
                        businessId,
                        branchId,
                        principal.userId(),
                        from,
                        to
                )
        );
    }

    @GetMapping("/services")
    public ResponseEntity<List<ServiceAnalyticsResponse>> getServiceAnalytics(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate from,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate to,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        if (principal == null) {
            throw new AuthenticationCredentialsNotFoundException(
                    "Authentication is required"
            );
        }

        return ResponseEntity.ok(
                analyticsService.getServiceAnalytics(
                        businessId, branchId, principal.userId(), from, to
                )
        );
    }

    @GetMapping("/peak-hours")
    public ResponseEntity<List<PeakHourResponse>> getPeakHours(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate from,
            @RequestParam
            @DateTimeFormat(iso = DateTimeFormat.ISO.DATE)
            LocalDate to,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {
        if (principal == null) {
            throw new AuthenticationCredentialsNotFoundException(
                    "Authentication is required"
            );
        }

        return ResponseEntity.ok(
                analyticsService.getPeakHours(
                        businessId, branchId, principal.userId(), from, to
                )
        );
    }
}
