package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.GuestJoinIdempotency;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueStatus;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.security.AuthTokenService;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.ZoneId;
import java.util.List;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class QueueEntryControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private JdbcTemplate jdbcTemplate;

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
    private UserAccountRepository userAccountRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @Autowired
    private AuthTokenService authTokenService;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

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
        guestJoinIdempotencyRepository.deleteAll();
    }

    @Test
    void shouldAllowGuestToJoinServiceQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "Computer Repair"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "R"
        );

        MvcResult result =
                mockMvc.perform(
                                post(
                                        "/api/v1/queues/{queueId}/entries",
                                        queue.getId()
                                )
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("{}")
                        )
                        .andExpect(status().isCreated())
                        .andExpect(content()
                                .contentTypeCompatibleWith(
                                        MediaType.APPLICATION_JSON
                                ))
                        .andExpect(jsonPath("$.id").isNumber())
                        .andExpect(jsonPath("$.queueId")
                                .value(queue.getId()))
                        .andExpect(jsonPath("$.serviceId")
                                .value(service.getId()))
                        .andExpect(jsonPath("$.userId")
                                .doesNotExist())
                        .andExpect(jsonPath("$.ticketSequence")
                                .value(1))
                        .andExpect(jsonPath("$.ticketNumber")
                                .value("R001"))
                        .andExpect(jsonPath("$.status")
                                .value("WAITING"))
                        .andExpect(jsonPath("$.joinedAt")
                                .exists())
                        .andExpect(jsonPath("$.guestToken")
                                .isString())
                        .andReturn();

        assertThat(queueEntryRepository.count())
                .isEqualTo(1);

        QueueEntry entry =
                queueEntryRepository.findAll()
                        .getFirst();

        assertThat(entry.getUser()).isNull();
        assertThat(entry.getGuestTokenHash())
                .isNotBlank();

        String rawGuestToken =
                extractJsonString(
                        result.getResponse()
                                .getContentAsString(),
                        "guestToken"
                );

        assertThat(entry.getGuestTokenHash())
                .isEqualTo(
                        authTokenService.hashToken(
                                rawGuestToken
                        )
                );

        assertThat(entry.getGuestTokenHash())
                .isNotEqualTo(rawGuestToken);
    }

    @Test
    void shouldAllowRegisteredUserToJoinServiceQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "Computer Repair"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "R"
        );

        UserAccount user = createUser(
                "customer@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "customer@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.userId")
                        .value(user.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("R001"))
                .andExpect(jsonPath("$.status")
                        .value("WAITING"))
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist());

        List<QueueEntry> entries =
                queueEntryRepository.findAll();

        assertThat(entries).hasSize(1);
        assertThat(entries.getFirst()
                .getUser()
                .getId())
                .isEqualTo(user.getId());

        assertThat(entries.getFirst()
                .getGuestTokenHash())
                .isNull();
    }

    @Test
    void shouldRejectJoinWhenServiceSpecificQueueServiceIsInactive()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    service.setActive(false);
    serviceRepository.save(service);

    Queue queue = createServiceQueue(
            branch,
            service,
            "R"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isConflict())
            .andExpect(
                    jsonPath("$.message")
                            .value("Service is not active")
            );

    assertThat(queueEntryRepository.count())
            .isZero();
  }

    @Test
    void shouldAllocateSequentialTicketNumbers()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "General Service"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "A"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"));

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.ticketSequence")
                        .value(2))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A002"));

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.ticketSequence")
                        .value(3))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A003"));

        assertThat(queueEntryRepository.count())
                .isEqualTo(3);

        Queue updatedQueue =
                queueRepository.findById(queue.getId())
                        .orElseThrow();

        assertThat(updatedQueue.getNextTicketSequence())
                .isEqualTo(4);
    }

    @Test
    void shouldAllowGuestToJoinSharedQueueWithService()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "Passport Service"
                );

        Queue queue = createSharedQueue(
                branch,
                "A"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceId": %d
                                        }
                                        """.formatted(
                                        service.getId()
                                ))
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("WAITING"))
                .andExpect(jsonPath("$.guestToken")
                        .isString());

        QueueEntry entry =
                queueEntryRepository.findAll()
                        .getFirst();

        assertThat(entry.getService()
                .getId())
                .isEqualTo(service.getId());
    }

    @Test
    void shouldRequireServiceWhenJoiningSharedQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        Queue queue = createSharedQueue(
                branch,
                "A"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service is required when joining a shared queue"
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectServiceFromAnotherBranchForSharedQueue()
            throws Exception {

        Business business = createBusiness();

        Branch firstBranch =
                createBranch(business);

        Branch secondBranch =
                new Branch(
                        business,
                        "Second Branch",
                        "456 Second Street",
                        new BigDecimal("1.300000"),
                        new BigDecimal("103.800000")
                );

        secondBranch.setTimezone(
                "Asia/Singapore"
        );

        secondBranch =
                branchRepository.save(
                        secondBranch
                );

        com.queueflow.api.entity.Service otherService =
                createService(
                        secondBranch,
                        "Other Service"
                );

        Queue queue = createSharedQueue(
                firstBranch,
                "A"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceId": %d
                                        }
                                        """.formatted(
                                        otherService.getId()
                                ))
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service not found with id: "
                                        + otherService.getId()
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectDifferentServiceForServiceQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service queueService =
                createService(
                        branch,
                        "Computer Repair"
                );

        com.queueflow.api.entity.Service otherService =
                createService(
                        branch,
                        "Phone Repair"
                );

        Queue queue = createServiceQueue(
                branch,
                queueService,
                "R"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceId": %d
                                        }
                                        """.formatted(
                                        otherService.getId()
                                ))
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Requested service does not match this queue"
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectDuplicateActiveTicketForRegisteredUser()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "General Service"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "A"
        );

        createUser(
                "duplicate@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "duplicate@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"));

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.message")
                        .value(
                                "User already has an active ticket in this queue"
                        ));

        assertThat(queueEntryRepository.count())
                .isEqualTo(1);

        Queue updatedQueue =
                queueRepository.findById(queue.getId())
                        .orElseThrow();

        assertThat(updatedQueue.getNextTicketSequence())
                .isEqualTo(2);
    }

    @Test
    void shouldRejectJoinWhenQueueIsPaused()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "General Service"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "A"
        );

        queue.setStatus(
                QueueStatus.PAUSED
        );

        queueRepository.save(queue);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue is not open for joining"
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectJoinWhenQueueIsClosed()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                createService(
                        branch,
                        "General Service"
                );

        Queue queue = createServiceQueue(
                branch,
                service,
                "A"
        );

        queue.setStatus(
                QueueStatus.CLOSED
        );

        queueRepository.save(queue);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue is not open for joining"
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldReturnNotFoundForUnknownQueue()
            throws Exception {

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                999999L
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("{}")
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue not found with id: 999999"
                        ));

        assertThat(queueEntryRepository.count())
                .isZero();
    }
    @Test
    void shouldAllowRegisteredUserToCancelOwnWaitingEntry()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    UserAccount user = createUser(
            "cancel@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "cancel@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(entry.getId()))
            .andExpect(jsonPath("$.queueId")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.userId")
                    .value(user.getId()))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("A001"))
            .andExpect(jsonPath("$.status")
                    .value("CANCELLED"))

            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());

    QueueEntry cancelledEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(cancelledEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.CANCELLED
            );

    assertThat(cancelledEntry.getCancelledAt())
            .isNotNull();
  }
    @Test
    void shouldAllowRegisteredUserToViewOwnQueuePosition()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    UserAccount user = createUser(
            "position@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "position@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/position",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.entryId")
                    .value(entry.getId()))
            .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queueName")
                        .value(queue.getName()))
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.businessName")
                        .value(business.getName()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.branchName")
                        .value(branch.getName()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.serviceName")
                        .value(service.getName()))
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("A001"))
            .andExpect(jsonPath("$.status")
                    .value("WAITING"))
            .andExpect(jsonPath("$.peopleAhead")
                    .value(0))
            .andExpect(jsonPath("$.estimatedWaitMinutes")
                        .value(0))
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist())
                .andExpect(jsonPath("$.userId")
                        .doesNotExist());
   }
   @Test
   void shouldAllowGuestToViewOwnQueuePositionWithGuestToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    MvcResult joinResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.guestToken")
                            .isString())
                    .andReturn();

    String guestToken =
            extractJsonString(
                    joinResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/position",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.entryId")
                    .value(entry.getId()))
            .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queueName")
                        .value(queue.getName()))
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.businessName")
                        .value(business.getName()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.branchName")
                        .value(branch.getName()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.serviceName")
                        .value(service.getName()))
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("A001"))
            .andExpect(jsonPath("$.status")
                    .value("WAITING"))
            .andExpect(jsonPath("$.peopleAhead")
                    .value(0))
            .andExpect(jsonPath("$.estimatedWaitMinutes")
                        .value(0))
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist())
                .andExpect(jsonPath("$.userId")
                        .doesNotExist());
  }

    @Test
    void shouldRejectGuestPositionWithWrongToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/position",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    "this-is-the-wrong-token"
                            )
            )
            .andExpect(status().isForbidden());
 }

    @Test
    void shouldRejectGuestPositionWithoutToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/position",
                            queue.getId(),
                            entry.getId()
                    )
            )
            .andExpect(status().isForbidden());
  }

     @Test
     void shouldRejectRegisteredUserViewingAnotherUsersPosition()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    createUser(
            "position-owner@example.com",
            "password123"
    );

    String ownerToken = loginAndGetToken(
            "position-owner@example.com",
            "password123"
    );

    createUser(
            "position-other@example.com",
            "password123"
    );

    String otherToken = loginAndGetToken(
            "position-other@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + ownerToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/position",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + otherToken
                            )
            )
            .andExpect(status().isForbidden());
  }




    private Business createBusiness() {

        return businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );
    }
     @Test
     void shouldAllowGuestToCancelOwnWaitingEntryWithGuestToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    MvcResult joinResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.guestToken")
                            .isString())
                    .andReturn();

    String guestToken =
            extractJsonString(
                    joinResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(entry.getId()))
            .andExpect(jsonPath("$.queueId")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.userId")
                    .doesNotExist())
            .andExpect(jsonPath("$.ticketNumber")
                    .value("A001"))
            .andExpect(jsonPath("$.status")
                    .value("CANCELLED"))
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());

    QueueEntry cancelledEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(cancelledEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.CANCELLED
            );

    assertThat(cancelledEntry.getCancelledAt())
            .isNotNull();
  }
    @Test
    void shouldRejectGuestCancellationWithWrongToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    "this-is-the-wrong-token"
                            )
            )
            .andExpect(status().isForbidden());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.WAITING
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
  }
     @Test
     void shouldRejectGuestCancellationWithoutToken()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
            )
            .andExpect(status().isForbidden());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.WAITING
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
  }
    @Test
    void shouldRejectRegisteredUserCancellingAnotherUsersEntry()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    createUser(
            "owner@example.com",
            "password123"
    );

    String ownerToken = loginAndGetToken(
            "owner@example.com",
            "password123"
    );

    createUser(
            "other@example.com",
            "password123"
    );

    String otherToken = loginAndGetToken(
            "other@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + ownerToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + otherToken
                            )
            )
            .andExpect(status().isForbidden());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.WAITING
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
  }
    @Test
    void shouldRejectCancellationWhenEntryIsAlreadyCalled()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    createUser(
            "called@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "called@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    entry.setStatus(
            com.queueflow.api.entity.QueueEntryStatus.CALLED
    );

    queueEntryRepository.saveAndFlush(entry);

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.CALLED
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
  }
    @Test
    void shouldReturnNotFoundWhenEntryBelongsToDifferentQueue()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service serviceOne =
            createService(
                    branch,
                    "Service One"
            );

    com.queueflow.api.entity.Service serviceTwo =
            createService(
                    branch,
                    "Service Two"
            );

    Queue queueOne = createServiceQueue(
            branch,
            serviceOne,
            "A"
    );

    Queue queueTwo = createServiceQueue(
            branch,
            serviceTwo,
            "B"
    );

    createUser(
            "mismatch@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "mismatch@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queueOne.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queueTwo.getId(),
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isNotFound());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.WAITING
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
  }
    @Test
    void shouldReturnNotFoundWhenCancellingFromUnknownQueue()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    createUser(
            "unknownqueue@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "unknownqueue@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated());

    QueueEntry entry =
            queueEntryRepository.findAll()
                    .getFirst();

    long unknownQueueId = 999999999L;

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            unknownQueueId,
                            entry.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isNotFound());

    QueueEntry unchangedEntry =
            queueEntryRepository.findById(
                    entry.getId()
            ).orElseThrow();

    assertThat(unchangedEntry.getStatus())
            .isEqualTo(
                    com.queueflow.api.entity.QueueEntryStatus.WAITING
            );

    assertThat(unchangedEntry.getCancelledAt())
            .isNull();
   }
    @Test
    void shouldReturnNotFoundWhenCancellingUnknownEntry()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "General Service"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "A"
    );

    createUser(
            "unknownentry@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "unknownentry@example.com",
            "password123"
    );

    long unknownEntryId = 999999999L;

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries/{entryId}/cancel",
                            queue.getId(),
                            unknownEntryId
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isNotFound());
  }

    private Branch createBranch(
            Business business
    ) {

        Branch branch =
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                );

        branch.setTimezone(
                "Asia/Singapore"
        );

        return branchRepository.save(
                branch
        );
    }

    private com.queueflow.api.entity.Service createService(
            Branch branch,
            String name
    ) {

        return serviceRepository.save(
                new com.queueflow.api.entity.Service(
                        branch,
                        name,
                        name + " description",
                        30
                )
        );
    }

    private Queue createSharedQueue(
            Branch branch,
            String prefix
    ) {

        Queue queue =
                new Queue(
                        branch,
                        null,
                        "Main Queue",
                        LocalDate.now(
                                ZoneId.of(
                                        branch.getTimezone()
                                )
                        ),
                        prefix
                );

        return queueRepository.save(
                queue
        );
    }

    private Queue createServiceQueue(
            Branch branch,
            com.queueflow.api.entity.Service service,
            String prefix
    ) {

        Queue queue =
                new Queue(
                        branch,
                        service,
                        service.getName() + " Queue",
                        LocalDate.now(
                                ZoneId.of(
                                        branch.getTimezone()
                                )
                        ),
                        prefix
                );

        return queueRepository.save(
                queue
        );
    }

    private UserAccount createUser(
            String email,
            String rawPassword
    ) {

        UserAccount user =
                new UserAccount(
                        email,
                        passwordEncoder.encode(
                                rawPassword
                        ),
                        "Queue",
                        "Customer",
                        null
                );

        return userAccountRepository.save(
                user
        );
    }

    private String loginAndGetToken(
            String email,
            String password
    ) throws Exception {

        MvcResult result =
                mockMvc.perform(
                                post("/api/v1/auth/login")
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("""
                                                {
                                                    "email": "%s",
                                                    "password": "%s"
                                                }
                                                """.formatted(
                                                email,
                                                password
                                        )))
                        .andExpect(status().isOk())
                        .andReturn();

        return extractJsonString(
                result.getResponse()
                        .getContentAsString(),
                "token"
        );
    }

    private String extractJsonString(
            String json,
            String fieldName
    ) {

        String marker =
                "\"" + fieldName + "\":\"";

        int start =
                json.indexOf(marker);

        assertThat(start)
                .isGreaterThanOrEqualTo(0);

        start += marker.length();

        int end =
                json.indexOf(
                        "\"",
                        start
                );

        assertThat(end)
                .isGreaterThan(start);

        return json.substring(
                start,
                end
        );
    }

    @Test
void shouldReplayGuestJoinWithSameIdempotencyKey()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    Queue queue =
            createServiceQueue(
                    branch,
                    service,
                    "R"
            );

    String idempotencyKey =
            "11111111-1111-4111-8111-111111111111";

    MvcResult firstResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .header(
                                            "Idempotency-Key",
                                            idempotencyKey
                                    )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.ticketSequence")
                            .value(1))
                    .andExpect(jsonPath("$.ticketNumber")
                            .value("R001"))
                    .andReturn();

    MvcResult replayResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .header(
                                            "Idempotency-Key",
                                            idempotencyKey
                                    )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.ticketSequence")
                            .value(1))
                    .andExpect(jsonPath("$.ticketNumber")
                            .value("R001"))
                    .andReturn();

    String firstGuestToken =
            extractJsonString(
                    firstResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );

    String replayGuestToken =
            extractJsonString(
                    replayResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );

    assertThat(replayGuestToken)
            .isEqualTo(firstGuestToken);

    assertThat(queueEntryRepository.count())
            .isEqualTo(1);

    assertThat(guestJoinIdempotencyRepository.count())
            .isEqualTo(1);

    Queue updatedQueue =
            queueRepository.findById(queue.getId())
                    .orElseThrow();

    assertThat(updatedQueue.getNextTicketSequence())
            .isEqualTo(2);
}

@Test
void shouldRejectSameIdempotencyKeyWithDifferentService()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service firstService =
            createService(
                    branch,
                    "Computer Repair"
            );

    com.queueflow.api.entity.Service secondService =
            createService(
                    branch,
                    "Phone Repair"
            );

    Queue queue =
            createSharedQueue(
                    branch,
                    "A"
            );

    String idempotencyKey =
            "22222222-2222-4222-8222-222222222222";

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceId": %d
                                    }
                                    """.formatted(
                                    firstService.getId()
                            ))
            )
            .andExpect(status().isCreated());

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceId": %d
                                    }
                                    """.formatted(
                                    secondService.getId()
                            ))
            )
            .andExpect(status().isConflict());

    assertThat(queueEntryRepository.count())
            .isEqualTo(1);

    assertThat(guestJoinIdempotencyRepository.count())
            .isEqualTo(1);
}

@Test
void shouldRejectInvalidIdempotencyKey()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    Queue queue =
            createServiceQueue(
                    branch,
                    service,
                    "R"
            );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    "not-a-valid-uuid"
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isBadRequest())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Idempotency-Key must be a valid UUID"
                    ));

    assertThat(queueEntryRepository.count())
            .isZero();

    assertThat(guestJoinIdempotencyRepository.count())
            .isZero();
}

 @Test
 void shouldAllowDifferentIdempotencyKeysForGuestJoins()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    Queue queue =
            createServiceQueue(
                    branch,
                    service,
                    "R"
            );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    "33333333-3333-4333-8333-333333333333"
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("R001"));

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    "44444444-4444-4444-8444-444444444444"
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andExpect(jsonPath("$.ticketSequence")
                    .value(2))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("R002"));

    assertThat(queueEntryRepository.count())
            .isEqualTo(2);

    assertThat(guestJoinIdempotencyRepository.count())
            .isEqualTo(2);
 }
    @Test
    void shouldTreatExpiredIdempotencyKeyAsFreshGuestJoin()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    Queue queue =
            createServiceQueue(
                    branch,
                    service,
                    "R"
            );

    String idempotencyKey =
            "55555555-5555-4555-8555-555555555555";

    MvcResult firstResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .header(
                                            "Idempotency-Key",
                                            idempotencyKey
                                    )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.ticketSequence")
                            .value(1))
                    .andExpect(jsonPath("$.ticketNumber")
                            .value("R001"))
                    .andReturn();

    String firstGuestToken =
            extractJsonString(
                    firstResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );

    jdbcTemplate.update(
            """
            UPDATE guest_join_idempotency
            SET expires_at = ?
            WHERE queue_id = ?
              AND idempotency_key = ?
            """,
            OffsetDateTime.now().minusMinutes(1),
            queue.getId(),
            idempotencyKey
    );

    MvcResult secondResult =
            mockMvc.perform(
                            post(
                                    "/api/v1/queues/{queueId}/entries",
                                    queue.getId()
                            )
                                    .header(
                                            "Idempotency-Key",
                                            idempotencyKey
                                    )
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("{}")
                    )
                    .andExpect(status().isCreated())
                    .andExpect(jsonPath("$.ticketSequence")
                            .value(2))
                    .andExpect(jsonPath("$.ticketNumber")
                            .value("R002"))
                    .andReturn();

    String secondGuestToken =
            extractJsonString(
                    secondResult.getResponse()
                            .getContentAsString(),
                    "guestToken"
            );
    assertThat(secondGuestToken)
        .isNotEqualTo(firstGuestToken);

    assertThat(queueEntryRepository.count())
        .isEqualTo(2);

    assertThat(guestJoinIdempotencyRepository.count())
        .isEqualTo(1);

     Queue updatedQueue =
        queueRepository.findById(queue.getId())
                .orElseThrow();

   assertThat(updatedQueue.getNextTicketSequence())
        .isEqualTo(3);

   GuestJoinIdempotency replacement =
        guestJoinIdempotencyRepository
                .findByQueueIdAndIdempotencyKey(
                        queue.getId(),
                        idempotencyKey
                )
                .orElseThrow();

  Long replacementEntryId =
        replacement.getQueueEntry().getId();

  QueueEntry replacementEntry =
        queueEntryRepository
                .findById(replacementEntryId)
                .orElseThrow();

  assertThat(replacementEntry.getTicketSequence())
        .isEqualTo(2);

  assertThat(replacement.getExpiresAt())
        .isAfter(OffsetDateTime.now());
   }

   @Test
void shouldIgnoreGuestIdempotencyForAuthenticatedJoin()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service service =
            createService(
                    branch,
                    "Computer Repair"
            );

    Queue queue = createServiceQueue(
            branch,
            service,
            "R"
    );

    UserAccount user = createUser(
            "idempotent-user@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "idempotent-user@example.com",
            "password123"
    );

    String idempotencyKey =
            "77777777-7777-4777-8777-777777777777";

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andExpect(jsonPath("$.userId")
                    .value(user.getId()))
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("R001"))
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());

    assertThat(queueEntryRepository.count())
            .isEqualTo(1);

    assertThat(
            guestJoinIdempotencyRepository.count()
    ).isZero();

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "User already has an active ticket in this queue"
                    ));

    assertThat(queueEntryRepository.count())
            .isEqualTo(1);

    assertThat(
            guestJoinIdempotencyRepository.count()
    ).isZero();

    Queue updatedQueue =
            queueRepository.findById(queue.getId())
                    .orElseThrow();

    assertThat(updatedQueue.getNextTicketSequence())
            .isEqualTo(2);
  }

  @Test
void shouldAllowSameIdempotencyKeyAcrossDifferentQueues()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);

    com.queueflow.api.entity.Service firstService =
            createService(
                    branch,
                    "Computer Repair"
            );

    com.queueflow.api.entity.Service secondService =
            createService(
                    branch,
                    "Phone Repair"
            );

    Queue firstQueue =
            createServiceQueue(
                    branch,
                    firstService,
                    "R"
            );

    Queue secondQueue =
            createServiceQueue(
                    branch,
                    secondService,
                    "P"
            );

    String idempotencyKey =
            "88888888-8888-4888-8888-888888888888";

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            firstQueue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andExpect(jsonPath("$.queueId")
                    .value(firstQueue.getId()))
            .andExpect(jsonPath("$.serviceId")
                    .value(firstService.getId()))
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("R001"))
            .andExpect(jsonPath("$.guestToken")
                    .isString());

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            secondQueue.getId()
                    )
                            .header(
                                    "Idempotency-Key",
                                    idempotencyKey
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andExpect(jsonPath("$.queueId")
                    .value(secondQueue.getId()))
            .andExpect(jsonPath("$.serviceId")
                    .value(secondService.getId()))
            .andExpect(jsonPath("$.ticketSequence")
                    .value(1))
            .andExpect(jsonPath("$.ticketNumber")
                    .value("P001"))
            .andExpect(jsonPath("$.guestToken")
                    .isString());

    assertThat(queueEntryRepository.count())
            .isEqualTo(2);

    assertThat(
            guestJoinIdempotencyRepository.count()
    ).isEqualTo(2);

    Queue updatedFirstQueue =
            queueRepository.findById(firstQueue.getId())
                    .orElseThrow();

    Queue updatedSecondQueue =
            queueRepository.findById(secondQueue.getId())
                    .orElseThrow();

    assertThat(
            updatedFirstQueue.getNextTicketSequence()
    ).isEqualTo(2);

    assertThat(
            updatedSecondQueue.getNextTicketSequence()
    ).isEqualTo(2);
  }
}




