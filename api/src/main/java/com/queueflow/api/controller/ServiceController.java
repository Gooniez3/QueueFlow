package com.queueflow.api.controller;

import com.queueflow.api.request.CreateServiceRequest;
import com.queueflow.api.response.ServiceResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BusinessAuthorizationService;
import com.queueflow.api.service.ServiceService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping(
        "/api/v1/businesses/{businessId}/branches/{branchId}/services"
)
public class ServiceController {

    private final ServiceService serviceService;
    private final BusinessAuthorizationService
            businessAuthorizationService;

    public ServiceController(
            ServiceService serviceService,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.serviceService = serviceService;
        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @PostMapping
    public ResponseEntity<ServiceResponse> createService(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody CreateServiceRequest request
    ) {

        businessAuthorizationService.requireMembership(
                principal.userId(),
                businessId
        );

        ServiceResponse response =
                serviceService.createService(
                        businessId,
                        branchId,
                        request
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping
    public ResponseEntity<List<ServiceResponse>>
    getServicesByBranch(
            @PathVariable Long businessId,
            @PathVariable Long branchId
    ) {

        List<ServiceResponse> services =
                serviceService.getServicesByBranch(
                        businessId,
                        branchId
                );

        return ResponseEntity.ok(services);
    }

    @GetMapping("/{serviceId}")
    public ResponseEntity<ServiceResponse>
    getServiceById(
            @PathVariable Long businessId,
            @PathVariable Long branchId,
            @PathVariable Long serviceId
    ) {

        ServiceResponse response =
                serviceService.getServiceById(
                        businessId,
                        branchId,
                        serviceId
                );

        return ResponseEntity.ok(response);
    }
}