package com.queueflow.api.controller;

import java.util.List;

import com.queueflow.api.request.CreateBusinessRequest;
import com.queueflow.api.request.UpdateBusinessRequest;
import com.queueflow.api.response.BusinessResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.BusinessService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/businesses")
public class BusinessController {

    private final BusinessService businessService;

    public BusinessController(BusinessService businessService) {
        this.businessService = businessService;
    }

    @PostMapping
    public ResponseEntity<BusinessResponse> createBusiness(
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody CreateBusinessRequest request
    ) {

        BusinessResponse response =
                businessService.createBusiness(
                        principal.userId(),
                        request
                );

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping("/{id}")
    public ResponseEntity<BusinessResponse> getBusinessById(
            @PathVariable Long id
    ) {

        BusinessResponse response =
                businessService.getBusinessById(id);

        return ResponseEntity.ok(response);
    }

    @GetMapping
    public ResponseEntity<List<BusinessResponse>> getAllBusinesses() {

        List<BusinessResponse> businesses =
                businessService.getAllBusinesses();

        return ResponseEntity.ok(businesses);
    }

    @PutMapping("/{businessId}")
    public ResponseEntity<BusinessResponse> updateBusiness(
            @PathVariable Long businessId,
            @AuthenticationPrincipal AuthUserPrincipal principal,
            @Valid @RequestBody UpdateBusinessRequest request
    ) {
        BusinessResponse response =
                businessService.updateBusiness(
                        principal.userId(),
                        businessId,
                        request
                );

        return ResponseEntity.ok(response);
    }
}
