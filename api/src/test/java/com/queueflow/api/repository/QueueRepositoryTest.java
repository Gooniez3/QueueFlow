package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueStatus;
import com.queueflow.api.entity.Service;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDate;

import static org.assertj.core.api.Assertions.assertThat;
import static org.assertj.core.api.Assertions.assertThatThrownBy;

@SpringBootTest
@Transactional
class QueueRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Test
    void shouldSaveServiceSpecificQueue() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "QueueFlow Test Business",
                        "Business for queue persistence test"
                )
        );

        Branch branch = branchRepository.saveAndFlush(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Test Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        Service service = serviceRepository.saveAndFlush(
                new Service(
                        branch,
                        "General Consultation",
                        "General customer service",
                        30
                )
        );

        Queue queue = new Queue(
                branch,
                service,
                "General Consultation Queue",
                LocalDate.of(2026, 9, 30),
                "A"
        );

        Queue savedQueue = queueRepository.saveAndFlush(queue);

        assertThat(savedQueue.getId()).isNotNull();
        assertThat(savedQueue.getStatus()).isEqualTo(QueueStatus.OPEN);
        assertThat(savedQueue.getNextTicketSequence()).isEqualTo(1);
        assertThat(savedQueue.getOpenedAt()).isNotNull();

        Queue foundQueue = queueRepository
                .findById(savedQueue.getId())
                .orElseThrow();

        assertThat(foundQueue.getBranch().getId())
                .isEqualTo(branch.getId());

        assertThat(foundQueue.getService()).isNotNull();

        assertThat(foundQueue.getService().getId())
                .isEqualTo(service.getId());

        assertThat(foundQueue.getTicketPrefix())
                .isEqualTo("A");
    }

    @Test
    void shouldSaveSharedBranchQueueWithoutService() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "QueueFlow Shared Queue Business",
                        "Business for shared queue persistence test"
                )
        );

        Branch branch = branchRepository.saveAndFlush(
                new Branch(
                        business,
                        "Main Branch",
                        "456 Test Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        Queue queue = new Queue(
                branch,
                null,
                "Main Branch Queue",
                LocalDate.of(2026, 10, 1),
                "B"
        );

        Queue savedQueue = queueRepository.saveAndFlush(queue);

        assertThat(savedQueue.getId()).isNotNull();
        assertThat(savedQueue.getService()).isNull();
        assertThat(savedQueue.getStatus()).isEqualTo(QueueStatus.OPEN);

        Queue foundQueue = queueRepository
                .findById(savedQueue.getId())
                .orElseThrow();

        assertThat(foundQueue.getBranch().getId())
                .isEqualTo(branch.getId());

        assertThat(foundQueue.getService()).isNull();

        assertThat(foundQueue.getTicketPrefix())
                .isEqualTo("B");
    }

    @Test
    void shouldRejectDuplicateSharedQueueForSameBranchAndBusinessDate() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "Duplicate Shared Queue Business",
                        "Business for shared queue uniqueness test"
                )
        );

        Branch branch = branchRepository.saveAndFlush(
                new Branch(
                        business,
                        "Shared Queue Branch",
                        "789 Test Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        LocalDate businessDate = LocalDate.of(2026, 10, 2);

        Queue firstQueue = new Queue(
                branch,
                null,
                "First Shared Queue",
                businessDate,
                "A"
        );

        queueRepository.saveAndFlush(firstQueue);

        Queue duplicateQueue = new Queue(
                branch,
                null,
                "Duplicate Shared Queue",
                businessDate,
                "B"
        );

        assertThatThrownBy(() ->
                queueRepository.saveAndFlush(duplicateQueue)
        ).isInstanceOf(DataIntegrityViolationException.class);
    }

    @Test
    void shouldRejectDuplicateServiceQueueForSameServiceAndBusinessDate() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "Duplicate Service Queue Business",
                        "Business for service queue uniqueness test"
                )
        );

        Branch branch = branchRepository.saveAndFlush(
                new Branch(
                        business,
                        "Service Queue Branch",
                        "987 Test Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        Service service = serviceRepository.saveAndFlush(
                new Service(
                        branch,
                        "Account Services",
                        "Service for queue uniqueness test",
                        20
                )
        );

        LocalDate businessDate = LocalDate.of(2026, 10, 3);

        Queue firstQueue = new Queue(
                branch,
                service,
                "First Service Queue",
                businessDate,
                "C"
        );

        queueRepository.saveAndFlush(firstQueue);

        Queue duplicateQueue = new Queue(
                branch,
                service,
                "Duplicate Service Queue",
                businessDate,
                "D"
        );

        assertThatThrownBy(() ->
                queueRepository.saveAndFlush(duplicateQueue)
        ).isInstanceOf(DataIntegrityViolationException.class);
    }
}