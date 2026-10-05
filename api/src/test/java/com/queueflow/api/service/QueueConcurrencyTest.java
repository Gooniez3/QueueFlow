package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueStatus;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.request.CreateQueueRequest;
import com.queueflow.api.request.JoinQueueRequest;
import com.queueflow.api.response.QueueEntryResponse;
import com.queueflow.api.response.QueueResponse;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
class QueueConcurrencyTest {

    @Autowired
    private QueueService queueService;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @BeforeEach
    void cleanDatabase() {
        guestJoinIdempotencyRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldAllocateUniqueSequentialTicketsForConcurrentJoins()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "Concurrency Clinic",
                        "Concurrency test"
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
                                "General consultation",
                                30
                        )
                );

        Queue queue = queueRepository.save(
                new Queue(
                        branch,
                        service,
                        "Consultation Queue",
                        LocalDate.now(),
                        "A"
                )
        );

        int concurrentJoins = 10;

        ExecutorService executor =
                Executors.newFixedThreadPool(concurrentJoins);

        CountDownLatch ready =
                new CountDownLatch(concurrentJoins);

        CountDownLatch start =
                new CountDownLatch(1);

        List<Future<QueueEntryResponse>> futures =
                new ArrayList<>();

        try {
            for (int i = 0; i < concurrentJoins; i++) {

                futures.add(
                        executor.submit(() -> {

                            ready.countDown();

                            start.await();

                            return queueService.joinQueue(
                                    queue.getId(),
                                    null,
                                    null,
                                    new JoinQueueRequest(null)
                            );
                        })
                );
            }

            ready.await();

            start.countDown();

            List<QueueEntryResponse> responses =
                    new ArrayList<>();

            for (Future<QueueEntryResponse> future : futures) {
                responses.add(future.get());
            }

            assertThat(responses)
                    .hasSize(concurrentJoins);

            Set<Integer> sequences =
                    new HashSet<>();

            Set<String> ticketNumbers =
                    new HashSet<>();

            for (QueueEntryResponse response : responses) {
                sequences.add(
                        response.ticketSequence()
                );

                ticketNumbers.add(
                        response.ticketNumber()
                );
            }

            assertThat(sequences)
                    .containsExactlyInAnyOrder(
                            1, 2, 3, 4, 5,
                            6, 7, 8, 9, 10
                    );

            assertThat(ticketNumbers)
                    .containsExactlyInAnyOrder(
                            "A001",
                            "A002",
                            "A003",
                            "A004",
                            "A005",
                            "A006",
                            "A007",
                            "A008",
                            "A009",
                            "A010"
                    );

            List<QueueEntry> savedEntries =
                    queueEntryRepository
                            .findByQueueIdOrderByTicketSequenceAsc(
                                    queue.getId()
                            );

            assertThat(savedEntries)
                    .hasSize(concurrentJoins);

            assertThat(savedEntries)
                    .extracting(
                            QueueEntry::getTicketSequence
                    )
                    .containsExactly(
                            1, 2, 3, 4, 5,
                            6, 7, 8, 9, 10
                    );

            Queue updatedQueue =
                    queueRepository.findById(queue.getId())
                            .orElseThrow();

            assertThat(
                    updatedQueue.getNextTicketSequence()
            ).isEqualTo(11);

        } finally {
            executor.shutdownNow();
        }
    }

    @Test
    void shouldPreventDuplicateQueueCreationUnderConcurrency()
        throws Exception {

    Business business = businessRepository.save(
            new Business(
                    "Concurrency Clinic",
                    "Concurrency test"
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
                            "General consultation",
                            30
                    )
            );

    CreateQueueRequest request =
            new CreateQueueRequest(
                    service.getId(),
                    "Consultation Queue",
                    "A"
            );

    int concurrentCreates = 2;

    ExecutorService executor =
            Executors.newFixedThreadPool(concurrentCreates);

    CountDownLatch ready =
            new CountDownLatch(concurrentCreates);

    CountDownLatch start =
            new CountDownLatch(1);

    List<Future<Object>> futures =
            new ArrayList<>();

    try {
        for (int i = 0; i < concurrentCreates; i++) {

            futures.add(
                    executor.submit(() -> {

                        ready.countDown();

                        start.await();

                        try {
                            return queueService.createQueue(
                                    business.getId(),
                                    branch.getId(),
                                    request
                            );
                        } catch (Exception exception) {
                            return exception;
                        }
                    })
            );
        }

        ready.await();
        start.countDown();

        List<Object> results = new ArrayList<>();

        for (Future<Object> future : futures) {
            results.add(future.get());
        }

        long successfulCreates =
                results.stream()
                        .filter(
                                QueueResponse.class::isInstance
                        )
                        .count();

        assertThat(successfulCreates)
                .isEqualTo(1);

        assertThat(queueRepository.count())
                .isEqualTo(1);

        List<Exception> failures =
                results.stream()
                        .filter(Exception.class::isInstance)
                        .map(Exception.class::cast)
                        .toList();

        assertThat(failures)
                .hasSize(1);

        assertThat(failures.getFirst())
                .isInstanceOf(
                        IllegalStateException.class
                );

        assertThat(failures.getFirst())
                .hasMessage(
                        "Queue already exists for this service today"
                );

    } finally {
        executor.shutdownNow();
    }
 }
  @Test
