package com.queueflow.api.entity;

import jakarta.persistence.*;

import java.time.OffsetDateTime;
import java.util.UUID;

@Entity
@Table(
        name = "pre_queue_reservation",
        uniqueConstraints = {
                @UniqueConstraint(
                        name = "uq_pre_queue_reservation_code",
                        columnNames = {"reservation_code"}
                ),
                @UniqueConstraint(
                        name = "uq_pre_queue_reservation_queue_entry",
                        columnNames = {"queue_entry_id"}
                )
        }
)
public class PreQueueReservation {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "service_session_id", nullable = false)
    private ServiceSession serviceSession;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "user_id")
    private UserAccount user;

    @OneToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "queue_entry_id")
    private QueueEntry queueEntry;

    @Column(
            name = "reservation_code",
            nullable = false,
            unique = true,
            length = 36
    )
    private String reservationCode;

    @Column(name = "guest_token_hash", length = 255)
    private String guestTokenHash;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 20)
    private PreQueueReservationStatus status;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    @Column(name = "cancelled_at")
    private OffsetDateTime cancelledAt;

    @Column(name = "checked_in_at")
    private OffsetDateTime checkedInAt;

    public PreQueueReservation() {
    }

    public PreQueueReservation(
            ServiceSession serviceSession,
            UserAccount user,
            String guestTokenHash
    ) {
        this.serviceSession = serviceSession;
        this.user = user;
        this.guestTokenHash = guestTokenHash;
        this.status = PreQueueReservationStatus.RESERVED;
    }

    @PrePersist
    protected void onCreate() {
        OffsetDateTime now = OffsetDateTime.now();

        if (reservationCode == null || reservationCode.isBlank()) {
            reservationCode = UUID.randomUUID().toString();
        }

        if (status == null) {
            status = PreQueueReservationStatus.RESERVED;
        }

        createdAt = now;
        updatedAt = now;
    }

    @PreUpdate
    protected void onUpdate() {
        updatedAt = OffsetDateTime.now();
    }

    public Long getId() {
        return id;
    }

    public ServiceSession getServiceSession() {
        return serviceSession;
    }

    public void setServiceSession(ServiceSession serviceSession) {
        this.serviceSession = serviceSession;
    }

    public UserAccount getUser() {
        return user;
    }

    public void setUser(UserAccount user) {
        this.user = user;
    }

    public QueueEntry getQueueEntry() {
        return queueEntry;
    }

    public void setQueueEntry(QueueEntry queueEntry) {
        this.queueEntry = queueEntry;
    }

    public String getReservationCode() {
        return reservationCode;
    }

    public void setReservationCode(String reservationCode) {
        this.reservationCode = reservationCode;
    }

    public String getGuestTokenHash() {
        return guestTokenHash;
    }

    public void setGuestTokenHash(String guestTokenHash) {
        this.guestTokenHash = guestTokenHash;
    }

    public PreQueueReservationStatus getStatus() {
        return status;
    }

    public void setStatus(PreQueueReservationStatus status) {
        this.status = status;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(OffsetDateTime createdAt) {
        this.createdAt = createdAt;
    }

    public OffsetDateTime getUpdatedAt() {
        return updatedAt;
    }

    public void setUpdatedAt(OffsetDateTime updatedAt) {
        this.updatedAt = updatedAt;
    }

    public OffsetDateTime getCancelledAt() {
        return cancelledAt;
    }

    public void setCancelledAt(OffsetDateTime cancelledAt) {
        this.cancelledAt = cancelledAt;
    }

    public OffsetDateTime getCheckedInAt() {
        return checkedInAt;
    }

    public void setCheckedInAt(OffsetDateTime checkedInAt) {
        this.checkedInAt = checkedInAt;
    }
}