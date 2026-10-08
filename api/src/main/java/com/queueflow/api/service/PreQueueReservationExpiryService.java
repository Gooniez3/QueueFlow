package com.queueflow.api.service;

import com.queueflow.api.entity.PreQueueReservation;
import com.queueflow.api.entity.PreQueueReservationStatus;
import com.queueflow.api.entity.ServiceSession;
import com.queueflow.api.repository.PreQueueReservationRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.LocalTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;

@Service
public class PreQueueReservationExpiryService {

    private final PreQueueReservationRepository
            preQueueReservationRepository;

    public PreQueueReservationExpiryService(
            PreQueueReservationRepository
                    preQueueReservationRepository
    ) {
        this.preQueueReservationRepository =
                preQueueReservationRepository;
    }

    @Transactional(
            propagation = Propagation.REQUIRES_NEW
    )
    public void expireIfNeeded(
            Long reservationId
    ) {

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findById(reservationId)
                        .orElse(null);

        if (reservation == null
                || reservation.getStatus()
                != PreQueueReservationStatus.RESERVED) {
            return;
        }

        ServiceSession session =
                reservation.getServiceSession();

        ZoneId branchZone =
                ZoneId.of(
                        session.getService()
                                .getBranch()
                                .getTimezone()
                );

        ZonedDateTime now =
                ZonedDateTime.now(
                        branchZone
                );

        LocalDate currentDate =
                now.toLocalDate();

        LocalTime currentTime =
                now.toLocalTime();

        boolean datePassed =
                currentDate.isAfter(
                        session.getLocalDate()
                );

        boolean windowPassedToday =
                currentDate.equals(
                        session.getLocalDate()
                )
                        && currentTime.isAfter(
                        session.getCheckInEndTime()
                );

        if (datePassed || windowPassedToday) {
            reservation.setStatus(
                    PreQueueReservationStatus.EXPIRED
            );

            preQueueReservationRepository.save(
                    reservation
            );
        }
    }
}