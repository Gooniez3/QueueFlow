package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.response.QueuePositionResponse;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.security.AuthTokenService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;

import java.time.LocalDate;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
class QueuePositionTest {

    @Autowired
    private QueueService queueService;

    @Autowired
    private AuthTokenService authTokenService;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @BeforeEach
    void cleanDatabase() {
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        serviceRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
    }

    @Test
    void shouldCalculatePeopleAheadAndEstimatedWaitForSharedQueue() {

        Business business = businessRepository.save(
                new Business(
                        "Position Clinic",
                        "Queue position test"
                )
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business,
                        "Main Branch",
                        "123 Main Street",
                        null,
                        null
                )
        );

        com.queueflow.api.entity.Service shortService =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Quick Consultation",
                                "20 minute consultation",
                                20
                        )
                );

        com.queueflow.api.entity.Service longService =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Full Consultation",
                                "30 minute consultation",
                                30
                        )
                );

        Queue queue = queueRepository.save(
                new Queue(
                        branch,
                        null,
                        "Shared Queue",
                        LocalDate.now(),
                        "A"
                )
        );

        QueueEntry first = new QueueEntry(
                queue,
                shortService,
                null,
                1,
                "guest-hash-1"
        );

        first.setStatus(QueueEntryStatus.SERVING);

        queueEntryRepository.save(first);

        QueueEntry second = new QueueEntry(
                queue,
                longService,
                null,
                2,
                "guest-hash-2"
        );

        second.setStatus(QueueEntryStatus.CALLED);

        queueEntryRepository.save(second);

        QueueEntry third = new QueueEntry(
                queue,
                shortService,
                null,
                3,
                "guest-hash-3"
        );

        third.setStatus(QueueEntryStatus.WAITING);

        queueEntryRepository.save(third);

        QueueEntry target = new QueueEntry(
                queue,
                longService,
                null,
                4,
                authTokenService.hashToken("target-token")
        );

        target.setStatus(QueueEntryStatus.WAITING);

        target = queueEntryRepository.save(target);

        QueuePositionResponse response =
                queueService.getQueuePosition(
                        queue.getId(),
                        target.getId(),
                        null,
                        "target-token"
                );

        assertThat(response.entryId())
                .isEqualTo(target.getId());

        assertThat(response.queueId())
                .isEqualTo(queue.getId());

        assertThat(response.publicCode())
                .isEqualTo(queue.getPublicCode());

        assertThat(response.serviceId())
                .isEqualTo(longService.getId());

        assertThat(response.ticketSequence())
                .isEqualTo(4);

        assertThat(response.ticketNumber())
                .isEqualTo("A004");

        assertThat(response.status())
                .isEqualTo(QueueEntryStatus.WAITING);

        assertThat(response.peopleAhead())
                .isEqualTo(3);

        assertThat(response.estimatedWaitMinutes())
                .isEqualTo(70);
    }

    @Test
    void shouldIgnoreInactiveEntriesWhenCalculatingPosition() {

        Business business = businessRepository.save(
                new Business(
                        "Position Clinic",
                        "Inactive entry test"
                )
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business,
                        "Main Branch",
                        "123 Main Street",
                        null,
                        null
                )
        );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Consultation",
                                "30 minute consultation",
                                30
                        )
                );

        Queue queue = queueRepository.save(
                new Queue(
                        branch,
                        service,
                        "Consultation Queue",
                        LocalDate.now(),
                        "B"
                )
        );

        QueueEntry completed = new QueueEntry(
                queue,
                service,
                null,
                1,
                "guest-hash-1"
        );

        completed.setStatus(
                QueueEntryStatus.COMPLETED
        );

        queueEntryRepository.save(completed);

        QueueEntry cancelled = new QueueEntry(
                queue,
                service,
                null,
                2,
                "guest-hash-2"
        );

        cancelled.setStatus(
                QueueEntryStatus.CANCELLED
        );

        queueEntryRepository.save(cancelled);

        QueueEntry skipped = new QueueEntry(
                queue,
                service,
                null,
                3,
                "guest-hash-3"
        );

        skipped.setStatus(
                QueueEntryStatus.SKIPPED
        );

        queueEntryRepository.save(skipped);

        QueueEntry waiting = new QueueEntry(
                queue,
                service,
                null,
                4,
                "guest-hash-4"
        );

        waiting.setStatus(
                QueueEntryStatus.WAITING
        );

        queueEntryRepository.save(waiting);

        QueueEntry target = new QueueEntry(
                queue,
                service,
                null,
                5,
                authTokenService.hashToken("target-token-2")
        );

        target.setStatus(
                QueueEntryStatus.WAITING
        );

        target = queueEntryRepository.save(target);

        QueuePositionResponse response =
                queueService.getQueuePosition(
                        queue.getId(),
                        target.getId(),
                        null,
                        "target-token-2"
                );

        assertThat(response.peopleAhead())
                .isEqualTo(1);

        assertThat(response.estimatedWaitMinutes())
                .isEqualTo(30);

        assertThat(response.ticketNumber())
                .isEqualTo("B005");
    }

    @Test
    void shouldReturnZeroPositionForFirstWaitingEntry() {

        Business business = businessRepository.save(
                new Business(
                        "Position Clinic",
                        "First entry test"
                )
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business,
                        "Main Branch",
                        "123 Main Street",
                        null,
                        null
                )
        );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Consultation",
                                "25 minute consultation",
                                25
                        )
                );

        Queue queue = queueRepository.save(
                new Queue(
                        branch,
                        service,
                        "Consultation Queue",
                        LocalDate.now(),
                        "C"
                )
        );

        QueueEntry target = new QueueEntry(
                queue,
                service,
                null,
                1,
                authTokenService.hashToken("target-token-3")
        );

        target.setStatus(
                QueueEntryStatus.WAITING
        );

        target = queueEntryRepository.save(target);

        QueuePositionResponse response =
                queueService.getQueuePosition(
                        queue.getId(),
                        target.getId(),
                        null,
                        "target-token-3"
                );

        assertThat(response.peopleAhead())
                .isZero();

        assertThat(response.estimatedWaitMinutes())
                .isZero();

        assertThat(response.ticketNumber())
                .isEqualTo("C001");
    }
}
