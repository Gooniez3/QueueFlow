package com.queueflow.api.entity;

import jakarta.persistence.*;

import java.time.OffsetDateTime;

@Entity
@Table(
        name = "queue_entry_qr_credential",
        uniqueConstraints = {
                @UniqueConstraint(
                        name = "uq_qr_credential_hash",
                        columnNames = "credential_hash"
                )
        }
)
public class QueueEntryQrCredential {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(
            fetch = FetchType.LAZY,
            optional = false
    )
    @JoinColumn(
            name = "queue_entry_id",
            nullable = false
    )
    private QueueEntry queueEntry;

    @Column(
            name = "credential_hash",
            nullable = false,
            length = 64
    )
    private String credentialHash;

    @Column(name = "encrypted_credential", columnDefinition = "TEXT")
    private String encryptedCredential;

    @Column(
            name = "expires_at",
            nullable = false
    )
    private OffsetDateTime expiresAt;

    @Column(name = "revoked_at")
    private OffsetDateTime revokedAt;

    @Column(
            name = "created_at",
            nullable = false
    )
    private OffsetDateTime createdAt;

    public QueueEntryQrCredential() {
    }

    public QueueEntryQrCredential(
            QueueEntry queueEntry,
            String credentialHash,
            OffsetDateTime expiresAt
    ) {
        this.queueEntry = queueEntry;
        this.credentialHash = credentialHash;
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

    public QueueEntry getQueueEntry() {
        return queueEntry;
    }

    public void setQueueEntry(
            QueueEntry queueEntry
    ) {
        this.queueEntry = queueEntry;
    }

    public String getCredentialHash() {
        return credentialHash;
    }

    public void setCredentialHash(
            String credentialHash
    ) {
        this.credentialHash = credentialHash;
    }

    public OffsetDateTime getExpiresAt() {
        return expiresAt;
    }

    public void setExpiresAt(
            OffsetDateTime expiresAt
    ) {
        this.expiresAt = expiresAt;
    }

    public OffsetDateTime getRevokedAt() {
        return revokedAt;
    }

    public void setRevokedAt(
            OffsetDateTime revokedAt
    ) {
        this.revokedAt = revokedAt;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(
            OffsetDateTime createdAt
    ) {
        this.createdAt = createdAt;
    }

    public boolean isRevoked() {
        return revokedAt != null;
    }

    public boolean isExpired(
            OffsetDateTime now
    ) {
        return !expiresAt.isAfter(now);
    }

    public String getEncryptedCredential() {
        return encryptedCredential;
    }

    public void setEncryptedCredential(String encryptedCredential) {
        this.encryptedCredential = encryptedCredential;
    }
}
