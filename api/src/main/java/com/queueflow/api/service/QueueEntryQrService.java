package com.queueflow.api.service;

import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryQrCredential;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.exception.QrCredentialInactiveException;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.QueueEntryQrCredentialRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.response.QueueEntryQrCredentialResponse;
import com.queueflow.api.response.QueueEntryQrVerificationResponse;
import com.queueflow.api.security.AuthTokenService;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.OffsetDateTime;
import java.util.EnumSet;
import java.util.List;
import java.util.Set;

@Service
public class QueueEntryQrService {

    private static final Set<QueueEntryStatus> LIVE_STATUSES =
            EnumSet.of(
                    QueueEntryStatus.WAITING,
                    QueueEntryStatus.CALLED,
                    QueueEntryStatus.SERVING
            );

    private final QueueEntryRepository queueEntryRepository;

    private final QueueEntryQrCredentialRepository
            qrCredentialRepository;

    private final AuthTokenService authTokenService;

    private final BusinessAuthorizationService
            businessAuthorizationService;

    public QueueEntryQrService(
            QueueEntryRepository queueEntryRepository,
            QueueEntryQrCredentialRepository qrCredentialRepository,
            AuthTokenService authTokenService,
            BusinessAuthorizationService businessAuthorizationService
    ) {
        this.queueEntryRepository =
                queueEntryRepository;

        this.qrCredentialRepository =
                qrCredentialRepository;

        this.authTokenService =
                authTokenService;

        this.businessAuthorizationService =
                businessAuthorizationService;
    }

    @Transactional
    public QueueEntryQrCredentialResponse issueCredential(
            Long queueId,
            Long entryId,
            Long userId,
            String guestToken
    ) {

        QueueEntry entry =
                requireEntry(
                        queueId,
                        entryId
                );

        requireCustomerOwnership(
                entry,
                userId,
                guestToken
        );

        requireLiveTicket(entry);

        OffsetDateTime now =
                OffsetDateTime.now();

        List<QueueEntryQrCredential>
                existingCredentials =
                qrCredentialRepository
                        .findByQueueEntryIdAndRevokedAtIsNull(
                                entryId
                        );

        existingCredentials.forEach(
                credential ->
                        credential.setRevokedAt(now)
        );

        if (!existingCredentials.isEmpty()) {
            qrCredentialRepository.saveAll(
                    existingCredentials
            );
        }

        String rawCredential;
        String credentialHash;

        do {
            rawCredential =
                    authTokenService.generateToken();

            credentialHash =
                    authTokenService.hashToken(
                            rawCredential
                    );

        } while (
                qrCredentialRepository
                        .existsByCredentialHash(
                                credentialHash
                        )
        );

        OffsetDateTime expiresAt =
                now.plusHours(24);

        QueueEntryQrCredential credential =
                new QueueEntryQrCredential(
                        entry,
                        credentialHash,
                        expiresAt
                );

        qrCredentialRepository.save(
                credential
        );

        return new QueueEntryQrCredentialResponse(
                rawCredential,
                expiresAt
        );
    }

    @Transactional(readOnly = true)
    public QueueEntryQrVerificationResponse verifyCredential(
            String rawCredential,
            Long staffUserId
    ) {

        String credentialHash =
                authTokenService.hashToken(
                        rawCredential
                );

        QueueEntryQrCredential credential =
                qrCredentialRepository
                        .findByCredentialHash(
                                credentialHash
                        )
                        .orElseThrow(() ->
                                new ResourceNotFoundException(
                                        "QR credential not found"
                                )
                        );

        QueueEntry entry =
                credential.getQueueEntry();

        Long businessId =
                entry.getQueue()
                        .getBranch()
                        .getBusiness()
                        .getId();

        Long branchId =
                entry.getQueue()
                        .getBranch()
                        .getId();

        businessAuthorizationService
                .requireBranchAccess(
                        staffUserId,
                        businessId,
                        branchId
                );

        OffsetDateTime now =
                OffsetDateTime.now();

        if (credential.isRevoked()) {
            throw new QrCredentialInactiveException(
                    "QR credential has been revoked"
            );
        }

        if (credential.isExpired(now)) {
            throw new QrCredentialInactiveException(
                    "QR credential has expired"
            );
        }

        requireLiveTicket(entry);

        String ticketNumber =
                entry.getQueue()
                        .getTicketPrefix()
                        + String.format(
                                "%03d",
                                entry.getTicketSequence()
                        );

        return new QueueEntryQrVerificationResponse(
                entry.getId(),
                entry.getQueue().getId(),
                businessId,
                branchId,
                entry.getQueue()
                        .getBranch()
                        .getName(),
                entry.getService().getId(),
                entry.getService().getName(),
                ticketNumber,
                entry.getStatus()
        );
    }

    private QueueEntry requireEntry(
            Long queueId,
            Long entryId
    ) {

        QueueEntry entry =
                queueEntryRepository
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

        return entry;
    }

    private void requireCustomerOwnership(
            QueueEntry entry,
            Long userId,
            String guestToken
    ) {

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

        if (!registeredOwner
                && !guestOwner) {

            throw new AccessDeniedException(
                    "You cannot access this queue entry"
            );
        }
    }

    private void requireLiveTicket(
            QueueEntry entry
    ) {

        if (!LIVE_STATUSES.contains(
                entry.getStatus()
        )) {

            throw new IllegalStateException(
                    "QR verification is unavailable for terminal tickets"
            );
        }
    }
}
