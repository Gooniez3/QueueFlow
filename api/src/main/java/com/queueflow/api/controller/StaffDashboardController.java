package com.queueflow.api.controller;

import com.queueflow.api.response.StaffDashboardResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.QueueService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping(
        "/api/v1/businesses/{businessId}/branches/{branchId}/staff"
)
public class StaffDashboardController {

    private final QueueService queueService;

    public StaffDashboardController(
            QueueService queueService
    ) {
        this.queueService = queueService;
    }

    @GetMapping("/dashboard")
    public ResponseEntity<StaffDashboardResponse> getDashboard(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @AuthenticationPrincipal AuthUserPrincipal principal
    ) {

        StaffDashboardResponse response =
                queueService.getStaffDashboard(
                        businessId,
                        branchId,
                        principal.userId()
                );

        return ResponseEntity.ok(response);
    }
}
