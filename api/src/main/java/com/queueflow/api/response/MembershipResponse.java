package com.queueflow.api.response;

import com.queueflow.api.entity.StaffRole;

public record MembershipResponse(
        Long businessId,
        Long branchId,
        StaffRole role
) {
}