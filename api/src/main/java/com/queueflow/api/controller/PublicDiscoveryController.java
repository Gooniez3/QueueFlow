package com.queueflow.api.controller;

import com.queueflow.api.response.PublicDiscoveryResponse;
import com.queueflow.api.service.PublicDiscoveryService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/v1/public/discovery")
public class PublicDiscoveryController {

    private final PublicDiscoveryService publicDiscoveryService;

    public PublicDiscoveryController(
            PublicDiscoveryService publicDiscoveryService
    ) {
        this.publicDiscoveryService =
                publicDiscoveryService;
    }

    @GetMapping
    public ResponseEntity<List<PublicDiscoveryResponse>> discover(
            @RequestParam(required = false) String search,
            @RequestParam(required = false) String category,
            @RequestParam(required = false) Double latitude,
            @RequestParam(required = false) Double longitude
    ) {

        return ResponseEntity.ok(
                publicDiscoveryService.discover(
                        search,
                        category,
                        latitude,
                        longitude
                )
        );
    }
}