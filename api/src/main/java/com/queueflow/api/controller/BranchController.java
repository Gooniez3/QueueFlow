package com.queueflow.api.controller;

import com.queueflow.api.request.CreateBranchRequest;
import com.queueflow.api.request.UpdateBranchRequest;
import com.queueflow.api.response.BranchResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BranchService;
import com.queueflow.api.service.BusinessAuthorizationService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/v1/businesses/{businessId}/branches")
public class BranchController {

    private final BranchService branchService;
    private final BusinessAuthorizationService
            businessAuthorizationService;

    public BranchController(
            BranchService branchService,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.branchService = branchService;
        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @PostMapping
    public ResponseEntity<BranchResponse> createBranch(
            @PathVariable Long businessId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody CreateBranchRequest request
    ) {

        businessAuthorizationService.requireMembership(
                principal.userId(),
                businessId
        );

        BranchResponse response =
                branchService.createBranch(
                        businessId,
                        request
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping
    public ResponseEntity<List<BranchResponse>>
    getBranchesByBusiness(
            @PathVariable Long businessId
    ) {

        List<BranchResponse> branches =
                branchService.getBranchesByBusiness(
                        businessId
                );

        return ResponseEntity.ok(branches);
    }

    @GetMapping("/{branchId}")
    public ResponseEntity<BranchResponse>
    getBranchById(
            @PathVariable Long businessId,
            @PathVariable Long branchId
    ) {

        BranchResponse response =
                branchService.getBranchById(
                        businessId,
                        branchId
                );

        return ResponseEntity.ok(response);
    }

    @PutMapping("/{branchId}")
    public ResponseEntity<BranchResponse> updateBranch(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody UpdateBranchRequest request
    ) {
        BranchResponse response =
                branchService.updateBranch(
                        principal.userId(),
                        businessId,
                        branchId,
                        request
                );

        return ResponseEntity.ok(response);
    }
}
