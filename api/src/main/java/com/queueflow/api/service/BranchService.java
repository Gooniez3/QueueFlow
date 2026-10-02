package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.request.CreateBranchRequest;
import com.queueflow.api.response.BranchResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.DateTimeException;
import java.time.ZoneId;
import java.util.List;

@Service
public class BranchService {

    private final BranchRepository branchRepository;
    private final BusinessRepository businessRepository;

    public BranchService(
            BranchRepository branchRepository,
            BusinessRepository businessRepository
    ) {
        this.branchRepository = branchRepository;
        this.businessRepository = businessRepository;
    }

    @Transactional
    public BranchResponse createBranch(
            Long businessId,
            CreateBranchRequest request
    ) {

        Business business = businessRepository
                .findById(businessId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Business not found with id: "
                                        + businessId
                        )
                );

         String timezone =
           request.timezone() == null
                || request.timezone().isBlank()
                ? "Asia/Singapore"
                : request.timezone().trim();

          timezone = validateTimezone(timezone);

        Branch branch = new Branch(
                business,
                request.name().trim(),
                request.address().trim(),
                request.latitude(),
                request.longitude()
        );

        branch.setTimezone(timezone);

        Branch savedBranch =
                branchRepository.save(branch);

        return toResponse(savedBranch);
    }

    @Transactional(readOnly = true)
    public List<BranchResponse> getBranchesByBusiness(
            Long businessId
    ) {

        if (!businessRepository.existsById(businessId)) {
            throw new ResourceNotFoundException(
                    "Business not found with id: "
                            + businessId
            );
        }

        return branchRepository
                .findByBusinessId(businessId)
                .stream()
                .map(this::toResponse)
                .toList();
    }

    @Transactional(readOnly = true)
    public BranchResponse getBranchById(
            Long businessId,
            Long branchId
    ) {

        Branch branch = branchRepository
                .findById(branchId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Branch not found with id: "
                                        + branchId
                        )
                );

        if (!branch.getBusiness()
                .getId()
                .equals(businessId)) {

            throw new ResourceNotFoundException(
                    "Branch not found with id: "
                            + branchId
            );
        }

        return toResponse(branch);
    }

    private String validateTimezone(
            String timezone
    ) {

        try {
            ZoneId.of(timezone);
            return timezone;
        } catch (DateTimeException exception) {
            throw new IllegalArgumentException(
                    "Invalid timezone: " + timezone
            );
        }
    }

    private BranchResponse toResponse(
            Branch branch
    ) {

        return new BranchResponse(
                branch.getId(),
                branch.getBusiness().getId(),
                branch.getName(),
                branch.getAddress(),
                branch.getLatitude(),
                branch.getLongitude(),
                branch.getTimezone(),
                branch.getCreatedAt()
        );
    }
}