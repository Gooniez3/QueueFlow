package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.response.PublicQueueResolveResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class PublicQueueResolveService {

    private final QueueRepository queueRepository;

    public PublicQueueResolveService(
            QueueRepository queueRepository
    ) {
        this.queueRepository = queueRepository;
    }

    @Transactional(readOnly = true)
    public PublicQueueResolveResponse resolve(
            String publicCode
    ) {

        Queue queue =
                queueRepository.findByPublicCode(publicCode)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Queue not found for public code"
                                )
                        );

        Branch branch = queue.getBranch();
        Business business = branch.getBusiness();

        Long serviceId = null;
        String serviceName = null;

        if (queue.getService() != null) {
            serviceId = queue.getService().getId();
            serviceName = queue.getService().getName();
        }

        return new PublicQueueResolveResponse(
                queue.getPublicCode(),
                business.getId(),
                business.getName(),
                branch.getId(),
                branch.getName(),
                serviceId,
                serviceName,
                queue.getId(),
                queue.getName(),
                queue.getStatus()
        );
    }
}