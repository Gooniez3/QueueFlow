package com.queueflow.api.repository;

import com.queueflow.api.entity.Queue;
import org.springframework.data.jpa.repository.JpaRepository;

public interface QueueRepository extends JpaRepository<Queue, Long> {
}