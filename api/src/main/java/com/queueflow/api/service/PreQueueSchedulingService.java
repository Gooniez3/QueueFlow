package com.queueflow.api.service;

import com.queueflow.api.entity.PreQueueReservation;
import com.queueflow.api.entity.PreQueueReservationStatus;
import com.queueflow.api.entity.ServiceSession;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.PreQueueReservationRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.ServiceSessionRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.response.PreQueueReservationResponse;
import com.queueflow.api.response.ServiceSessionResponse;
import com.queueflow.api.security.AuthTokenService;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.response.PreQueueCheckInResponse;
import com.queueflow.api.response.QueueEntryResponse;
import com.queueflow.api.response.PublicQueueResponse;
import com.queueflow.api.request.JoinQueueRequest;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.List;
import java.time.LocalTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;


@Service
public class PreQueueSchedulingService {

    private final ServiceRepository serviceRepository;
    private final ServiceSessionRepository serviceSessionRepository;
    private final PreQueueReservationRepository preQueueReservationRepository;
    private final UserAccountRepository userAccountRepository;
    private final AuthTokenService authTokenService;
    private final QueueEntryRepository queueEntryRepository;
    private final QueueService queueService;
    private final PreQueueReservationExpiryService preQueueReservationExpiryService;

    public PreQueueSchedulingService(
        ServiceRepository serviceRepository,
        ServiceSessionRepository serviceSessionRepository,
        PreQueueReservationRepository preQueueReservationRepository,
        UserAccountRepository userAccountRepository,
        QueueEntryRepository queueEntryRepository,
        AuthTokenService authTokenService,
        QueueService queueService,
        PreQueueReservationExpiryService preQueueReservationExpiryService
   ){
    this.serviceRepository = serviceRepository;
    this.serviceSessionRepository = serviceSessionRepository;
    this.preQueueReservationRepository = preQueueReservationRepository;
    this.userAccountRepository = userAccountRepository;
    this.queueEntryRepository = queueEntryRepository;
    this.authTokenService = authTokenService;
    this.queueService = queueService;
    this.preQueueReservationExpiryService = preQueueReservationExpiryService;
   }

    @Transactional(readOnly = true)
    public List<ServiceSessionResponse> getAvailableSlots(
            Long branchId,
            Long serviceId,
            LocalDate date
    ) {

        com.queueflow.api.entity.Service service =
                serviceRepository.findById(serviceId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Service not found with id: "
                                                + serviceId
                                )
                        );

        if (!service.getBranch().getId().equals(branchId)) {
            throw new ResourceNotFoundException(
                    "Service not found for branch"
            );
        }

        if (!service.isActive()) {
            return List.of();
        }

