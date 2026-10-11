package com.queueflow.api.service;

import com.queueflow.api.entity.CustomerNotification;
import com.queueflow.api.entity.CustomerNotificationType;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.response.CustomerNotificationResponse;
import com.queueflow.api.security.AuthTokenService;
import org.springframework.security.access.AccessDeniedException;
import java.util.List;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.realtime.QueueRealtimeEvent;
import com.queueflow.api.realtime.QueueRealtimeEventType;
import com.queueflow.api.repository.CustomerNotificationRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

@Service
public class CustomerNotificationService {

    private final CustomerNotificationRepository notificationRepository;
    private final QueueEntryRepository queueEntryRepository;
    private final AuthTokenService authTokenService;

    public CustomerNotificationService(
            CustomerNotificationRepository notificationRepository,
            QueueEntryRepository queueEntryRepository,
            AuthTokenService authTokenService
    ) {
        this.notificationRepository = notificationRepository;
        this.queueEntryRepository = queueEntryRepository;
        this.authTokenService = authTokenService;
    }

    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public void createForQueueEvent(QueueRealtimeEvent event) {

        if (event.entryId() == null) {
            return;
        }

        CustomerNotificationType notificationType;
        String title;
        String message;

        if (event.type() == QueueRealtimeEventType.CALL_NEXT) {
            notificationType = CustomerNotificationType.TICKET_CALLED;
            title = "Your ticket has been called";
            message = "Please proceed to the service counter.";
        } else if (event.type() == QueueRealtimeEventType.RECALL) {
            notificationType = CustomerNotificationType.TICKET_RECALLED;
            title = "Your ticket has been recalled";
            message = "Please proceed to the service counter.";
        } else {
            return;
        }

        QueueEntry entry = queueEntryRepository
                .findById(event.entryId())
                .orElseThrow(() ->
                        new IllegalStateException(
                                "Queue entry not found: " + event.entryId()
                        )
                );

        if (!entry.getQueue().getId().equals(event.queueId())) {
            throw new IllegalStateException(
                    "Queue entry does not belong to event queue"
            );
        }

        CustomerNotification notification =
                new CustomerNotification(
                        entry,
                        notificationType,
                        title,
                        message
                );

        notificationRepository.save(notification);
    }

    @Transactional(readOnly = true)
    public List<CustomerNotificationResponse> getNotifications(
            Long queueId,
            Long entryId,
            Long userId,
            String guestToken
    ) {
        QueueEntry entry = requireOwnedEntry(
                queueId, entryId, userId, guestToken
        );

        return notificationRepository
                .findByQueueEntryIdOrderByCreatedAtDescIdDesc(entry.getId())
                .stream()
                .map(notification -> new CustomerNotificationResponse(
                        notification.getId(),
                        notification.getType(),
                        notification.getTitle(),
                        notification.getMessage(),
                        notification.isRead(),
                        notification.getCreatedAt(),
                        notification.getReadAt()
                ))
                .toList();
    }

    @Transactional
    public CustomerNotificationResponse markNotificationAsRead(
        Long queueId,
        Long entryId,
        Long notificationId,
        Long userId,
        String guestToken
  ) {
    QueueEntry entry = requireOwnedEntry(
            queueId,
            entryId,
            userId,
            guestToken
    );

    CustomerNotification notification = notificationRepository
            .findById(notificationId)
            .orElseThrow(() -> new ResourceNotFoundException(
                    "Notification not found with id: " + notificationId
            ));

    if (!notification.getQueueEntry().getId().equals(entry.getId())) {
        throw new ResourceNotFoundException(
                "Notification not found with id: " + notificationId
        );
    }

    notification.markAsRead();

    return new CustomerNotificationResponse(
            notification.getId(),
            notification.getType(),
            notification.getTitle(),
            notification.getMessage(),
            notification.isRead(),
            notification.getCreatedAt(),
            notification.getReadAt()
    );
  }

    private QueueEntry requireOwnedEntry(
            Long queueId,
            Long entryId,
            Long userId,
            String guestToken
    ) {
        QueueEntry entry = queueEntryRepository.findById(entryId)
                .orElseThrow(() -> new ResourceNotFoundException(
                        "Queue entry not found with id: " + entryId
                ));

        if (!entry.getQueue().getId().equals(queueId)) {
            throw new ResourceNotFoundException(
                    "Queue entry not found with id: " + entryId
            );
        }

        boolean registeredOwner =
                entry.getUser() != null
                && userId != null
                && entry.getUser().getId().equals(userId);

        boolean guestOwner =
                entry.getUser() == null
                && guestToken != null
                && !guestToken.isBlank()
                && entry.getGuestTokenHash() != null
                && entry.getGuestTokenHash().equals(
                        authTokenService.hashToken(guestToken)
                );

        if (!registeredOwner && !guestOwner) {
            throw new AccessDeniedException(
                    "You cannot access this queue entry"
            );
        }

        return entry;
    }
}