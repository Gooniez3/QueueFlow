package com.queueflow.api.repository;

import com.queueflow.api.entity.CustomerNotification;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface CustomerNotificationRepository
        extends JpaRepository<CustomerNotification, Long> {

    List<CustomerNotification>
    findByQueueEntryIdOrderByCreatedAtDescIdDesc(Long queueEntryId);
}