package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.request.CreateQueueRequest;
import com.queueflow.api.response.QueueResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.ZoneId;

@Service
public class QueueService {

    private final QueueRepository queueRepository;
    private final BranchRepository branchRepository;
    private final ServiceRepository serviceRepository;

    public QueueService(
            QueueRepository queueRepository,
            BranchRepository branchRepository,
            ServiceRepository serviceRepository
    ) {
        this.queueRepository = queueRepository;
        this.branchRepository = branchRepository;
        this.serviceRepository = serviceRepository;
    }

    @Transactional
    public QueueResponse createQueue(
            Long businessId,
            Long branchId,
            CreateQueueRequest request
    ) {

        Branch branch = requireBranch(
                businessId,
                branchId
        );

        LocalDate businessDate = LocalDate.now(
                ZoneId.of(branch.getTimezone())
        );

        com.queueflow.api.entity.Service service = null;

        if (request.serviceId() != null) {

            service = serviceRepository
                    .findById(request.serviceId())
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Service not found with id: "
                                            + request.serviceId()
                            )
                    );

            if (!service.getBranch()
                    .getId()
                    .equals(branchId)) {

                throw new ResourceNotFoundException(
                        "Service not found with id: "
                                + request.serviceId()
                );
            }

            if (queueRepository
                    .findByBranchIdAndServiceIdAndBusinessDate(
                            branchId,
                            service.getId(),
                            businessDate
                    )
                    .isPresent()) {

                throw new IllegalStateException(
                        "Queue already exists for this service today"
                );
            }

        } else {

            if (queueRepository
                    .findByBranchIdAndServiceIsNullAndBusinessDate(
                            branchId,
                            businessDate
                    )
                    .isPresent()) {

                throw new IllegalStateException(
                        "Shared queue already exists for this branch today"
                );
            }
        }

        Queue queue = new Queue(
                branch,
                service,
                request.name().trim(),
                businessDate,
                request.ticketPrefix()
                        .trim()
                        .toUpperCase()
        );

        Queue savedQueue =
                queueRepository.save(queue);

        return toResponse(savedQueue);
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

    private QueueResponse toResponse(
            Queue queue
    ) {

        Long serviceId =
                queue.getService() == null
                        ? null
                        : queue.getService().getId();

        return new QueueResponse(
                queue.getId(),
                queue.getBranch().getId(),
                serviceId,
                queue.getName(),
                queue.getBusinessDate(),
                queue.getTicketPrefix(),
                queue.getNextTicketSequence(),
                queue.getStatus(),
                queue.getOpenedAt(),
                queue.getClosedAt(),
                queue.getCreatedAt(),
                queue.getUpdatedAt()
        );
    }
}