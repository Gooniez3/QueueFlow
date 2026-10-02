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

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class ServiceControllerTest {

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
    void shouldGetServicesByBranch() throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        serviceRepository.save(
                new com.queueflow.api.entity.Service(
                        branch,
                        "Consultation",
                        "General consultation",
                        30
                )
        );

        serviceRepository.save(
                new com.queueflow.api.entity.Service(
                        branch,
                        "Checkup",
                        "Routine checkup",
                        20
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
                                business.getId(),
                                branch.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()").value(2))
                .andExpect(jsonPath("$[0].name")
                        .value("Consultation"))
                .andExpect(jsonPath("$[0].branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$[1].name")
                        .value("Checkup"));
    }

    @Test
    void shouldReturnEmptyListWhenBranchHasNoServices()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
                                business.getId(),
                                branch.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$").isArray())
                .andExpect(jsonPath("$").isEmpty());
    }

    @Test
    void shouldGetServiceById() throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "Consultation",
                                "General consultation",
                                30
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services/{serviceId}",
                                business.getId(),
                                branch.getId(),
                                service.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.id")
                        .value(service.getId()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Consultation"))
                .andExpect(jsonPath("$.description")
                        .value("General consultation"))
                .andExpect(jsonPath("$.durationMinutes")
                        .value(30))
                .andExpect(jsonPath("$.active")
                        .value(true))
                .andExpect(jsonPath("$.createdAt")
                        .exists());
    }

    @Test
    void shouldReturnNotFoundWhenBranchDoesNotBelongToBusiness()
            throws Exception {

        Business firstBusiness = createBusiness();

        Business secondBusiness =
                businessRepository.save(
                        new Business(
                                "QueueFlow Bank",
                                "Banking services"
                        )
                );

        Branch secondBranch =
                branchRepository.save(
                        new Branch(
                                secondBusiness,
                                "Bank Branch",
                                "456 Bank Street",
                                null,
                                null
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
                                firstBusiness.getId(),
                                secondBranch.getId()
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Branch not found with id: "
                                        + secondBranch.getId()
                        ));
    }

    @Test
    void shouldReturnNotFoundWhenServiceDoesNotBelongToBranch()
            throws Exception {

        Business business = createBusiness();

        Branch firstBranch = createBranch(business);

        Branch secondBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "North Branch",
                                "456 North Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                secondBranch,
                                "Consultation",
                                "General consultation",
                                30
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services/{serviceId}",
                                business.getId(),
                                firstBranch.getId(),
                                service.getId()
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
    }

    @Test
    void shouldRequireAuthenticationToCreateService()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
                                business.getId(),
                                branch.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(validServiceRequest())
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldCreateServiceWhenUserHasBusinessMembership()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        UserAccount user = createUser(
                "member@example.com",
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

        String token = loginAndGetToken(
                "member@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
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
                                .content(validServiceRequest())
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.id").isNumber())
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Consultation"))
                .andExpect(jsonPath("$.description")
                        .value("General consultation"))
                .andExpect(jsonPath("$.durationMinutes")
                        .value(30))
                .andExpect(jsonPath("$.active")
                        .value(true))
                .andExpect(jsonPath("$.createdAt")
                        .exists());

        assertThat(serviceRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectServiceCreationWhenUserHasNoBusinessMembership()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        createUser(
                "nonmember@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "nonmember@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
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
                                .content(validServiceRequest())
                )
                .andExpect(status().isForbidden());

        assertThat(serviceRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectInvalidServiceDuration()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        UserAccount user = createUser(
                "member@example.com",
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

        String token = loginAndGetToken(
                "member@example.com",
                "password123"
        );

        String requestBody = """
                {
                    "name": "Consultation",
                    "description": "General consultation",
                    "durationMinutes": 0
                }
                """;

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/services",
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
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400))
                .andExpect(jsonPath("$.message")
                        .value("Request validation failed"))
                .andExpect(jsonPath(
                        "$.validationErrors.durationMinutes"
                ).value(
                        "Duration must be greater than 0"
                ));
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
            Business business
    ) {
        return branchRepository.save(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        null,
                        null
                )
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

    private String validServiceRequest() {
        return """
                {
                    "name": "Consultation",
                    "description": "General consultation",
                    "durationMinutes": 30
                }
                """;
    }
}