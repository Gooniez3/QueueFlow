package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.entity.QueueStatus;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.request.CreateQueueRequest;
import com.queueflow.api.request.JoinQueueRequest;
import com.queueflow.api.response.QueueEntryResponse;
import com.queueflow.api.response.QueuePositionResponse;
import com.queueflow.api.response.QueueResponse;
import com.queueflow.api.response.QueueStaffEntryResponse;
import com.queueflow.api.security.AuthTokenService;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.ZoneId;
import java.util.List;

@Service
public class QueueService {

    private static final List<QueueEntryStatus> ACTIVE_ENTRY_STATUSES =
            List.of(
                    QueueEntryStatus.WAITING,
                    QueueEntryStatus.CALLED,
                    QueueEntryStatus.SERVING
            );

    private final QueueRepository queueRepository;
    private final QueueEntryRepository queueEntryRepository;
    private final BranchRepository branchRepository;
    private final ServiceRepository serviceRepository;
    private final UserAccountRepository userAccountRepository;
    private final AuthTokenService authTokenService;
    private final BusinessAuthorizationService businessAuthorizationService;

    public QueueService(
            QueueRepository queueRepository,
            QueueEntryRepository queueEntryRepository,
            BranchRepository branchRepository,
            ServiceRepository serviceRepository,
            UserAccountRepository userAccountRepository,
            AuthTokenService authTokenService,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.queueRepository = queueRepository;
        this.queueEntryRepository = queueEntryRepository;
        this.branchRepository = branchRepository;
        this.serviceRepository = serviceRepository;
        this.userAccountRepository = userAccountRepository;
        this.authTokenService = authTokenService;
        this.businessAuthorizationService = businessAuthorizationService;
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

    @Transactional
    public QueueEntryResponse joinQueue(
            Long queueId,
            Long userId,
            JoinQueueRequest request
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        if (queue.getStatus() != QueueStatus.OPEN) {
            throw new IllegalStateException(
                    "Queue is not open for joining"
            );
        }

        com.queueflow.api.entity.Service service =
                resolveJoinService(
                        queue,
                        request.serviceId()
                );

        UserAccount user = null;
        String rawGuestToken = null;
        String guestTokenHash = null;

        if (userId != null) {

            user = userAccountRepository
                    .findById(userId)
                    .orElseThrow(() ->
                            new ResourceNotFoundException(
                                    "User not found with id: "
                                            + userId
                            )
                    );

            boolean alreadyInQueue =
                    queueEntryRepository
                            .existsByQueueIdAndUserIdAndStatusIn(
                                    queueId,
                                    userId,
                                    ACTIVE_ENTRY_STATUSES
                            );

            if (alreadyInQueue) {
                throw new IllegalStateException(
                        "User already has an active ticket in this queue"
                );
            }

        } else {

            rawGuestToken =
                    authTokenService.generateToken();

            guestTokenHash =
                    authTokenService.hashToken(
                            rawGuestToken
                    );
        }

        Integer ticketSequence =
                queue.getNextTicketSequence();

        queue.setNextTicketSequence(
                ticketSequence + 1
        );

        QueueEntry queueEntry = new QueueEntry(
                queue,
                service,
                user,
                ticketSequence,
                guestTokenHash
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(queueEntry);

        String ticketNumber =
                formatTicketNumber(
                        queue.getTicketPrefix(),
                        ticketSequence
                );

        return toEntryResponse(
                savedEntry,
                ticketNumber,
                rawGuestToken
        );
    }
    @Transactional
    public QueueEntryResponse cancelQueueEntry(
        Long queueId,
        Long entryId,
        Long userId,
        String guestToken
 ) {

    Queue queue = queueRepository
            .findByIdForUpdate(queueId)
            .orElseThrow(() ->
                    new ResourceNotFoundException(
                            "Queue not found with id: "
                                    + queueId
                    )
            );

    QueueEntry entry = queueEntryRepository
            .findById(entryId)
            .orElseThrow(() ->
                    new ResourceNotFoundException(
                            "Queue entry not found with id: "
                                    + entryId
                    )
            );

    if (!entry.getQueue()
            .getId()
            .equals(queueId)) {

        throw new ResourceNotFoundException(
                "Queue entry not found with id: "
                        + entryId
        );
    }

    boolean registeredOwner =
        entry.getUser() != null
                && userId != null
                && entry.getUser()
                        .getId()
                        .equals(userId);

  boolean guestOwner =
        entry.getUser() == null
                && guestToken != null
                && !guestToken.isBlank()
                && entry.getGuestTokenHash() != null
                && entry.getGuestTokenHash()
                        .equals(
                                authTokenService.hashToken(
                                        guestToken
                                )
                        );

  if (!registeredOwner && !guestOwner) {
    throw new org.springframework.security.access.AccessDeniedException(
            "You cannot cancel this queue entry"
    );
 }

    if (entry.getStatus()
            != QueueEntryStatus.WAITING) {

        throw new IllegalStateException(
                "Only a waiting queue entry can be cancelled"
        );
    }

    entry.setStatus(
            QueueEntryStatus.CANCELLED
    );

    entry.setCancelledAt(
            OffsetDateTime.now()
    );

    QueueEntry savedEntry =
            queueEntryRepository.save(entry);

    return toEntryResponse(
            savedEntry,
            formatTicketNumber(
                    queue.getTicketPrefix(),
                    savedEntry.getTicketSequence()
            ),
            null
    );
 }

    @Transactional(readOnly = true)
    public QueuePositionResponse getQueuePosition(
            Long queueId,
            Long entryId
    ) {

        QueueEntry entry = queueEntryRepository
                .findById(entryId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue entry not found with id: "
                                        + entryId
                        )
                );

        if (!entry.getQueue()
                .getId()
                .equals(queueId)) {

            throw new ResourceNotFoundException(
                    "Queue entry not found with id: "
                            + entryId
            );
        }

        List<QueueEntry> entriesAhead =
                queueEntryRepository
                        .findByQueueIdAndStatusInAndTicketSequenceLessThanOrderByTicketSequenceAsc(
                                queueId,
                                ACTIVE_ENTRY_STATUSES,
                                entry.getTicketSequence()
                        );

        int peopleAhead =
                entriesAhead.size();

        int estimatedWaitMinutes =
                entriesAhead
                        .stream()
                        .mapToInt(queueEntry ->
                                queueEntry
                                        .getService()
                                        .getDurationMinutes()
                        )
                        .sum();

        String ticketNumber =
                formatTicketNumber(
                        entry.getQueue().getTicketPrefix(),
                        entry.getTicketSequence()
                );

        return new QueuePositionResponse(
                entry.getId(),
                entry.getQueue().getId(),
                entry.getService().getId(),
                entry.getTicketSequence(),
                ticketNumber,
                entry.getStatus(),
                peopleAhead,
                estimatedWaitMinutes
        );
    }

    @Transactional
    public QueueStaffEntryResponse callNext(
            Long queueId,
            Long staffUserId
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        businessAuthorizationService
                .requireMembership(
                        staffUserId,
                        businessId
                );

        if (queue.getStatus() != QueueStatus.OPEN) {
            throw new IllegalStateException(
                    "Queue must be open to call the next entry"
            );
        }

        QueueEntry entry =
                queueEntryRepository
                        .findFirstByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queueId,
                                QueueEntryStatus.WAITING
                        )
                        .orElseThrow(() ->
                                new IllegalStateException(
                                        "No waiting entries in this queue"
                                )
                        );

        entry.setStatus(
                QueueEntryStatus.CALLED
        );

        entry.setCalledAt(
                OffsetDateTime.now()
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(entry);

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse startServing(
            Long queueId,
            Long entryId,
            Long staffUserId
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        businessAuthorizationService
                .requireMembership(
                        staffUserId,
                        businessId
                );

        QueueEntry entry = queueEntryRepository
                .findById(entryId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue entry not found with id: "
                                        + entryId
                        )
                );

        if (!entry.getQueue()
                .getId()
                .equals(queueId)) {

            throw new IllegalArgumentException(
                    "Queue entry does not belong to this queue"
            );
        }

        if (entry.getStatus() != QueueEntryStatus.CALLED) {
            throw new IllegalStateException(
                    "Only a called entry can start serving"
            );
        }

        entry.setStatus(
                QueueEntryStatus.SERVING
        );

        entry.setServingAt(
                OffsetDateTime.now()
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(entry);

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse completeEntry(
            Long queueId,
            Long entryId,
            Long staffUserId
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        businessAuthorizationService
                .requireMembership(
                        staffUserId,
                        businessId
                );

        QueueEntry entry = queueEntryRepository
                .findById(entryId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue entry not found with id: "
                                        + entryId
                        )
                );

        if (!entry.getQueue()
                .getId()
                .equals(queueId)) {

            throw new IllegalArgumentException(
                    "Queue entry does not belong to this queue"
            );
        }

        if (entry.getStatus() != QueueEntryStatus.SERVING) {
            throw new IllegalStateException(
                    "Only a serving entry can be completed"
            );
        }

        entry.setStatus(
                QueueEntryStatus.COMPLETED
        );

        entry.setCompletedAt(
                OffsetDateTime.now()
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(entry);

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse skipEntry(
            Long queueId,
            Long entryId,
            Long staffUserId
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        businessAuthorizationService
                .requireMembership(
                        staffUserId,
                        businessId
                );

        QueueEntry entry = queueEntryRepository
                .findById(entryId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue entry not found with id: "
                                        + entryId
                        )
                );

        if (!entry.getQueue()
                .getId()
                .equals(queueId)) {

            throw new IllegalArgumentException(
                    "Queue entry does not belong to this queue"
            );
        }

        if (entry.getStatus() != QueueEntryStatus.CALLED) {
            throw new IllegalStateException(
                    "Only a called entry can be skipped"
            );
        }

        entry.setStatus(
                QueueEntryStatus.SKIPPED
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(entry);

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueResponse pauseQueue(
            Long queueId,
            Long staffUserId
    ) {

        Queue queue = queueRepository
                .findByIdForUpdate(queueId)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found with id: "
                                        + queueId
                        )
                );

        Long businessId =
                queue.getBranch()
                        .getBusiness()
                        .getId();

        businessAuthorizationService
                .requireMembership(
                        staffUserId,
                        businessId
                );

        if (queue.getStatus() != QueueStatus.OPEN) {
            throw new IllegalStateException(
                    "Only an open queue can be paused"
            );
        }

        queue.setStatus(
                QueueStatus.PAUSED
        );

        Queue savedQueue =
                queueRepository.save(queue);

        return toResponse(savedQueue);
    }

    @Transactional
    public QueueResponse resumeQueue(
        Long queueId,
        Long staffUserId
    ) {

    Queue queue = queueRepository
            .findByIdForUpdate(queueId)
            .orElseThrow(() ->
                    new ResourceNotFoundException(
                            "Queue not found with id: "
                                    + queueId
                    )
            );

    Long businessId =
            queue.getBranch()
                    .getBusiness()
                    .getId();

    businessAuthorizationService
            .requireMembership(
                    staffUserId,
                    businessId
            );

    if (queue.getStatus() != QueueStatus.PAUSED) {
        throw new IllegalStateException(
                "Only a paused queue can be resumed"
        );
    }

    queue.setStatus(
            QueueStatus.OPEN
    );

    Queue savedQueue =
            queueRepository.save(queue);

    return toResponse(savedQueue);
   }
   @Transactional
   public QueueResponse closeQueue(
        Long queueId,
        Long staffUserId
   ) {

    Queue queue = queueRepository
            .findByIdForUpdate(queueId)
            .orElseThrow(() ->
                    new ResourceNotFoundException(
                            "Queue not found with id: "
                                    + queueId
                    )
            );

    Long businessId =
            queue.getBranch()
                    .getBusiness()
                    .getId();

    businessAuthorizationService
            .requireMembership(
                    staffUserId,
                    businessId
            );

    if (queue.getStatus() == QueueStatus.CLOSED) {
        throw new IllegalStateException(
                "Queue is already closed"
        );
    }

    queue.setStatus(
            QueueStatus.CLOSED
    );

    queue.setClosedAt(
            OffsetDateTime.now()
    );

    Queue savedQueue =
            queueRepository.save(queue);

    return toResponse(savedQueue);
   }

    private com.queueflow.api.entity.Service resolveJoinService(
            Queue queue,
            Long requestedServiceId
    ) {

        if (queue.getService() != null) {

            if (requestedServiceId != null
                    && !queue.getService()
                    .getId()
                    .equals(requestedServiceId)) {

                throw new IllegalStateException(
                        "Requested service does not match this queue"
                );
            }

            return queue.getService();
        }

        if (requestedServiceId == null) {
            throw new IllegalArgumentException(
                    "Service is required when joining a shared queue"
            );
        }

        com.queueflow.api.entity.Service service =
                serviceRepository
                        .findById(requestedServiceId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Service not found with id: "
                                                + requestedServiceId
                                )
                        );

        if (!service.getBranch()
                .getId()
                .equals(
                        queue.getBranch().getId()
                )) {

            throw new ResourceNotFoundException(
                    "Service not found with id: "
                            + requestedServiceId
            );
        }

        if (!service.isActive()) {
            throw new IllegalStateException(
                    "Service is not active"
            );
        }

        return service;
    }

    private String formatTicketNumber(
            String prefix,
            Integer sequence
    ) {
        return prefix
                + String.format("%03d", sequence);
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

    private QueueEntryResponse toEntryResponse(
            QueueEntry entry,
            String ticketNumber,
            String guestToken
    ) {

        Long userId =
                entry.getUser() == null
                        ? null
                        : entry.getUser().getId();

        return new QueueEntryResponse(
                entry.getId(),
                entry.getQueue().getId(),
                entry.getService().getId(),
                userId,
                entry.getTicketSequence(),
                ticketNumber,
                entry.getStatus(),
                entry.getJoinedAt(),
                guestToken
        );
    }

    private QueueStaffEntryResponse toStaffEntryResponse(
            QueueEntry entry
    ) {

        Long userId =
                entry.getUser() == null
                        ? null
                        : entry.getUser().getId();

        Long counterId =
                entry.getCounter() == null
                        ? null
                        : entry.getCounter().getId();

        String ticketNumber =
                formatTicketNumber(
                        entry.getQueue().getTicketPrefix(),
                        entry.getTicketSequence()
                );

        return new QueueStaffEntryResponse(
                entry.getId(),
                entry.getQueue().getId(),
                entry.getService().getId(),
                userId,
                counterId,
                entry.getTicketSequence(),
                ticketNumber,
                entry.getStatus(),
                entry.getJoinedAt(),
                entry.getCalledAt(),
                entry.getServingAt(),
                entry.getCompletedAt(),
                entry.getCancelledAt()
        );
    }
}