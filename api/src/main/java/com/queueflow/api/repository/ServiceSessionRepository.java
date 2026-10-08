package com.queueflow.api.repository;

import com.queueflow.api.entity.ServiceSession;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDate;
import java.util.List;
import java.util.Optional;

public interface ServiceSessionRepository
        extends JpaRepository<ServiceSession, Long> {

    List<ServiceSession> findByServiceIdAndLocalDateAndBookingOpenTrueOrderByStartTimeAsc(
            Long serviceId,
            LocalDate localDate
    );

    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("""
            select session
            from ServiceSession session
            join fetch session.service service
            join fetch service.branch branch
            where session.id = :sessionId
            """)
    Optional<ServiceSession> findByIdForUpdate(
            @Param("sessionId") Long sessionId
    );
}