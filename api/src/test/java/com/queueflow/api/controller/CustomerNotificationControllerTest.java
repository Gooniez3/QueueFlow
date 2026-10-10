
package com.queueflow.api.controller;

import tools.jackson.databind.JsonNode;
import tools.jackson.databind.ObjectMapper;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.CustomerNotification;
import com.queueflow.api.entity.CustomerNotificationType;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.CustomerNotificationRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.repository.AuthSessionRepository;
import org.springframework.security.crypto.password.PasswordEncoder;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.ZoneId;
import java.time.OffsetDateTime;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class CustomerNotificationControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private ObjectMapper objectMapper;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private CustomerNotificationRepository notificationRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        notificationRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    private Queue createTestQueue(String businessName) {
        Business business = businessRepository.save(
                new Business(businessName, "Test")
        );

        Branch branch = new Branch(
                business,
                "Test Branch",
                "123 Main Street",
                new BigDecimal("1.352100"),
                new BigDecimal("103.819800")
        );

        branch.setTimezone("Asia/Singapore");
        branch = branchRepository.save(branch);

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Service",
                                "Test service",
                                30
                        )
                );

        return queueRepository.save(
                new Queue(
                        branch,
                        service,
                        "Test Queue",
                        LocalDate.now(ZoneId.of("Asia/Singapore")),
                        "N"
                )
        );
    }

    private JsonNode joinAsGuest(Queue queue) throws Exception {
        MvcResult result = mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries",
                                queue.getId()
                        )
                                .contentType(MediaType.APPLICATION_JSON)
                                .content("{}")
                )
                .andExpect(status().isCreated())
                .andReturn();

        return objectMapper.readTree(
                result.getResponse().getContentAsString()
        );
    }

    private CustomerNotification createNotification(Long entryId) {
        return notificationRepository.save(
                new CustomerNotification(
                        queueEntryRepository.findById(entryId)
                                .orElseThrow(),
                        CustomerNotificationType.TICKET_CALLED,
                        "Your ticket has been called",
                        "Please proceed to the service counter."
                )
        );
    }

    @Test
    void shouldAllowGuestToReadOwnNotifications() throws Exception {
        Queue queue = createTestQueue(
                "Notification Test Business"
        );

        JsonNode joined = joinAsGuest(queue);

        long entryId = joined.path("id").asLong();
        String guestToken = joined.path("guestToken").asText();

        CustomerNotification notification =
                createNotification(entryId);

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/entries/{entryId}/notifications",
                                queue.getId(),
                                entryId
                        )
                                .header("X-Guest-Token", guestToken)
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$[0].id")
                        .value(notification.getId()))
                .andExpect(jsonPath("$[0].type")
                        .value("TICKET_CALLED"))
                .andExpect(jsonPath("$[0].read")
                        .value(false))
                .andExpect(jsonPath("$[0].readAt")
                        .isEmpty());
    }

    @Test
    void shouldRejectGuestWithInvalidToken() throws Exception {
        Queue queue = createTestQueue(
                "Security Test Business"
        );

        JsonNode joined = joinAsGuest(queue);

        long entryId = joined.path("id").asLong();

        createNotification(entryId);

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/entries/{entryId}/notifications",
                                queue.getId(),
                                entryId
                        )
                                .header(
                                        "X-Guest-Token",
                                        "invalid-guest-token"
                                )
                )
                .andExpect(status().isForbidden());
    }

    @Test
    void shouldAllowGuestToMarkNotificationAsRead() throws Exception {
        Queue queue = createTestQueue(
                "Read Test Business"
        );

        JsonNode joined = joinAsGuest(queue);

        long entryId = joined.path("id").asLong();
        String guestToken = joined.path("guestToken").asText();

        CustomerNotification notification =
                createNotification(entryId);

        String endpoint =
                "/api/v1/queues/{queueId}/entries/{entryId}"
                        + "/notifications/{notificationId}/read";

        MvcResult firstResult = mockMvc.perform(
                        post(
                                endpoint,
                                queue.getId(),
                                entryId,
                                notification.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.read")
                        .value(true))
                .andExpect(jsonPath("$.readAt")
                        .isNotEmpty())
                .andReturn();

        JsonNode firstResponse = objectMapper.readTree(
                firstResult.getResponse().getContentAsString()
        );

        String firstReadAt =
                firstResponse.path("readAt").asText();

        MvcResult repeatedResult = mockMvc.perform(
                        post(
                                endpoint,
                                queue.getId(),
                                entryId,
                                notification.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.read")
                        .value(true))
                .andReturn();

        JsonNode repeatedResponse = objectMapper.readTree(
                repeatedResult.getResponse().getContentAsString()
        );

        String repeatedReadAt =
                repeatedResponse.path("readAt").asText();

        assertThat(
        java.time.Duration.between(
                OffsetDateTime.parse(firstReadAt).toInstant(),
                OffsetDateTime.parse(repeatedReadAt).toInstant()
        ).abs()
).isLessThanOrEqualTo(
        java.time.Duration.ofNanos(1000)
);

        CustomerNotification persisted =
                notificationRepository.findById(
                        notification.getId()
                ).orElseThrow();

        assertThat(persisted.isRead()).isTrue();
        assertThat(persisted.getReadAt()).isNotNull();
    }

    @Test
    void shouldRejectMarkingAnotherTicketsNotificationAsRead()
            throws Exception {

        Queue queue = createTestQueue(
                "Ownership Test Business"
        );

        JsonNode first = joinAsGuest(queue);
        JsonNode second = joinAsGuest(queue);

        long firstEntryId =
                first.path("id").asLong();

        long secondEntryId =
                second.path("id").asLong();

        String firstGuestToken =
                first.path("guestToken").asText();

        assertThat(firstEntryId)
                .isNotEqualTo(secondEntryId);

        CustomerNotification secondNotification =
                createNotification(secondEntryId);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/entries/{entryId}"
                                        + "/notifications/{notificationId}/read",
                                queue.getId(),
                                firstEntryId,
                                secondNotification.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        firstGuestToken
                                )
                )
                .andExpect(status().isNotFound());

        CustomerNotification persisted =
                notificationRepository.findById(
                        secondNotification.getId()
                ).orElseThrow();

        assertThat(persisted.isRead()).isFalse();
        assertThat(persisted.getReadAt()).isNull();
    }

    @Test
    void shouldAllowRegisteredCustomerToReadOwnNotifications()
        throws Exception {

    Queue queue = createTestQueue("Registered Customer Business");

    String email = "notification-customer@example.com";
    String password = "password123";

    userAccountRepository.save(
            new UserAccount(
                    email,
                    passwordEncoder.encode(password),
                    "Queue",
                    "Customer",
                    null
            )
    );

    MvcResult loginResult = mockMvc.perform(
                    post("/api/v1/auth/login")
                            .contentType(MediaType.APPLICATION_JSON)
                            .content("""
                                    {
                                        "email": "%s",
                                        "password": "%s"
                                    }
                                    """.formatted(email, password))
            )
            .andExpect(status().isOk())
            .andReturn();

    String bearerToken = objectMapper.readTree(
            loginResult.getResponse().getContentAsString()
    ).path("token").asText();

    assertThat(bearerToken).isNotBlank();

    MvcResult joinResult = mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/entries",
                            queue.getId()
                    )
                            .header("Authorization", "Bearer " + bearerToken)
                            .contentType(MediaType.APPLICATION_JSON)
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andReturn();

    long entryId = objectMapper.readTree(
            joinResult.getResponse().getContentAsString()
    ).path("id").asLong();

    CustomerNotification notification = createNotification(entryId);

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/notifications",
                            queue.getId(),
                            entryId
                    )
                            .header("Authorization", "Bearer " + bearerToken)
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$[0].id").value(notification.getId()))
            .andExpect(jsonPath("$[0].type").value("TICKET_CALLED"))
            .andExpect(jsonPath("$[0].read").value(false));
    }

    @Test
    void shouldRejectOtherRegisteredCustomerReadingNotifications()
        throws Exception {

    Queue queue = createTestQueue("Customer Isolation Business");

    String password = "password123";

    userAccountRepository.save(
            new UserAccount(
                    "customer-a@example.com",
                    passwordEncoder.encode(password),
                    "Customer",
                    "A",
                    null
            )
    );

    userAccountRepository.save(
            new UserAccount(
                    "customer-b@example.com",
                    passwordEncoder.encode(password),
                    "Customer",
                    "B",
                    null
            )
    );

    String tokenA = loginForNotificationTest(
            "customer-a@example.com", password
    );

    String tokenB = loginForNotificationTest(
            "customer-b@example.com", password
    );

    MvcResult joined = mockMvc.perform(
                    post("/api/v1/queues/{queueId}/entries", queue.getId())
                            .header("Authorization", "Bearer " + tokenA)
                            .contentType(MediaType.APPLICATION_JSON)
                            .content("{}")
            )
            .andExpect(status().isCreated())
            .andReturn();

    long entryId = objectMapper.readTree(
            joined.getResponse().getContentAsString()
    ).path("id").asLong();

    createNotification(entryId);

    mockMvc.perform(
                    get(
                            "/api/v1/queues/{queueId}/entries/{entryId}/notifications",
                            queue.getId(),
                            entryId
                    )
                            .header("Authorization", "Bearer " + tokenB)
            )
            .andExpect(status().isForbidden());
    }

    private String loginForNotificationTest(
        String email,
        String password
  ) throws Exception {

    MvcResult result = mockMvc.perform(
                    post("/api/v1/auth/login")
                            .contentType(MediaType.APPLICATION_JSON)
                            .content("""
                                    {
                                        "email": "%s",
                                        "password": "%s"
                                    }
                                    """.formatted(email, password))
            )
            .andExpect(status().isOk())
            .andReturn();

    return objectMapper.readTree(
            result.getResponse().getContentAsString()
    ).path("token").asText();
  }
    
    @Test
    void shouldCreateNotificationWhenStaffCallsNext()
        throws Exception {

    Queue queue = createTestQueue("Call Next Notification Business");

    JsonNode joined = joinAsGuest(queue);
    long entryId = joined.path("id").asLong();

    String email = "notification-staff@example.com";
    String password = "password123";

    UserAccount staff = userAccountRepository.save(
            new UserAccount(
                    email,
                    passwordEncoder.encode(password),
                    "Notification",
                    "Staff",
                    null
            )
    );

    staffMembershipRepository.save(
            new StaffMembership(
                    staff,
                    queue.getBranch().getBusiness(),
                    queue.getBranch(),
                    StaffRole.STAFF
            )
    );

    String token = loginForNotificationTest(email, password);

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/call-next",
                            queue.getId()
                    )
                            .header("Authorization", "Bearer " + token)
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.entryId").value(entryId))
            .andExpect(jsonPath("$.status").value("CALLED"));

    var notifications = notificationRepository
            .findByQueueEntryIdOrderByCreatedAtDescIdDesc(entryId);

    assertThat(notifications).hasSize(1);
    assertThat(notifications.get(0).getType())
            .isEqualTo(CustomerNotificationType.TICKET_CALLED);
    assertThat(notifications.get(0).isRead()).isFalse();
  }

  @Test
  void shouldCreateNotificationWhenStaffRecallsTicket()
        throws Exception {

    Queue queue = createTestQueue("Recall Notification Business");

    JsonNode joined = joinAsGuest(queue);
    long entryId = joined.path("id").asLong();

    String email = "recall-notification-staff@example.com";
    String password = "password123";

    UserAccount staff = userAccountRepository.save(
            new UserAccount(
                    email,
                    passwordEncoder.encode(password),
                    "Notification",
                    "Staff",
                    null
            )
    );

    staffMembershipRepository.save(
            new StaffMembership(
                    staff,
                    queue.getBranch().getBusiness(),
                    queue.getBranch(),
                    StaffRole.STAFF
            )
    );

    String token = loginForNotificationTest(email, password);

    // First call the ticket.
    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/call-next",
                            queue.getId()
                    )
                            .header("Authorization", "Bearer " + token)
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.entryId").value(entryId))
            .andExpect(jsonPath("$.status").value("CALLED"));

    // Then recall the same ticket.
    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/entries/{entryId}/recall",
                            queue.getId(),
                            entryId
                    )
                            .header("Authorization", "Bearer " + token)
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.entryId").value(entryId))
            .andExpect(jsonPath("$.status").value("CALLED"));

    var notifications = notificationRepository
            .findByQueueEntryIdOrderByCreatedAtDescIdDesc(entryId);

    assertThat(notifications).hasSize(2);

    assertThat(notifications)
            .extracting(CustomerNotification::getType)
            .containsExactlyInAnyOrder(
                    CustomerNotificationType.TICKET_CALLED,
                    CustomerNotificationType.TICKET_RECALLED
            );

    assertThat(notifications)
            .allMatch(notification -> !notification.isRead());
  }
}
