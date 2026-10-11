package com.queueflow.api.service;

import com.queueflow.api.entity.Business;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.response.PublicBusinessResolveResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class PublicBusinessResolveService {

    private final BusinessRepository businessRepository;

    public PublicBusinessResolveService(
            BusinessRepository businessRepository
    ) {
        this.businessRepository = businessRepository;
    }

    @Transactional(readOnly = true)
    public PublicBusinessResolveResponse resolve(String publicCode) {
        Business business = businessRepository.findByPublicCode(publicCode)
                .orElseThrow(() -> new ResourceNotFoundException(
                        "Business not found for public code"
                ));

        return new PublicBusinessResolveResponse(
                business.getPublicCode(),
                business.getId(),
                business.getName()
        );
    }
}