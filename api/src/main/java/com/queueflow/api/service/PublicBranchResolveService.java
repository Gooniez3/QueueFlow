package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.response.PublicBranchResolveResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class PublicBranchResolveService {

    private final BranchRepository branchRepository;

    public PublicBranchResolveService(
            BranchRepository branchRepository
    ) {
        this.branchRepository = branchRepository;
    }

    @Transactional(readOnly = true)
    public PublicBranchResolveResponse resolve(String publicCode) {
        Branch branch = branchRepository.findByPublicCode(publicCode)
                .orElseThrow(() -> new ResourceNotFoundException(
                        "Branch not found for public code"
                ));

        Business business = branch.getBusiness();

        return new PublicBranchResolveResponse(
                branch.getPublicCode(),
                business.getId(),
                business.getName(),
                branch.getId(),
                branch.getName()
        );
    }
}