package com.queueflow.api.controller;

import com.queueflow.api.request.CreateBusinessRequest;
import com.queueflow.api.response.BusinessResponse;
import com.queueflow.api.service.BusinessService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
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
            @Valid @RequestBody CreateBusinessRequest request
    ) {
        BusinessResponse response = businessService.createBusiness(request);

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @GetMapping("/{id}")
    public ResponseEntity<BusinessResponse> getBusinessById(
            @PathVariable Long id
    ) {
        BusinessResponse response = businessService.getBusinessById(id);

        return ResponseEntity.ok(response);
    }
}