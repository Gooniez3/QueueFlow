package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.request.CreateServiceRequest;
import com.queueflow.api.response.ServiceResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
public class ServiceService {

    private final ServiceRepository serviceRepository;
    private final BranchRepository branchRepository;

    public ServiceService(
            ServiceRepository serviceRepository,
            BranchRepository branchRepository
    ) {
        this.serviceRepository = serviceRepository;
        this.branchRepository = branchRepository;
    }

    @Transactional
    public ServiceResponse createService(
            Long businessId,
            Long branchId,
            CreateServiceRequest request
    ) {

        Branch branch = requireBranch(
                businessId,
                branchId
        );

        com.queueflow.api.entity.Service service =
                new com.queueflow.api.entity.Service(
                        branch,
                        request.name().trim(),
                        request.description(),
                        request.durationMinutes()
                );

        com.queueflow.api.entity.Service savedService =
                serviceRepository.save(service);

        return toResponse(savedService);
    }

    @Transactional(readOnly = true)
    public List<ServiceResponse> getServicesByBranch(
            Long businessId,
            Long branchId
    ) {

        requireBranch(
                businessId,
                branchId
        );

        return serviceRepository
                .findByBranchId(branchId)
                .stream()
                .map(this::toResponse)
                .toList();
    }

    @Transactional(readOnly = true)
    public ServiceResponse getServiceById(
            Long businessId,
            Long branchId,
            Long serviceId
    ) {

        requireBranch(
                businessId,
                branchId
        );

        com.queueflow.api.entity.Service service =
                serviceRepository
                        .findById(serviceId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Service not found with id: "
                                                + serviceId
                                )
                        );

        if (!service.getBranch()
                .getId()
                .equals(branchId)) {

            throw new ResourceNotFoundException(
                    "Service not found with id: "
                            + serviceId
            );
        }

        return toResponse(service);
    }

    private Branch requireBranch(
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

        return branch;
    }

    private ServiceResponse toResponse(
            com.queueflow.api.entity.Service service
    ) {

        return new ServiceResponse(
                service.getId(),
                service.getBranch().getId(),
                service.getName(),
                service.getDescription(),
                service.getDurationMinutes(),
                service.isActive(),
                service.getCreatedAt()
        );
    }
}