package com.queueflow.api.entity;

import jakarta.persistence.*;

import java.time.LocalDate;
import java.time.LocalTime;
import java.time.OffsetDateTime;

@Entity
@Table(
        name = "service_session",
        uniqueConstraints = {
                @UniqueConstraint(
                        name = "uq_service_session_service_date_start",
                        columnNames = {
                                "service_id",
                                "local_date",
                                "start_time"
                        }
                )
        }
)
public class ServiceSession {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "service_id", nullable = false)
    private Service service;

    @Column(name = "local_date", nullable = false)
    private LocalDate localDate;

    @Column(name = "start_time", nullable = false)
    private LocalTime startTime;

    @Column(name = "end_time", nullable = false)
    private LocalTime endTime;

    @Column(nullable = false)
    private Integer capacity;

    @Column(name = "booking_open", nullable = false)
    private boolean bookingOpen = true;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    @Column(name = "check_in_start_time", nullable = false)
    private LocalTime checkInStartTime;

    @Column(name = "check_in_end_time", nullable = false)
    private LocalTime checkInEndTime;

    public ServiceSession() {
    }

    public ServiceSession(
            Service service,
            LocalDate localDate,
            LocalTime startTime,
            LocalTime endTime,
            Integer capacity
    ) {
        this.service = service;
        this.localDate = localDate;
        this.startTime = startTime;
        this.endTime = endTime;
        this.capacity = capacity;
        this.bookingOpen = true;
        this.checkInStartTime = startTime;
        this.checkInEndTime = endTime;
    }

    @PrePersist
    protected void onCreate() {
        OffsetDateTime now = OffsetDateTime.now();
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

    public Service getService() {
        return service;
    }

    public void setService(Service service) {
        this.service = service;
    }

    public LocalDate getLocalDate() {
        return localDate;
    }

    public void setLocalDate(LocalDate localDate) {
        this.localDate = localDate;
    }

    public LocalTime getStartTime() {
        return startTime;
    }

    public void setStartTime(LocalTime startTime) {
        this.startTime = startTime;
    }

    public LocalTime getEndTime() {
        return endTime;
    }

    public void setEndTime(LocalTime endTime) {
        this.endTime = endTime;
    }

    public Integer getCapacity() {
        return capacity;
    }

    public void setCapacity(Integer capacity) {
        this.capacity = capacity;
    }

    public boolean isBookingOpen() {
        return bookingOpen;
    }

    public void setBookingOpen(boolean bookingOpen) {
        this.bookingOpen = bookingOpen;
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

    public LocalTime getCheckInStartTime() {
    return checkInStartTime;
   }

   public void setCheckInStartTime(LocalTime checkInStartTime) {
       this.checkInStartTime = checkInStartTime;
  }

   public LocalTime getCheckInEndTime() {
    return checkInEndTime;
  }

  public void setCheckInEndTime(LocalTime checkInEndTime) {
    this.checkInEndTime = checkInEndTime;
  }
}