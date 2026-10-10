package com.queueflow.api.service;

import java.util.List;

import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.request.CreateBusinessRequest;
import com.queueflow.api.request.UpdateBusinessRequest;
import com.queueflow.api.response.BusinessResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class BusinessService {

    private final BusinessRepository businessRepository;
    private final UserAccountRepository userAccountRepository;
    private final StaffMembershipRepository staffMembershipRepository;
    private final BusinessAuthorizationService businessAuthorizationService;

    public BusinessService(
            BusinessRepository businessRepository,
            UserAccountRepository userAccountRepository,
            StaffMembershipRepository staffMembershipRepository,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.businessRepository = businessRepository;
        this.userAccountRepository = userAccountRepository;
        this.staffMembershipRepository = staffMembershipRepository;
        this.businessAuthorizationService = businessAuthorizationService;
    }

    @Transactional
    public BusinessResponse createBusiness(
            Long userId,
            CreateBusinessRequest request
    ) {

        UserAccount user = userAccountRepository
                .findById(userId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "User not found with id: " + userId
                        )
                );

        Business business = new Business(
                request.name(),
                request.description()
        );

        business.setCategory(
                normalizeCategory(request.category())
        );

        Business savedBusiness =
                businessRepository.save(business);

        StaffMembership ownerMembership =
                new StaffMembership(
                        user,
                        savedBusiness,
                        null,
                        StaffRole.OWNER
                );

        staffMembershipRepository.save(ownerMembership);

        return toResponse(savedBusiness);
    }

    @Transactional(readOnly = true)
    public BusinessResponse getBusinessById(Long id) {

        Business business = businessRepository
                .findById(id)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Business not found with id: " + id
                        )
                );

        return toResponse(business);
    }

    @Transactional(readOnly = true)
    public List<BusinessResponse> getAllBusinesses() {

        return businessRepository
                .findAll()
                .stream()
                .map(this::toResponse)
                .toList();
    }

    @Transactional
    public BusinessResponse updateBusiness(
            Long userId,
            Long businessId,
            UpdateBusinessRequest request
    ) {
        Business business = businessRepository
                .findById(businessId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Business not found with id: " + businessId
                        )
                );

        businessAuthorizationService.requireBusinessOwner(
                userId,
                businessId
        );

        business.setName(request.name());
        business.setDescription(
                normalizeNullableDescription(request.description())
        );

        business.setCategory(
                normalizeCategory(request.category())
        );

        return toResponse(businessRepository.save(business));
    }

    private com.queueflow.api.entity.BusinessCategory normalizeCategory(
            com.queueflow.api.entity.BusinessCategory category
    ) {
        return category == null
                ? com.queueflow.api.entity.BusinessCategory.OTHER
                : category;
    }
    private String normalizeNullableDescription(String description) {
        return description == null || description.isBlank()
                ? null
                : description;
    }


    private BusinessResponse toResponse(Business business) {

        return new BusinessResponse(
                business.getId(),
                business.getPublicCode(),
                business.getName(),
                business.getDescription(),
                business.getCategory(),
                business.getCreatedAt()
        );
    }
}




