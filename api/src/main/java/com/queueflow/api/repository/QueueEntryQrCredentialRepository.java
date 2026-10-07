package com.queueflow.api.repository;

import com.queueflow.api.entity.QueueEntryQrCredential;
import org.springframework.data.jpa.repository.JpaRepository;

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
}
