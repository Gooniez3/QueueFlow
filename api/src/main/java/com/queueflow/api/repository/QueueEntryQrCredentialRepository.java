package com.queueflow.api.repository;

import com.queueflow.api.entity.QueueEntryQrCredential;
import org.springframework.data.jpa.repository.JpaRepository;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;
import java.util.Optional;

public interface QueueEntryQrCredentialRepository
        extends JpaRepository<QueueEntryQrCredential, Long> {

    Optional<QueueEntryQrCredential> findByCredentialHash(
            String credentialHash
    );

    boolean existsByCredentialHash(
            String credentialHash
    );

    List<QueueEntryQrCredential> findByQueueEntryIdAndRevokedAtIsNull(
            Long queueEntryId
    );

    @Lock(LockModeType.PESSIMISTIC_WRITE)
  @Query("""
        SELECT c
        FROM QueueEntryQrCredential c
        WHERE c.credentialHash = :credentialHash
        """)
Optional<QueueEntryQrCredential> findByCredentialHashForUpdate(
        @Param("credentialHash") String credentialHash
  );
}