        return serviceSessionRepository
                .findByServiceIdAndLocalDateAndBookingOpenTrueOrderByStartTimeAsc(
                        serviceId,
                        date
                )
                .stream()
                .map(this::toSessionResponse)
                .filter(response ->
                        response.remainingCapacity() > 0
                )
                .toList();
    }

    @Transactional
    public PreQueueReservationResponse createReservation(
            Long serviceSessionId,
            Long userId
    ) {

        ServiceSession session =
                serviceSessionRepository
                        .findByIdForUpdate(serviceSessionId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Service session not found with id: "
                                                + serviceSessionId
                                )
                        );

        if (!session.isBookingOpen()) {
            throw new IllegalStateException(
                    "Service session is not open for booking"
            );
        }

        if (!session.getService().isActive()) {
            throw new IllegalStateException(
                    "Service is not active"
            );
        }

        long reservedCount =
                preQueueReservationRepository
                        .countByServiceSessionIdAndStatus(
                                session.getId(),
                                PreQueueReservationStatus.RESERVED
                        );

        if (reservedCount >= session.getCapacity()) {
            throw new IllegalStateException(
                    "Service session is fully booked"
            );
        }

        UserAccount user = null;
        String rawGuestToken = null;
        String guestTokenHash = null;

        if (userId != null) {
            user =
                    userAccountRepository
                            .findById(userId)
                            .orElseThrow(() ->
                                    new ResourceNotFoundException(
                                            "User not found with id: "
                                                    + userId
                                    )
                            );
        } else {
            rawGuestToken =
                    authTokenService.generateToken();

            guestTokenHash =
                    authTokenService.hashToken(
                            rawGuestToken
                    );
        }

        PreQueueReservation reservation =
                new PreQueueReservation(
                        session,
                        user,
                        guestTokenHash
                );

        PreQueueReservation savedReservation =
                preQueueReservationRepository.save(
                        reservation
                );

        return toReservationResponse(
                savedReservation,
                rawGuestToken
        );
    }

    @Transactional(readOnly = true)
    public PreQueueReservationResponse getReservation(
        Long reservationId,
        Long userId,
        String guestToken
 ) {

    preQueueReservationExpiryService.expireIfNeeded(
        reservationId
    );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findById(reservationId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Pre-queue reservation not found with id: "
                                            + reservationId
                            )
                    );

    boolean registeredOwner =
            reservation.getUser() != null
                    && userId != null
                    && reservation.getUser()
                    .getId()
                    .equals(userId);

    boolean guestOwner =
            reservation.getUser() == null
                    && guestToken != null
                    && !guestToken.isBlank()
                    && reservation.getGuestTokenHash() != null
                    && reservation.getGuestTokenHash()
                    .equals(
                            authTokenService.hashToken(
                                    guestToken
                            )
                    );

    if (!registeredOwner && !guestOwner) {
        throw new org.springframework.security.access.AccessDeniedException(
                "You cannot view this reservation"
        );
    }

    return toReservationResponse(
            reservation,
            null
    );
 }

    @Transactional
    public PreQueueReservationResponse cancelReservation(
        Long reservationId,
        Long userId,
        String guestToken
  ) {

    preQueueReservationExpiryService.expireIfNeeded(
        reservationId
    );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByIdForUpdate(reservationId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Pre-queue reservation not found with id: "
                                            + reservationId
                            )
                    );

    boolean registeredOwner =
            reservation.getUser() != null
                    && userId != null
                    && reservation.getUser()
                    .getId()
                    .equals(userId);

    boolean guestOwner =
            reservation.getUser() == null
                    && guestToken != null
                    && !guestToken.isBlank()
                    && reservation.getGuestTokenHash() != null
                    && reservation.getGuestTokenHash()
                    .equals(
                            authTokenService.hashToken(
                                    guestToken
                            )
                    );

    if (!registeredOwner && !guestOwner) {
        throw new org.springframework.security.access.AccessDeniedException(
                "You cannot cancel this reservation"
        );
    }

    if (reservation.getStatus()
            != PreQueueReservationStatus.RESERVED) {

        throw new IllegalStateException(
                "Only a reserved reservation can be cancelled"
        );
    }

    reservation.setStatus(
            PreQueueReservationStatus.CANCELLED
    );

    reservation.setCancelledAt(
            java.time.OffsetDateTime.now()
    );

    PreQueueReservation savedReservation =
            preQueueReservationRepository.save(
                    reservation
            );

    return toReservationResponse(
            savedReservation,
            null
    );
 }

    @Transactional
    public PreQueueReservationResponse rescheduleReservation(
        Long reservationId,
        Long newServiceSessionId,
        Long userId,
        String guestToken
    ) {

    preQueueReservationExpiryService.expireIfNeeded(
        reservationId
    );


    ServiceSession newSession =
            serviceSessionRepository
                    .findByIdForUpdate(newServiceSessionId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Service session not found with id: "
                                            + newServiceSessionId
                            )
                    );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByIdForUpdate(reservationId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Pre-queue reservation not found with id: "
                                            + reservationId
                            )
                    );

    boolean registeredOwner =
            reservation.getUser() != null
                    && userId != null
                    && reservation.getUser()
                    .getId()
                    .equals(userId);

    boolean guestOwner =
            reservation.getUser() == null
                    && guestToken != null
                    && !guestToken.isBlank()
                    && reservation.getGuestTokenHash() != null
                    && reservation.getGuestTokenHash()
                    .equals(
                            authTokenService.hashToken(
                                    guestToken
                            )
                    );

    if (!registeredOwner && !guestOwner) {
        throw new org.springframework.security.access.AccessDeniedException(
                "You cannot reschedule this reservation"
        );
    }

    if (reservation.getStatus()
            != PreQueueReservationStatus.RESERVED) {

        throw new IllegalStateException(
                "Only a reserved reservation can be rescheduled"
        );
    }

    if (!newSession.isBookingOpen()) {
        throw new IllegalStateException(
                "Service session is not open for booking"
        );
    }

    if (!newSession.getService().isActive()) {
        throw new IllegalStateException(
                "Service is not active"
        );
    }

    if (!reservation.getServiceSession()
            .getService()
            .getId()
            .equals(
                    newSession.getService().getId()
            )) {

        throw new IllegalStateException(
                "Reservation can only be rescheduled within the same service"
        );
    }

    if (!reservation.getServiceSession()
            .getId()
            .equals(newSession.getId())) {

        long reservedCount =
                preQueueReservationRepository
                        .countByServiceSessionIdAndStatus(
                                newSession.getId(),
                                PreQueueReservationStatus.RESERVED
                        );

        if (reservedCount >= newSession.getCapacity()) {
            throw new IllegalStateException(
                    "Service session is fully booked"
            );
        }

        reservation.setServiceSession(
                newSession
        );
    }

    PreQueueReservation savedReservation =
            preQueueReservationRepository.save(
                    reservation
            );

    return toReservationResponse(
            savedReservation,
            null
    );
 }

    private ServiceSessionResponse toSessionResponse(
            ServiceSession session
    ) {

        long reservedCount =
                preQueueReservationRepository
                        .countByServiceSessionIdAndStatus(
                                session.getId(),
                                PreQueueReservationStatus.RESERVED
                        );

        int remainingCapacity =
                Math.max(
                        0,
                        session.getCapacity()
                                - Math.toIntExact(reservedCount)
                );

        return new ServiceSessionResponse(
                session.getId(),
                session.getService().getId(),
                session.getLocalDate(),
                session.getStartTime(),
                session.getEndTime(),
                session.getCapacity(),
                reservedCount,
                remainingCapacity,
                session.isBookingOpen()
        );
    }

    @Transactional
