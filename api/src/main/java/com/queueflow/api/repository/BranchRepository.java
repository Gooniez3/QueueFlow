package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

import java.util.List;

public interface BranchRepository
        extends JpaRepository<Branch, Long> {

    List<Branch> findByBusinessId(
            Long businessId
    );

    List<Branch> findAllByOrderByNameAsc();

    Optional<Branch> findByPublicCode(String publicCode);
}