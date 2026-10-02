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

    public StaffMembership requireBranchAccess(
            Long userId,
            Long businessId,
            Long branchId
    ) {
        StaffMembership membership =
                requireMembership(
                        userId,
                        businessId
                );

        if (membership.getBranch() == null) {
            return membership;
        }

        if (membership.getBranch()
                .getId()
                .equals(branchId)) {

            return membership;
        }

        throw new AccessDeniedException(
                "You do not have access to this branch"
        );
    }
}