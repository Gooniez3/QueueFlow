package com.queueflow.api.entity;

import jakarta.persistence.*;

import java.time.OffsetDateTime;

@Entity
@Table(
        name = "guest_join_idempotency",
        uniqueConstraints = {
                @UniqueConstraint(
                        name = "uq_guest_join_idempotency_queue_key",
                        columnNames = {"queue_id", "idempotency_key"}
                )
        }
)
public class GuestJoinIdempotency {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "queue_id", nullable = false)
    private Queue queue;

    @OneToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "queue_entry_id", nullable = false)
    private QueueEntry queueEntry;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "service_id", nullable = false)
    private Service service;

    @Column(name = "idempotency_key", nullable = false, length = 36)
    private String idempotencyKey;

    @Column(name = "encrypted_guest_token", nullable = false, columnDefinition = "TEXT")
    private String encryptedGuestToken;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "expires_at", nullable = false)
    private OffsetDateTime expiresAt;

    public GuestJoinIdempotency() {
    }

    public GuestJoinIdempotency(
            Queue queue,
            QueueEntry queueEntry,
            Service service,
            String idempotencyKey,
            String encryptedGuestToken,
            OffsetDateTime expiresAt
    ) {
        this.queue = queue;
        this.queueEntry = queueEntry;
        this.service = service;
        this.idempotencyKey = idempotencyKey;
        this.encryptedGuestToken = encryptedGuestToken;
        this.expiresAt = expiresAt;
    }

    @PrePersist
    protected void onCreate() {
        if (createdAt == null) {
            createdAt = OffsetDateTime.now();
        }
    }

    public Long getId() {
        return id;
    }

    public Queue getQueue() {
        return queue;
    }

    public QueueEntry getQueueEntry() {
        return queueEntry;
    }

    public Service getService() {
        return service;
    }

    public String getIdempotencyKey() {
        return idempotencyKey;
    }

    public String getEncryptedGuestToken() {
        return encryptedGuestToken;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getExpiresAt() {
        return expiresAt;
    }
}