void shouldReplaySameGuestJoinUnderConcurrency()
        throws Exception {

    Business business = businessRepository.save(
            new Business(
                    "Concurrency Clinic",
                    "Concurrency test"
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
                            "General consultation",
                            30
                    )
            );

    Queue queue = queueRepository.save(
            new Queue(
                    branch,
                    service,
                    "Consultation Queue",
                    LocalDate.now(),
                    "A"
            )
    );

    String idempotencyKey =
            "66666666-6666-4666-8666-666666666666";

    int concurrentJoins = 2;

    ExecutorService executor =
            Executors.newFixedThreadPool(concurrentJoins);

    CountDownLatch ready =
            new CountDownLatch(concurrentJoins);

    CountDownLatch start =
            new CountDownLatch(1);

    List<Future<QueueEntryResponse>> futures =
            new ArrayList<>();

    try {
        for (int i = 0; i < concurrentJoins; i++) {

            futures.add(
                    executor.submit(() -> {

                        ready.countDown();

                        start.await();

                        return queueService.joinQueue(
                                queue.getId(),
                                null,
                                idempotencyKey,
                                new JoinQueueRequest(null)
                        );
                    })
            );
        }

        ready.await();
        start.countDown();

        List<QueueEntryResponse> responses =
                new ArrayList<>();

        for (Future<QueueEntryResponse> future : futures) {
            responses.add(future.get());
        }

        assertThat(responses)
                .hasSize(2);

        assertThat(responses)
                .extracting(
                        QueueEntryResponse::ticketSequence
                )
                .containsOnly(1);

        assertThat(responses)
                .extracting(
                        QueueEntryResponse::ticketNumber
                )
                .containsOnly("A001");

        assertThat(responses)
                .extracting(
                        QueueEntryResponse::guestToken
                )
                .allMatch(token -> token != null
                        && !token.isBlank());

        assertThat(responses.get(0).guestToken())
                .isEqualTo(
                        responses.get(1).guestToken()
                );

        assertThat(queueEntryRepository.count())
                .isEqualTo(1);

        assertThat(
                guestJoinIdempotencyRepository.count()
        ).isEqualTo(1);

        Queue updatedQueue =
                queueRepository
                        .findById(queue.getId())
                        .orElseThrow();

        assertThat(
                updatedQueue.getNextTicketSequence()
        ).isEqualTo(2);

    } finally {
        executor.shutdownNow();
    }
  }

    @Test
    void shouldAllowOnlyOneConcurrentReopen()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "Concurrency Clinic",
                                "Concurrency test"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Main Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        branch.setTimezone("Asia/Singapore");
        branch = branchRepository.save(branch);

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Consultation",
                                "General consultation",
                                30
                        )
                );

        Queue queue =
                new Queue(
                        branch,
                        service,
                        "Consultation Queue",
                        LocalDate.now(
                                java.time.ZoneId.of(
                                        branch.getTimezone()
                                )
                        ),
                        "A"
                );

        queue.setStatus(QueueStatus.CLOSED);
        queue.setClosedAt(OffsetDateTime.now());

        queue = queueRepository.save(queue);

        UserAccount user =
                userAccountRepository.save(
                        new UserAccount(
                                "reopen-concurrency@example.com",
                                "unused-password-hash",
                                "Queue",
                                "Staff",
                                null
                        )
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        user,
                        business,
                        branch,
                        StaffRole.STAFF
                )
        );

        Long queueId = queue.getId();
        Long staffUserId = user.getId();

        int concurrentReopens = 2;

        ExecutorService executor =
                Executors.newFixedThreadPool(
                        concurrentReopens
                );

        CountDownLatch ready =
                new CountDownLatch(
                        concurrentReopens
                );

        CountDownLatch start =
                new CountDownLatch(1);

        List<Future<Object>> futures =
                new ArrayList<>();

        try {
            for (int i = 0;
                 i < concurrentReopens;
                 i++) {

                futures.add(
                        executor.submit(() -> {

                            ready.countDown();
                            start.await();

                            try {
                                return queueService.reopenQueue(
                                        queueId,
                                        staffUserId
                                );
                            } catch (Exception exception) {
                                return exception;
                            }
                        })
                );
            }

            ready.await();
            start.countDown();

            List<Object> results =
                    new ArrayList<>();

            for (Future<Object> future : futures) {
                results.add(future.get());
            }

            long successfulReopens =
                    results.stream()
                            .filter(
                                    QueueResponse.class::isInstance
                            )
                            .count();

            assertThat(successfulReopens)
                    .isEqualTo(1);

            List<Exception> failures =
                    results.stream()
                            .filter(
                                    Exception.class::isInstance
                            )
                            .map(
                                    Exception.class::cast
                            )
                            .toList();

            assertThat(failures)
                    .hasSize(1);

            assertThat(failures.getFirst())
                    .isInstanceOf(
                            IllegalStateException.class
                    );

            assertThat(failures.getFirst())
                    .hasMessage(
                            "Only a closed queue can be reopened"
                    );

            Queue reopened =
                    queueRepository
                            .findById(queueId)
                            .orElseThrow();

            assertThat(reopened.getStatus())
                    .isEqualTo(QueueStatus.OPEN);

            assertThat(reopened.getClosedAt())
                    .isNull();

            assertThat(queueRepository.count())
                    .isEqualTo(1);

        } finally {
            executor.shutdownNow();
        }
    }
}

