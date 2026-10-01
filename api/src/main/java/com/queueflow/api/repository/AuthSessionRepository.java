package com.queueflow.api.repository;

import com.queueflow.api.entity.AuthSession;
import org.springframework.data.jpa.repository.EntityGraph;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface AuthSessionRepository
        extends JpaRepository<AuthSession, Long> {

    @EntityGraph(attributePaths = "user")
    Optional<AuthSession> findByTokenHashAndRevokedAtIsNull(
            String tokenHash
    );
}