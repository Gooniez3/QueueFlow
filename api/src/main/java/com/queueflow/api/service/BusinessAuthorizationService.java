package com.queueflow.api.service;

import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.repository.StaffMembershipRepository;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.stereotype.Service;

@Service
public class BusinessAuthorizationService {

    private final StaffMembershipRepository staffMembershipRepository;

    public BusinessAuthorizationService(
            StaffMembershipRepository staffMembershipRepository
    ) {
        this.staffMembershipRepository =
                staffMembershipRepository;
    }

    public StaffMembership requireMembership(
            Long userId,
            Long businessId
    ) {
        return staffMembershipRepository
                .findByUserIdAndBusinessIdAndActiveTrue(
                        userId,
                        businessId
                )
                .orElseThrow(() ->
                        new AccessDeniedException(
                                "You do not have access to this business"
                        )
                );
    }
}