public PreQueueCheckInResponse checkInReservation(
        Long reservationId,
        Long userId,
        String guestToken
) {

    preQueueReservationExpiryService.expireIfNeeded(
        reservationId
    );
PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByIdForUpdate(reservationId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Pre-queue reservation not found with id: "
                                            + reservationId
                            )
                    );

    boolean registeredOwner =
            reservation.getUser() != null
                    && userId != null
                    && reservation.getUser()
                    .getId()
                    .equals(userId);

    boolean guestOwner =
            reservation.getUser() == null
                    && guestToken != null
                    && !guestToken.isBlank()
                    && reservation.getGuestTokenHash() != null
                    && reservation.getGuestTokenHash()
                    .equals(
                            authTokenService.hashToken(
                                    guestToken
                            )
                    );

    if (!registeredOwner && !guestOwner) {
        throw new org.springframework.security.access.AccessDeniedException(
                "You cannot check in this reservation"
        );
    }

    if (reservation.getStatus()
            == PreQueueReservationStatus.EXPIRED) {

        throw new IllegalStateException(
                "Reservation check-in window has closed"
        );
    }

    if (reservation.getStatus()
            != PreQueueReservationStatus.RESERVED) {

        throw new IllegalStateException(
                "Only a reserved reservation can be checked in"
        );
    }

    ServiceSession session =
            reservation.getServiceSession();

    com.queueflow.api.entity.Service service =
            session.getService();

    ZoneId branchZone =
            ZoneId.of(
                    service.getBranch()
                            .getTimezone()
            );

    ZonedDateTime branchNow =
            ZonedDateTime.now(
                    branchZone
            );

    LocalDate currentDate =
            branchNow.toLocalDate();

    LocalTime currentTime =
            branchNow.toLocalTime();

    if (!currentDate.equals(
            session.getLocalDate()
    )) {
        throw new IllegalStateException(
                "Reservation cannot be checked in on this date"
        );
    }

    if (currentTime.isBefore(
            session.getCheckInStartTime()
    )) {
        throw new IllegalStateException(
                "Reservation check-in window has not opened"
        );
    }

    if (currentTime.isAfter(
            session.getCheckInEndTime()
    )) {
        throw new IllegalStateException(
                "Reservation check-in window has closed"
        );
    }

    Long serviceId =
            service.getId();

    Long branchId =
            service.getBranch().getId();

    Long businessId =
            service.getBranch()
                    .getBusiness()
                    .getId();

    PublicQueueResponse queue =
            queueService.getTodayQueue(
                    businessId,
                    branchId,
                    serviceId
            );

    QueueEntryResponse queueEntry =
            queueService.joinQueue(
                    queue.id(),
                    userId,
                    reservation.getReservationCode(),
                    new JoinQueueRequest(
                            serviceId
                    )
            );

    com.queueflow.api.entity.QueueEntry savedQueueEntry =
            queueEntryRepository
                    .findById(queueEntry.id())
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "Queue entry not found after check-in"
                            )
                    );

    reservation.setQueueEntry(
            savedQueueEntry
    );

    reservation.setStatus(
            PreQueueReservationStatus.CHECKED_IN
    );

    reservation.setCheckedInAt(
            java.time.OffsetDateTime.now()
    );

    PreQueueReservation savedReservation =
            preQueueReservationRepository.save(
                    reservation
            );

    return new PreQueueCheckInResponse(
            toReservationResponse(
                    savedReservation,
                    null
            ),
            queueEntry
    );
 }

    private PreQueueReservationResponse toReservationResponse(
            PreQueueReservation reservation,
            String guestToken
    ) {

        ServiceSession session =
                reservation.getServiceSession();

        return new PreQueueReservationResponse(
                reservation.getId(),
                reservation.getReservationCode(),
                session.getId(),
                session.getService().getId(),
                session.getService()
                        .getBranch()
                        .getId(),
                session.getLocalDate(),
                session.getStartTime(),
                session.getEndTime(),
                reservation.getStatus(),
                reservation.getCreatedAt(),
                reservation.getCancelledAt(),
                reservation.getCheckedInAt(),
                reservation.getQueueEntry() == null
                        ? null
                        : reservation.getQueueEntry().getId(),
                guestToken
        );
    }
}

