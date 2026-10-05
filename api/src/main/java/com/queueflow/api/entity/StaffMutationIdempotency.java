package com.queueflow.api.entity;

import jakarta.persistence.*;

import java.time.OffsetDateTime;

@Entity
@Table(
        name = "staff_mutation_idempotency",
        uniqueConstraints = {
                @UniqueConstraint(
                        name = "uq_staff_mutation_queue_staff_key",
                        columnNames = {
                                "queue_id",
                                "staff_user_id",
                                "idempotency_key"
                        }
                )
        }
)
public class StaffMutationIdempotency {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "queue_id", nullable = false)
    private Queue queue;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "staff_user_id", nullable = false)
    private UserAccount staffUser;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "queue_entry_id", nullable = false)
    private QueueEntry queueEntry;

    @Column(nullable = false, length = 30)
    private String operation;

    @Column(
            name = "idempotency_key",
            nullable = false,
            length = 36
    )
    private String idempotencyKey;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "expires_at", nullable = false)
    private OffsetDateTime expiresAt;

    public StaffMutationIdempotency() {
    }

    public StaffMutationIdempotency(
            Queue queue,
            UserAccount staffUser,
            QueueEntry queueEntry,
            String operation,
            String idempotencyKey,
            OffsetDateTime expiresAt
    ) {
        this.queue = queue;
        this.staffUser = staffUser;
        this.queueEntry = queueEntry;
        this.operation = operation;
        this.idempotencyKey = idempotencyKey;
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

    public UserAccount getStaffUser() {
        return staffUser;
    }

    public QueueEntry getQueueEntry() {
        return queueEntry;
    }

    public String getOperation() {
        return operation;
    }

    public String getIdempotencyKey() {
        return idempotencyKey;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public OffsetDateTime getExpiresAt() {
        return expiresAt;
    }
}
