package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.request.JoinQueueRequest;
import com.queueflow.api.response.QueueEntryResponse;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;

import java.time.LocalDate;
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
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @BeforeEach
    void cleanDatabase() {
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
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
}