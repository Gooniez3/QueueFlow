package com.queueflow.api.controller;

import com.queueflow.api.response.BusinessBranchAnalyticsResponse;
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
@RequestMapping("/api/v1/businesses/{businessId}/analytics")
public class BusinessAnalyticsController {

    private final AnalyticsService analyticsService;

    public BusinessAnalyticsController(AnalyticsService analyticsService) {
        this.analyticsService = analyticsService;
    }

    @GetMapping("/branches")
    public ResponseEntity<List<BusinessBranchAnalyticsResponse>> getBranches(
            @PathVariable Long businessId,
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
                analyticsService.getBusinessBranchAnalytics(
                        businessId, principal.userId(), from, to
                )
        );
    }
}