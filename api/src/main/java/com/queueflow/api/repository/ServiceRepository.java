package com.queueflow.api.repository;

import com.queueflow.api.entity.Service;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Collection;
import java.util.List;
import java.util.Optional;

public interface ServiceRepository
        extends JpaRepository<Service, Long> {

    List<Service> findByBranchId(
            Long branchId
    );

    List<Service> findByBranchIdAndActiveTrue(
            Long branchId
    );

    List<Service> findByBranchIdInAndActiveTrue(
            Collection<Long> branchIds
    );

    Optional<Service> findByQueueEntryCode(
            String queueEntryCode
    );
}
