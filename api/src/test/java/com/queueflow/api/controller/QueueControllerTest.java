package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
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
import org.springframework.http.MediaType;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.ZoneId;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class QueueControllerTest {

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
    private UserAccountRepository userAccountRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

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
    void shouldRequireAuthenticationToCreateQueue()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(sharedQueueRequest())
                )
                .andExpect(status().isUnauthorized());

        assertThat(queueRepository.count()).isZero();
    }

    @Test
    void shouldCreateSharedQueue()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        String token = createMemberAndLogin(
                business,
                "staff@example.com"
        );

        LocalDate expectedBusinessDate =
                LocalDate.now(
                        ZoneId.of("Asia/Singapore")
                );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(sharedQueueRequest())
                )
                .andExpect(status().isCreated())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.id").isNumber())
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .doesNotExist())
                .andExpect(jsonPath("$.name")
                        .value("Main Queue"))
                .andExpect(jsonPath("$.businessDate")
                        .value(expectedBusinessDate.toString()))
                .andExpect(jsonPath("$.ticketPrefix")
                        .value("A"))
                .andExpect(jsonPath("$.nextTicketSequence")
                        .value(1))
                .andExpect(jsonPath("$.status")
                        .value("OPEN"))
                .andExpect(jsonPath("$.openedAt").exists())
                .andExpect(jsonPath("$.createdAt").exists());

        assertThat(queueRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldCreateServiceQueue()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Computer Repair",
                                "Computer repair service",
                                30
                        )
                );

        String token = createMemberAndLogin(
                business,
                "service@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(
                                        serviceQueueRequest(
                                                service.getId()
                                        )
                                )
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Repair Queue"))
                .andExpect(jsonPath("$.ticketPrefix")
                        .value("R"))
                .andExpect(jsonPath("$.nextTicketSequence")
                        .value(1))
                .andExpect(jsonPath("$.status")
                        .value("OPEN"));

        assertThat(queueRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectDuplicateSharedQueueForToday()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        String token = createMemberAndLogin(
                business,
                "duplicate@example.com"
        );

        createQueue(
                token,
                business.getId(),
                branch.getId(),
                sharedQueueRequest()
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(sharedQueueRequest())
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.error")
                        .value("Conflict"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Shared queue already exists for this branch today"
                        ));

        assertThat(queueRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectDuplicateServiceQueueForToday()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Computer Repair",
                                "Computer repair service",
                                30
                        )
                );

        String token = createMemberAndLogin(
                business,
                "duplicate-service@example.com"
        );

        String requestBody =
                serviceQueueRequest(service.getId());

        createQueue(
                token,
                business.getId(),
                branch.getId(),
                requestBody
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(requestBody)
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue already exists for this service today"
                        ));

        assertThat(queueRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectQueueCreationWithoutBusinessMembership()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        createUser(
                "outsider@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "outsider@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(sharedQueueRequest())
                )
                .andExpect(status().isForbidden());

        assertThat(queueRepository.count())
                .isZero();
    }

    @Test
    void shouldReturnNotFoundWhenBranchDoesNotBelongToBusiness()
            throws Exception {

        Business firstBusiness = createBusiness();

        Business secondBusiness =
                businessRepository.save(
                        new Business(
                                "Other Business",
                                "Other business"
                        )
                );

        Branch secondBranch = createBranch(
                secondBusiness,
                "Asia/Singapore"
        );

        String token = createMemberAndLogin(
                firstBusiness,
                "first-business@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                firstBusiness.getId(),
                                secondBranch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(sharedQueueRequest())
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Branch not found with id: "
                                        + secondBranch.getId()
                        ));

        assertThat(queueRepository.count())
                .isZero();
    }

    @Test
    void shouldReturnNotFoundWhenServiceDoesNotBelongToBranch()
            throws Exception {

        Business business = createBusiness();

        Branch firstBranch = createBranch(
                business,
                "Asia/Singapore"
        );

        Branch secondBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Second Branch",
                                "456 Second Street",
                                null,
                                null
                        )
                );

        secondBranch.setTimezone(
                "Asia/Singapore"
        );

        secondBranch =
                branchRepository.save(secondBranch);

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                secondBranch,
                                "Other Service",
                                "Service at another branch",
                                20
                        )
                );

        String token = createMemberAndLogin(
                business,
                "wrong-service@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                firstBranch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(
                                        serviceQueueRequest(
                                                service.getId()
                                        )
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service not found with id: "
                                        + service.getId()
                        ));

        assertThat(queueRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectInvalidQueueRequest()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        String token = createMemberAndLogin(
                business,
                "validation@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "name": "",
                                            "ticketPrefix": ""
                                        }
                                        """)
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400))
                .andExpect(jsonPath("$.error")
                        .value("Bad Request"));

        assertThat(queueRepository.count())
                .isZero();
    }

    private Business createBusiness() {
        return businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );
    }

    private Branch createBranch(
            Business business,
            String timezone
    ) {

        Branch branch =
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                );

        branch.setTimezone(timezone);

        return branchRepository.save(branch);
    }

    private String createMemberAndLogin(
            Business business,
            String email
    ) throws Exception {

        UserAccount user = createUser(
                email,
                "password123"
        );

        staffMembershipRepository.save(
                new StaffMembership(
                        user,
                        business,
                        null,
                        StaffRole.STAFF
                )
        );

        return loginAndGetToken(
                email,
                "password123"
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
                        "Staff",
                        null
                );

        return userAccountRepository.save(user);
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

    private void createQueue(
            String token,
            Long businessId,
            Long branchId,
            String requestBody
    ) throws Exception {

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/queues",
                                businessId,
                                branchId
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(requestBody)
                )
                .andExpect(status().isCreated());
    }

    private String extractJsonString(
            String json,
            String fieldName
    ) {

        String marker =
                "\"" + fieldName + "\":\"";

        int start = json.indexOf(marker);

        assertThat(start)
                .isGreaterThanOrEqualTo(0);

        start += marker.length();

        int end = json.indexOf(
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

    private String sharedQueueRequest() {
        return """
                {
                    "name": "Main Queue",
                    "ticketPrefix": "a"
                }
                """;
    }

    private String serviceQueueRequest(
            Long serviceId
    ) {
        return """
                {
                    "serviceId": %d,
                    "name": "Repair Queue",
                    "ticketPrefix": "r"
                }
                """.formatted(serviceId);
    }
}