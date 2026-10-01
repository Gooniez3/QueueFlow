package com.queueflow.api.repository;

import com.queueflow.api.entity.StaffMembership;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface StaffMembershipRepository
        extends JpaRepository<StaffMembership, Long> {

    List<StaffMembership> findByUserIdAndActiveTrue(
            Long userId
    );

    Optional<StaffMembership>
    findByUserIdAndBusinessIdAndActiveTrue(
            Long userId,
            Long businessId
    );
}