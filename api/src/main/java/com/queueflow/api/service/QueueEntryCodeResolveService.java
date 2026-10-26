package com.queueflow.api.service;

import com.queueflow.api.entity.Service;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.response.QueueEntryCodeResolveResponse;
import com.queueflow.api.support.QueueEntryCode;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

@Component
public class QueueEntryCodeResolveService {

    private final ServiceRepository serviceRepository;

    public QueueEntryCodeResolveService(
            ServiceRepository serviceRepository
    ) {
        this.serviceRepository = serviceRepository;
    }

    @Transactional(readOnly = true)
    public QueueEntryCodeResolveResponse resolve(String entryCode) {
        String normalizedCode = QueueEntryCode.normalize(entryCode);

        if (!QueueEntryCode.isValid(normalizedCode)) {
            throw new ResourceNotFoundException(
                    "Queue entry code is invalid"
            );
        }

        Service service = serviceRepository
                .findByQueueEntryCode(normalizedCode)
                .orElseThrow(() -> new ResourceNotFoundException(
                        "Queue entry code was not found"
                ));

        return new QueueEntryCodeResolveResponse(
                QueueEntryCode.format(service.getQueueEntryCode()),
                service.getBranch().getBusiness().getId(),
                service.getBranch().getId(),
                service.getId()
        );
    }
}
