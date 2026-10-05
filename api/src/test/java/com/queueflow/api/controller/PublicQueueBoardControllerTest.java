package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.test.web.servlet.MockMvc;

import java.math.BigDecimal;
import java.time.LocalDate;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class PublicQueueBoardControllerTest {

    @Autowired
    private MockMvc mockMvc;

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

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldReturnPublicQueueBoardWithoutAuthentication()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                new BigDecimal("1.352100"),
                                new BigDecimal("103.819800")
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        Queue queue =
                queueRepository.save(
                        new Queue(
                                branch,
                                service,
                                "Consultation Queue",
                                LocalDate.now(),
                                "A"
                        )
                );

        QueueEntry serving =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        1,
                        "hash-1"
                );
        serving.setStatus(
                QueueEntryStatus.SERVING
        );

        QueueEntry called =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        2,
                        "hash-2"
                );
        called.setStatus(
                QueueEntryStatus.CALLED
        );

        QueueEntry waitingOne =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        3,
                        "hash-3"
                );

        QueueEntry waitingTwo =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        4,
                        "hash-4"
                );

        queueEntryRepository.save(serving);
        queueEntryRepository.save(called);
        queueEntryRepository.save(waitingOne);
        queueEntryRepository.save(waitingTwo);

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/board",
                                queue.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Consultation Queue"))
                .andExpect(jsonPath("$.status")
                        .value("OPEN"))
                .andExpect(jsonPath("$.nowServing")
                        .value("A001"))
                .andExpect(jsonPath("$.calling")
                        .value("A002"))
                .andExpect(jsonPath("$.waitingCount")
                        .value(2))
                .andExpect(jsonPath("$.upcomingTicketNumbers[0]")
                        .value("A003"))
                .andExpect(jsonPath("$.upcomingTicketNumbers[1]")
                        .value("A004"))
                .andExpect(jsonPath("$.userId")
                        .doesNotExist())
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist());
    }

    @Test
    void shouldLimitUpcomingTicketsToFive()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        Queue queue =
                queueRepository.save(
                        new Queue(
                                branch,
                                service,
                                "Consultation Queue",
                                LocalDate.now(),
                                "Q"
                        )
                );

        for (int i = 1; i <= 7; i++) {
            queueEntryRepository.save(
                    new QueueEntry(
                            queue,
                            service,
                            null,
                            i,
                            "hash-" + i
                    )
            );
        }

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/board",
                                queue.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.waitingCount")
                        .value(7))
                .andExpect(jsonPath("$.upcomingTicketNumbers.length()")
                        .value(5))
                .andExpect(jsonPath("$.upcomingTicketNumbers[0]")
                        .value("Q001"))
                .andExpect(jsonPath("$.upcomingTicketNumbers[4]")
                        .value("Q005"));
    }

    @Test
    void shouldReturnEmptyOperationalStateWhenQueueHasNoEntries()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        Queue queue =
                queueRepository.save(
                        new Queue(
                                branch,
                                service,
                                "Consultation Queue",
                                LocalDate.now(),
                                "A"
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/board",
                                queue.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.nowServing")
                        .doesNotExist())
                .andExpect(jsonPath("$.calling")
                        .doesNotExist())
                .andExpect(jsonPath("$.waitingCount")
                        .value(0))
                .andExpect(jsonPath("$.upcomingTicketNumbers")
                        .isEmpty());
    }

    @Test
    void shouldReturnNotFoundForUnknownQueue()
            throws Exception {

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/board",
                                999999L
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue not found with id: 999999"
                        ));
    }
}