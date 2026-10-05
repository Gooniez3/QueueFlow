package com.queueflow.api.service;

import com.queueflow.api.response.PublicQueueResponse;
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
import com.queueflow.api.response.StaffDashboardCountsResponse;
import com.queueflow.api.response.StaffDashboardQueueResponse;
import com.queueflow.api.response.StaffDashboardResponse;
import com.queueflow.api.response.StaffDashboardServiceResponse;
import com.queueflow.api.security.AuthTokenService;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import com.queueflow.api.security.GuestTokenEncryptionService;
import com.queueflow.api.entity.GuestJoinIdempotency;
import com.queueflow.api.entity.StaffMutationIdempotency;
import com.queueflow.api.repository.StaffMutationIdempotencyRepository;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.ZoneId;
import java.util.List;
import java.util.UUID;

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
    private final GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;
    private final GuestTokenEncryptionService guestTokenEncryptionService;
    private final StaffMutationIdempotencyRepository staffMutationIdempotencyRepository;

    public QueueService(
            QueueRepository queueRepository,
            QueueEntryRepository queueEntryRepository,
            BranchRepository branchRepository,
            ServiceRepository serviceRepository,
            UserAccountRepository userAccountRepository,
            AuthTokenService authTokenService,
            BusinessAuthorizationService businessAuthorizationService,
            GuestJoinIdempotencyRepository guestJoinIdempotencyRepository,
            GuestTokenEncryptionService guestTokenEncryptionService,
            StaffMutationIdempotencyRepository staffMutationIdempotencyRepository
    ) {
        this.queueRepository = queueRepository;
        this.queueEntryRepository = queueEntryRepository;
        this.branchRepository = branchRepository;
        this.serviceRepository = serviceRepository;
        this.userAccountRepository = userAccountRepository;
        this.authTokenService = authTokenService;
        this.businessAuthorizationService = businessAuthorizationService;
        this.guestJoinIdempotencyRepository = guestJoinIdempotencyRepository;
        this.guestTokenEncryptionService = guestTokenEncryptionService;
        this.staffMutationIdempotencyRepository =
                staffMutationIdempotencyRepository;
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

   try {
       Queue savedQueue =
            queueRepository.saveAndFlush(queue);

    return toResponse(savedQueue);

   } catch (DataIntegrityViolationException exception) {

    if (service != null) {
        throw new IllegalStateException(
                "Queue already exists for this service today",
                exception
        );
    }

    throw new IllegalStateException(
            "Shared queue already exists for this branch today",
            exception
         );
     }
 }
    @Transactional(readOnly = true)
    public PublicQueueResponse getTodayQueue(
        Long businessId,
        Long branchId,
        Long serviceId
   ) {

    Branch branch = requireBranch(
            businessId,
            branchId
    );

    LocalDate businessDate = LocalDate.now(
            ZoneId.of(branch.getTimezone())
    );

    Queue queue;

    if (serviceId != null) {

        com.queueflow.api.entity.Service service =
                serviceRepository
                        .findById(serviceId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "Service not found with id: "
                                                + serviceId
                                )
                        );

        if (!service.getBranch()
                .getId()
                .equals(branchId)) {

            throw new ResourceNotFoundException(
                    "Service not found with id: "
                            + serviceId
            );
        }

        queue = queueRepository
                .findByBranchIdAndServiceIdAndBusinessDate(
                        branchId,
                        serviceId,
                        businessDate
                )
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found for today"
                        )
                );

    } else {

        queue = queueRepository
                .findByBranchIdAndServiceIsNullAndBusinessDate(
                        branchId,
                        businessDate
                )
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "Queue not found for today"
                        )
                );
    }

    Long responseServiceId =
            queue.getService() == null
                    ? null
                    : queue.getService().getId();

    return new PublicQueueResponse(
            queue.getId(),
            queue.getBranch().getId(),
            responseServiceId,
            queue.getName(),
            queue.getBusinessDate(),
            queue.getTicketPrefix(),
            queue.getStatus()
    );
  }

    @Transactional
    public QueueEntryResponse joinQueue(
            Long queueId,
            Long userId,
            String idempotencyKey,
            JoinQueueRequest request
    ) {
        String normalizedIdempotencyKey =
        normalizeIdempotencyKey(idempotencyKey);

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
         if (userId == null
        && normalizedIdempotencyKey != null) {

    GuestJoinIdempotency existing =
            guestJoinIdempotencyRepository
                    .findByQueueIdAndIdempotencyKey(
                            queueId,
                            normalizedIdempotencyKey
                    )
                    .orElse(null);

    if (existing != null) {

        if (existing.getExpiresAt()
                .isAfter(OffsetDateTime.now())) {

            if (!existing.getService()
                    .getId()
                    .equals(service.getId())) {

                throw new IllegalStateException(
                        "Idempotency-Key was already used with a different request"
                );
            }

            QueueEntry existingEntry =
                    existing.getQueueEntry();

            String guestToken =
                    guestTokenEncryptionService.decrypt(
                            existing.getEncryptedGuestToken()
                    );

            String ticketNumber =
                    formatTicketNumber(
                            queue.getTicketPrefix(),
                            existingEntry.getTicketSequence()
                    );

            return toEntryResponse(
                    existingEntry,
                    ticketNumber,
                    guestToken
            );
        }

        guestJoinIdempotencyRepository.delete(existing);
        guestJoinIdempotencyRepository.flush();
    }
}

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

        if (userId == null
        && normalizedIdempotencyKey != null) {

    String encryptedGuestToken =
            guestTokenEncryptionService.encrypt(
                    rawGuestToken
            );

    GuestJoinIdempotency idempotencyRecord =
            new GuestJoinIdempotency(
                    queue,
                    savedEntry,
                    service,
                    normalizedIdempotencyKey,
                    encryptedGuestToken,
                    OffsetDateTime.now().plusHours(24)
            );

    guestJoinIdempotencyRepository.save(
            idempotencyRecord
    );
 }

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
            Long entryId,
            Long userId,
            String guestToken
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
            "You cannot view this queue entry"
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
        entry.getQueue().getName(),

        entry.getQueue()
                .getBranch()
                .getBusiness()
                .getId(),

        entry.getQueue()
                .getBranch()
                .getBusiness()
                .getName(),

        entry.getQueue()
                .getBranch()
                .getId(),

        entry.getQueue()
                .getBranch()
                .getName(),

        entry.getService().getId(),
        entry.getService().getName(),

        entry.getTicketSequence(),
        ticketNumber,
        entry.getStatus(),
        peopleAhead,
        estimatedWaitMinutes
   );
    }

    @Transactional(readOnly = true)
    public StaffDashboardResponse getStaffDashboard(
            Long businessId,
            Long branchId,
            Long staffUserId
    ) {

        Branch branch = requireBranch(
                businessId,
                branchId
        );

        businessAuthorizationService.requireBranchAccess(
                staffUserId,
                businessId,
                branchId
        );

        LocalDate businessDate = LocalDate.now(
                ZoneId.of(branch.getTimezone())
        );

        List<Queue> queues =
                queueRepository.findByBranchIdAndBusinessDate(
                        branchId,
                        businessDate
                );

        List<StaffDashboardQueueResponse> dashboardQueues =
                queues.stream()
                        .map(this::toStaffDashboardQueueResponse)
                        .toList();

        return new StaffDashboardResponse(
                businessId,
                branchId,
                businessDate,
                dashboardQueues
        );
    }
    @Transactional
    public QueueStaffEntryResponse callNext(
            Long queueId,
            Long staffUserId,
            String idempotencyKey
    ) {
        String normalizedIdempotencyKey =
                normalizeIdempotencyKey(idempotencyKey);

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
        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        QueueStaffEntryResponse replay =
                replayStaffMutation(
                        queue,
                        staffUserId,
                        null,
                        "CALL_NEXT",
                        normalizedIdempotencyKey
                );

        if (replay != null) {
            return replay;
        }

        if (queue.getStatus() != QueueStatus.OPEN) {
            throw new IllegalStateException(
                    "Queue must be open to call the next entry"
            );
        }

        if (queueEntryRepository.existsByQueueIdAndStatus(
                queueId,
                QueueEntryStatus.CALLED
        )) {
            throw new IllegalStateException(
                    "Queue already has a called entry"
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

        saveStaffMutationIdempotency(
                queue,
                staffUserId,
                savedEntry,
                "CALL_NEXT",
                normalizedIdempotencyKey
        );

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse recallEntry(
            Long queueId,
            Long entryId,
            Long staffUserId,
            String idempotencyKey
    ) {
        String normalizedIdempotencyKey =
                normalizeIdempotencyKey(idempotencyKey);

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

        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        QueueStaffEntryResponse replay =
                replayStaffMutation(
                        queue,
                        staffUserId,
                        entryId,
                        "RECALL",
                        normalizedIdempotencyKey
                );

        if (replay != null) {
            return replay;
        }

        requireQueueActiveForEntryMutation(queue);

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

        if (entry.getStatus() != QueueEntryStatus.CALLED) {
            throw new IllegalStateException(
                    "Only a called entry can be recalled"
            );
        }

        entry.setCalledAt(
                OffsetDateTime.now()
        );

        QueueEntry savedEntry =
                queueEntryRepository.save(entry);

        saveStaffMutationIdempotency(
                queue,
                staffUserId,
                savedEntry,
                "RECALL",
                normalizedIdempotencyKey
        );

        return toStaffEntryResponse(
                savedEntry
        );
    }
    @Transactional
    public QueueStaffEntryResponse startServing(
            Long queueId,
            Long entryId,
            Long staffUserId,
            String idempotencyKey
    ) {
        String normalizedIdempotencyKey =
                normalizeIdempotencyKey(idempotencyKey);

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

        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        QueueStaffEntryResponse replay =
                replayStaffMutation(
                        queue,
                        staffUserId,
                        entryId,
                        "START_SERVING",
                        normalizedIdempotencyKey
                );

        if (replay != null) {
            return replay;
        }

        requireQueueActiveForEntryMutation(queue);

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

        if (queueEntryRepository.existsByQueueIdAndStatus(
                queueId,
                QueueEntryStatus.SERVING
        )) {
            throw new IllegalStateException(
                    "Queue already has a serving entry"
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

        saveStaffMutationIdempotency(
                queue,
                staffUserId,
                savedEntry,
                "START_SERVING",
                normalizedIdempotencyKey
        );

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse completeEntry(
            Long queueId,
            Long entryId,
            Long staffUserId,
            String idempotencyKey
    ) {
        String normalizedIdempotencyKey =
                normalizeIdempotencyKey(idempotencyKey);

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
        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        QueueStaffEntryResponse replay =
                replayStaffMutation(
                        queue,
                        staffUserId,
                        entryId,
                        "COMPLETE",
                        normalizedIdempotencyKey
                );

        if (replay != null) {
            return replay;
        }

        requireQueueActiveForEntryMutation(queue);

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

        saveStaffMutationIdempotency(
                queue,
                staffUserId,
                savedEntry,
                "COMPLETE",
                normalizedIdempotencyKey
        );

        return toStaffEntryResponse(
                savedEntry
        );
    }

    @Transactional
    public QueueStaffEntryResponse skipEntry(
            Long queueId,
            Long entryId,
            Long staffUserId,
            String idempotencyKey
    ) {
        String normalizedIdempotencyKey =
                normalizeIdempotencyKey(idempotencyKey);

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

        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        QueueStaffEntryResponse replay =
                replayStaffMutation(
                        queue,
                        staffUserId,
                        entryId,
                        "SKIP",
                        normalizedIdempotencyKey
                );

        if (replay != null) {
            return replay;
        }

        requireQueueActiveForEntryMutation(queue);

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

        saveStaffMutationIdempotency(
                queue,
                staffUserId,
                savedEntry,
                "SKIP",
                normalizedIdempotencyKey
        );

        return toStaffEntryResponse(
                savedEntry
        );
    }

    private QueueStaffEntryResponse replayStaffMutation(
            Queue queue,
            Long staffUserId,
            Long requestedEntryId,
            String operation,
            String normalizedIdempotencyKey
    ) {

        if (normalizedIdempotencyKey == null) {
            return null;
        }

        StaffMutationIdempotency existing =
                staffMutationIdempotencyRepository
                        .findByQueueIdAndStaffUserIdAndIdempotencyKey(
                                queue.getId(),
                                staffUserId,
                                normalizedIdempotencyKey
                        )
                        .orElse(null);

        if (existing == null) {
            return null;
        }

        if (!existing.getExpiresAt()
                .isAfter(OffsetDateTime.now())) {

            staffMutationIdempotencyRepository.delete(existing);
            staffMutationIdempotencyRepository.flush();
            return null;
        }

        if (!existing.getOperation().equals(operation)) {
            throw new IllegalStateException(
                    "Idempotency-Key was already used with a different request"
            );
        }

        if (requestedEntryId != null
                && !existing.getQueueEntry()
                        .getId()
                        .equals(requestedEntryId)) {

            throw new IllegalStateException(
                    "Idempotency-Key was already used with a different request"
            );
        }

        return toStaffEntryResponse(
                existing.getQueueEntry()
        );
    }

    private void saveStaffMutationIdempotency(
            Queue queue,
            Long staffUserId,
            QueueEntry queueEntry,
            String operation,
            String normalizedIdempotencyKey
    ) {

        if (normalizedIdempotencyKey == null) {
            return;
        }

        UserAccount staffUser =
                userAccountRepository
                        .findById(staffUserId)
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "User not found with id: "
                                                + staffUserId
                                )
                        );

        StaffMutationIdempotency record =
                new StaffMutationIdempotency(
                        queue,
                        staffUser,
                        queueEntry,
                        operation,
                        normalizedIdempotencyKey,
                        OffsetDateTime.now().plusHours(24)
                );

        staffMutationIdempotencyRepository.save(record);
    }

    private void requireQueueActiveForEntryMutation(
            Queue queue
    ) {

        if (queue.getStatus() == QueueStatus.CLOSED) {
            throw new IllegalStateException(
                    "Closed queue does not allow entry mutations"
            );
        }
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

        Long branchId =
                queue.getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
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

    Long branchId =
            queue.getBranch()
                    .getId();

    businessAuthorizationService
            .requireBranchAccess(
                    staffUserId,
                    businessId,
                    branchId
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
    Long branchId =
            queue.getBranch()
                    .getId();

    businessAuthorizationService
            .requireBranchAccess(
                    staffUserId,
                    businessId,
                    branchId
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

    if (!queue.getService().isActive()) {
        throw new IllegalStateException(
                "Service is not active"
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

    private StaffDashboardQueueResponse toStaffDashboardQueueResponse(
            Queue queue
    ) {

        List<QueueEntry> waitingEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queue.getId(),
                                QueueEntryStatus.WAITING
                        );

        List<QueueEntry> calledEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queue.getId(),
                                QueueEntryStatus.CALLED
                        );

        List<QueueEntry> servingEntries =
                queueEntryRepository
                        .findByQueueIdAndStatusOrderByTicketSequenceAsc(
                                queue.getId(),
                                QueueEntryStatus.SERVING
                        );

        if (calledEntries.size() > 1) {
            throw new IllegalStateException(
                    "Queue invariant violated: multiple CALLED entries for queue "
                            + queue.getId()
            );
        }

        if (servingEntries.size() > 1) {
            throw new IllegalStateException(
                    "Queue invariant violated: multiple SERVING entries for queue "
                            + queue.getId()
            );
        }

        QueueStaffEntryResponse called =
                calledEntries.isEmpty()
                        ? null
                        : toStaffEntryResponse(
                                calledEntries.get(0)
                        );

        QueueStaffEntryResponse serving =
                servingEntries.isEmpty()
                        ? null
                        : toStaffEntryResponse(
                                servingEntries.get(0)
                        );

        List<QueueStaffEntryResponse> waiting =
                waitingEntries.stream()
                        .map(this::toStaffEntryResponse)
                        .toList();

        StaffDashboardServiceResponse service = null;

        if (queue.getService() != null) {
            service =
                    new StaffDashboardServiceResponse(
                            queue.getService().getId(),
                            queue.getService().getName(),
                            queue.getService().getDurationMinutes()
                    );
        }

        StaffDashboardCountsResponse counts =
                new StaffDashboardCountsResponse(
                        waitingEntries.size(),
                        calledEntries.size(),
                        servingEntries.size()
                );

        return new StaffDashboardQueueResponse(
                queue.getId(),
                queue.getName(),
                queue.getStatus(),
                queue.getTicketPrefix(),
                service,
                counts,
                serving,
                called,
                waiting
        );
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
    private String normalizeIdempotencyKey(
        String idempotencyKey
    ) {
    if (idempotencyKey == null
            || idempotencyKey.isBlank()) {
        return null;
    }

    String normalized =
            idempotencyKey.trim().toLowerCase();

    try {
        UUID uuid = UUID.fromString(normalized);

        if (!uuid.toString().equals(normalized)) {
            throw new IllegalArgumentException();
        }

        return normalized;

    } catch (IllegalArgumentException exception) {
        throw new IllegalArgumentException(
                "Idempotency-Key must be a valid UUID"
        );
    }
  }
}
