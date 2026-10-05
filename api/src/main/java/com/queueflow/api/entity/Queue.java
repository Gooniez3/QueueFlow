package com.queueflow.api.entity;

import jakarta.persistence.*;
import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.util.UUID;

@Entity
@Table(name = "queue")
public class Queue {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "branch_id", nullable = false)
    private Branch branch;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "service_id")
    private Service service;

    @Column(nullable = false, length = 150)
    private String name;

    @Column(name = "business_date", nullable = false)
    private LocalDate businessDate;

    @Column(name = "ticket_prefix", nullable = false, length = 10)
    private String ticketPrefix;

    @Column(name = "next_ticket_sequence", nullable = false)
    private Integer nextTicketSequence = 1;

    @Column(
            name = "public_code",
            nullable = false,
            unique = true,
            length = 36
    )
    private String publicCode;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 20)
    private QueueStatus status;

    @Column(name = "opened_at")
    private OffsetDateTime openedAt;

    @Column(name = "closed_at")
    private OffsetDateTime closedAt;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    public Queue() {
    }

    public Queue(
            Branch branch,
            Service service,
            String name,
            LocalDate businessDate,
            String ticketPrefix
    ) {
        this.branch = branch;
        this.service = service;
        this.name = name;
        this.businessDate = businessDate;
        this.ticketPrefix = ticketPrefix;
        this.nextTicketSequence = 1;
        this.status = QueueStatus.OPEN;
    }

    @PrePersist
    protected void onCreate() {
        OffsetDateTime now = OffsetDateTime.now();

        createdAt = now;
        updatedAt = now;

        if (publicCode == null || publicCode.isBlank()) {
            publicCode = UUID.randomUUID().toString();
        }

        if (openedAt == null) {
            openedAt = now;
        }
    }

    @PreUpdate
    protected void onUpdate() {
        updatedAt = OffsetDateTime.now();
    }

    public Long getId() {
        return id;
    }

    public Branch getBranch() {
        return branch;
    }

    public void setBranch(Branch branch) {
        this.branch = branch;
    }

    public Service getService() {
        return service;
    }

    public void setService(Service service) {
        this.service = service;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public LocalDate getBusinessDate() {
        return businessDate;
    }

    public void setBusinessDate(LocalDate businessDate) {
        this.businessDate = businessDate;
    }

    public String getTicketPrefix() {
        return ticketPrefix;
    }

    public void setTicketPrefix(String ticketPrefix) {
        this.ticketPrefix = ticketPrefix;
    }

    public Integer getNextTicketSequence() {
        return nextTicketSequence;
    }

    public void setNextTicketSequence(Integer nextTicketSequence) {
        this.nextTicketSequence = nextTicketSequence;
    }

    public String getPublicCode() {
        return publicCode;
    }

    public void setPublicCode(String publicCode) {
        this.publicCode = publicCode;
    }

    public QueueStatus getStatus() {
        return status;
    }

    public void setStatus(QueueStatus status) {
        this.status = status;
    }

    public OffsetDateTime getOpenedAt() {
        return openedAt;
    }

    public void setOpenedAt(OffsetDateTime openedAt) {
        this.openedAt = openedAt;
    }

    public OffsetDateTime getClosedAt() {
        return closedAt;
    }

    public void setClosedAt(OffsetDateTime closedAt) {
        this.closedAt = closedAt;
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
}