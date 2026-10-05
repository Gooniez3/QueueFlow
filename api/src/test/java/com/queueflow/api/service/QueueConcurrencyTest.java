package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
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
import com.queueflow.api.response.QueueStaffEntryResponse;
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
        staffMembershipRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
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
    void shouldAllowOnlyOneConcurrentCallNext()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "Concurrent Call Clinic",
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

        QueueEntry first = queueEntryRepository.save(
                new QueueEntry(
                        queue,
                        service,
                        null,
                        1,
                        "call-next-guest-1"
                )
        );

        QueueEntry second = queueEntryRepository.save(
                new QueueEntry(
                        queue,
                        service,
                        null,
                        2,
                        "call-next-guest-2"
                )
        );

        UserAccount staffUser =
                userAccountRepository.save(
                        new UserAccount(
                                "concurrent-call@example.com",
                                "test-password-hash",
                                "Concurrent",
                                "Staff",
                                null
                        )
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        staffUser,
                        business,
                        branch,
                        StaffRole.STAFF
                )
        );

        int concurrentCalls = 2;

        ExecutorService executor =
                Executors.newFixedThreadPool(concurrentCalls);

        CountDownLatch ready =
                new CountDownLatch(concurrentCalls);

        CountDownLatch start =
                new CountDownLatch(1);

        List<Future<Object>> futures =
                new ArrayList<>();

        try {
            for (int i = 0; i < concurrentCalls; i++) {

                futures.add(
                        executor.submit(() -> {

                            ready.countDown();
                            start.await();

                            try {
                                return queueService.callNext(
                                        queue.getId(),
                                        staffUser.getId(),
                                        null
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

            long successes =
                    results.stream()
                            .filter(
                                    QueueStaffEntryResponse.class::isInstance
                            )
                            .count();

            assertThat(successes)
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
                    )
                    .hasMessage(
                            "Queue already has a called entry"
                    );

            List<QueueEntry> entries =
                    queueEntryRepository
                            .findByQueueIdOrderByTicketSequenceAsc(
                                    queue.getId()
                            );

            assertThat(entries)
                    .hasSize(2);

            assertThat(
                    entries.stream()
                            .filter(entry ->
                                    entry.getStatus()
                                            == QueueEntryStatus.CALLED
                            )
                            .count()
            ).isEqualTo(1);

            assertThat(
                    entries.stream()
                            .filter(entry ->
                                    entry.getStatus()
                                            == QueueEntryStatus.WAITING
                            )
                            .count()
            ).isEqualTo(1);

            assertThat(first.getId())
                    .isNotNull();

            assertThat(second.getId())
                    .isNotNull();

        } finally {
            executor.shutdownNow();
        }
    }

    @Test
    void shouldAllowOnlyOneConcurrentStartServing()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "Concurrent Serving Clinic",
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

        QueueEntry first = new QueueEntry(
                queue,
                service,
                null,
                1,
                "serving-guest-1"
        );

        first.setStatus(
                QueueEntryStatus.CALLED
        );

        first.setCalledAt(
                OffsetDateTime.now()
        );

        first = queueEntryRepository.save(first);

        QueueEntry second = new QueueEntry(
                queue,
                service,
                null,
                2,
                "serving-guest-2"
        );

        second.setStatus(
                QueueEntryStatus.CALLED
        );

        second.setCalledAt(
                OffsetDateTime.now()
        );

        second = queueEntryRepository.save(second);

        Long firstEntryId = first.getId();
        Long secondEntryId = second.getId();

        UserAccount staffUser =
                userAccountRepository.save(
                        new UserAccount(
                                "concurrent-serving@example.com",
                                "test-password-hash",
                                "Concurrent",
                                "Staff",
                                null
                        )
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        staffUser,
                        business,
                        branch,
                        StaffRole.STAFF
                )
        );

        ExecutorService executor =
                Executors.newFixedThreadPool(2);

        CountDownLatch ready =
                new CountDownLatch(2);

        CountDownLatch start =
                new CountDownLatch(1);

        List<Future<Object>> futures =
                new ArrayList<>();

        try {
            futures.add(
                    executor.submit(() -> {

                        ready.countDown();
                        start.await();

                        try {
                            return queueService.startServing(
                                    queue.getId(),
                                    firstEntryId,
                                    staffUser.getId(),
                                    null
                            );
                        } catch (Exception exception) {
                            return exception;
                        }
                    })
            );

            futures.add(
                    executor.submit(() -> {

                        ready.countDown();
                        start.await();

                        try {
                            return queueService.startServing(
                                    queue.getId(),
                                    secondEntryId,
                                    staffUser.getId(),
                                    null
                            );
                        } catch (Exception exception) {
                            return exception;
                        }
                    })
            );

            ready.await();
            start.countDown();

            List<Object> results =
                    new ArrayList<>();

            for (Future<Object> future : futures) {
                results.add(future.get());
            }

            long successes =
                    results.stream()
                            .filter(
                                    QueueStaffEntryResponse.class::isInstance
                            )
                            .count();

            assertThat(successes)
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
                    )
                    .hasMessage(
                            "Queue already has a serving entry"
                    );

            List<QueueEntry> entries =
                    queueEntryRepository
                            .findByQueueIdOrderByTicketSequenceAsc(
                                    queue.getId()
                            );

            assertThat(
                    entries.stream()
                            .filter(entry ->
                                    entry.getStatus()
                                            == QueueEntryStatus.SERVING
                            )
                            .count()
            ).isEqualTo(1);

            assertThat(
                    entries.stream()
                            .filter(entry ->
                                    entry.getStatus()
                                            == QueueEntryStatus.CALLED
                            )
                            .count()
            ).isEqualTo(1);

        } finally {
            executor.shutdownNow();
        }
    }

}

