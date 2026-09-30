package com.queueflow.api.repository;

import com.queueflow.api.entity.StaffMembership;
import org.springframework.data.jpa.repository.JpaRepository;

public interface StaffMembershipRepository
        extends JpaRepository<StaffMembership, Long> {
}