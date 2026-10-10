package com.queueflow.api.entity;

import jakarta.persistence.*;
import java.time.OffsetDateTime;

@Entity
@Table(name = "customer_notification")
public class CustomerNotification {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "queue_entry_id", nullable = false)
    private QueueEntry queueEntry;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 40)
    private CustomerNotificationType type;

    @Column(nullable = false, length = 150)
    private String title;

    @Column(nullable = false, columnDefinition = "TEXT")
    private String message;

    @Column(name = "is_read", nullable = false)
    private boolean read = false;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "read_at")
    private OffsetDateTime readAt;

    protected CustomerNotification() {
    }

    public CustomerNotification(
            QueueEntry queueEntry,
            CustomerNotificationType type,
            String title,
            String message
    ) {
        this.queueEntry = queueEntry;
        this.type = type;
        this.title = title;
        this.message = message;
    }

    @PrePersist
    protected void onCreate() {
        if (createdAt == null) {
            createdAt = OffsetDateTime.now();
        }
    }

    public void markAsRead() {
        if (!read) {
            read = true;
            readAt = OffsetDateTime.now();
        }
    }

    public Long getId() {
        return id;
    }

    public QueueEntry getQueueEntry() {
        return queueEntry;
    }

    public CustomerNotificationType getType() {
        return type;
    }

    public String getTitle() {
        return title;
    }

    public String getMessage() {
        return message;
    }

    public boolean isRead() {
        return read;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getReadAt() {
        return readAt;
    }
}
