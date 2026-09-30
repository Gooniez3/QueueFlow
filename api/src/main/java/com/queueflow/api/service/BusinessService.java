package com.queueflow.api.service;

import com.queueflow.api.entity.Business;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.request.CreateBusinessRequest;
import com.queueflow.api.response.BusinessResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class BusinessService {

    private final BusinessRepository businessRepository;

    public BusinessService(BusinessRepository businessRepository) {
        this.businessRepository = businessRepository;
    }

    @Transactional
    public BusinessResponse createBusiness(CreateBusinessRequest request) {

        Business business = new Business(
                request.name(),
                request.description()
        );

        Business savedBusiness = businessRepository.save(business);

        return toResponse(savedBusiness);
    }

    @Transactional(readOnly = true)
    public BusinessResponse getBusinessById(Long id) {

        Business business = businessRepository.findById(id)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Business not found with id: " + id
                        )
                );

        return toResponse(business);
    }

    private BusinessResponse toResponse(Business business) {
        return new BusinessResponse(
                business.getId(),
                business.getName(),
                business.getDescription(),
                business.getCreatedAt()
        );
    }
}