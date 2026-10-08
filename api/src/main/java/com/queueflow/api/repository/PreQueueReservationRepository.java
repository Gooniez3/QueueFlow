package com.queueflow.api.repository;

import com.queueflow.api.entity.PreQueueReservation;
import com.queueflow.api.entity.PreQueueReservationStatus;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.Optional;

public interface PreQueueReservationRepository
        extends JpaRepository<PreQueueReservation, Long> {

    Optional<PreQueueReservation> findByReservationCode(
            String reservationCode
    );

    long countByServiceSessionIdAndStatus(
            Long serviceSessionId,
            PreQueueReservationStatus status
    );

    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("""
            select reservation
            from PreQueueReservation reservation
            join fetch reservation.serviceSession session
            join fetch session.service service
            join fetch service.branch branch
            left join fetch reservation.user user
            where reservation.id = :reservationId
            """)
    Optional<PreQueueReservation> findByIdForUpdate(
            @Param("reservationId") Long reservationId
    );
}