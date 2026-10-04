package com.queueflow.api.service;

import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
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

    public StaffMembership requireBusinessOwner(
            Long userId,
            Long businessId
    ) {
        StaffMembership membership =
                requireMembership(userId, businessId);

        if (membership.getRole() == StaffRole.OWNER
                && membership.getBranch() == null) {
            return membership;
        }

        throw new AccessDeniedException(
                "Owner access is required to manage this business"
        );
    }

    public StaffMembership requireBranchManagementAccess(
            Long userId,
            Long businessId,
            Long branchId
    ) {
        StaffMembership membership =
                requireBranchAccess(userId, businessId, branchId);

        if (membership.getRole() == StaffRole.MANAGER) {
            return membership;
        }

        if (membership.getRole() == StaffRole.OWNER
                && membership.getBranch() == null) {
            return membership;
        }

        throw new AccessDeniedException(
                "Manager or owner access is required to manage this branch"
        );
    }
}
