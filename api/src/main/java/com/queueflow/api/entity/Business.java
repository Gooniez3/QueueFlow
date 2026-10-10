package com.queueflow.api.entity;

import jakarta.persistence.*;
import java.time.OffsetDateTime;
import java.util.UUID;

@Entity
@Table(name = "business")
public class Business {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, length = 150)
    private String name;

    @Column(name = "public_code", nullable = false, unique = true, length = 36, updatable = false)
    private String publicCode;

    @Column(columnDefinition = "TEXT")
    private String description;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 100)
    private BusinessCategory category = BusinessCategory.OTHER;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt;

    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt;

    public Business() {
    }

    public Business(
            String name,
            String description
    ) {
        this.name = name;
        this.description = description;
        this.category = BusinessCategory.OTHER;
    }

    @PrePersist
    protected void onCreate() {
        OffsetDateTime now = OffsetDateTime.now();
        if (publicCode == null) {
            publicCode = "biz_" + UUID.randomUUID().toString().replace("-", "");
        }

        createdAt = now;
        updatedAt = now;

        if (category == null) {
            category = BusinessCategory.OTHER;
        }
    }

    @PreUpdate
    protected void onUpdate() {
        updatedAt = OffsetDateTime.now();
    }

    public String getPublicCode() {
        return publicCode;
    }

    public Long getId() {
        return id;
    }

    public String getName() {
        return name;
    }

    public void setName(
            String name
    ) {
        this.name = name;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(
            String description
    ) {
        this.description = description;
    }

    public BusinessCategory getCategory() {
        return category;
    }

    public void setCategory(
            BusinessCategory category
    ) {
        this.category = category;
    }

    public OffsetDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(
            OffsetDateTime createdAt
    ) {
        this.createdAt = createdAt;
    }

    public OffsetDateTime getUpdatedAt() {
        return updatedAt;
    }

    public void setUpdatedAt(
            OffsetDateTime updatedAt
    ) {
        this.updatedAt = updatedAt;
    }
}
