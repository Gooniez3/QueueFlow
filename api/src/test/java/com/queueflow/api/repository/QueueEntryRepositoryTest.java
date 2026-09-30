package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.entity.Service;
import com.queueflow.api.entity.UserAccount;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDate;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class QueueEntryRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Test
    void shouldSaveQueueEntryForLoggedInUser() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "QueueFlow Entry Test Business",
                        "Business for queue entry test"
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

        Queue queue = queueRepository.saveAndFlush(
                new Queue(
                        branch,
                        service,
                        "General Consultation Queue",
                        LocalDate.of(2026, 9, 30),
                        "A"
                )
        );

        UserAccount user = userAccountRepository.saveAndFlush(
                new UserAccount(
                        "customer@queueflow.com",
                        "hashed-password-example",
                        "Test",
                        "Customer",
                        "+6512345678"
                )
        );

        QueueEntry entry = new QueueEntry(
                queue,
                service,
                user,
                1,
                null
        );

        QueueEntry savedEntry = queueEntryRepository.saveAndFlush(entry);

        assertThat(savedEntry.getId()).isNotNull();
        assertThat(savedEntry.getStatus())
                .isEqualTo(QueueEntryStatus.WAITING);

        assertThat(savedEntry.getJoinedAt()).isNotNull();
        assertThat(savedEntry.getCreatedAt()).isNotNull();
        assertThat(savedEntry.getUpdatedAt()).isNotNull();

        QueueEntry foundEntry = queueEntryRepository
                .findById(savedEntry.getId())
                .orElseThrow();

        assertThat(foundEntry.getQueue().getId())
                .isEqualTo(queue.getId());

        assertThat(foundEntry.getService().getId())
                .isEqualTo(service.getId());

        assertThat(foundEntry.getUser()).isNotNull();

        assertThat(foundEntry.getUser().getId())
                .isEqualTo(user.getId());

        assertThat(foundEntry.getCounter()).isNull();

        assertThat(foundEntry.getTicketSequence())
                .isEqualTo(1);
    }
    @Test
    void shouldSaveQueueEntryForGuestUser() {

    Business business = businessRepository.saveAndFlush(
            new Business(
                    "QueueFlow Guest Test Business",
                    "Business for guest queue entry test"
            )
    );

    Branch branch = branchRepository.saveAndFlush(
            new Branch(
                    business,
                    "Guest Test Branch",
                    "456 Test Street",
                    new BigDecimal("1.352100"),
                    new BigDecimal("103.819800")
            )
    );

    Service service = serviceRepository.saveAndFlush(
            new Service(
                    branch,
                    "Guest Service",
                    "Service for guest customers",
                    20
            )
    );

    Queue queue = queueRepository.saveAndFlush(
            new Queue(
                    branch,
                    service,
                    "Guest Service Queue",
                    LocalDate.of(2026, 10, 1),
                    "G"
            )
    );

    QueueEntry entry = new QueueEntry(
            queue,
            service,
            null,
            1,
            "hashed-guest-token-example"
    );

    QueueEntry savedEntry = queueEntryRepository.saveAndFlush(entry);

    assertThat(savedEntry.getId()).isNotNull();
    assertThat(savedEntry.getUser()).isNull();
    assertThat(savedEntry.getCounter()).isNull();

    assertThat(savedEntry.getGuestTokenHash())
            .isEqualTo("hashed-guest-token-example");

    assertThat(savedEntry.getStatus())
            .isEqualTo(QueueEntryStatus.WAITING);

    assertThat(savedEntry.getTicketSequence())
            .isEqualTo(1);

    assertThat(savedEntry.getJoinedAt()).isNotNull();

    QueueEntry foundEntry = queueEntryRepository
            .findById(savedEntry.getId())
            .orElseThrow();

    assertThat(foundEntry.getUser()).isNull();

    assertThat(foundEntry.getGuestTokenHash())
            .isEqualTo("hashed-guest-token-example");

    assertThat(foundEntry.getQueue().getId())
            .isEqualTo(queue.getId());

    assertThat(foundEntry.getService().getId())
            .isEqualTo(service.getId());
    }
}