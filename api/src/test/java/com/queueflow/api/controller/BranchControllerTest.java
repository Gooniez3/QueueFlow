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

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class BranchControllerTest {

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
    void shouldGetBranchesByBusiness() throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        branchRepository.save(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        branchRepository.save(
                new Branch(
                        business,
                        "North Branch",
                        "456 North Street",
                        null,
                        null
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.length()").value(2))
                .andExpect(jsonPath("$[0].name")
                        .value("Downtown Branch"))
                .andExpect(jsonPath("$[0].businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$[1].name")
                        .value("North Branch"));
    }

    @Test
    void shouldReturnEmptyListWhenBusinessHasNoBranches()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$").isArray())
                .andExpect(jsonPath("$").isEmpty());
    }

    @Test
    void shouldReturnNotFoundWhenListingBranchesForMissingBusiness()
            throws Exception {

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches",
                                999999L
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status").value(404))
                .andExpect(jsonPath("$.error")
                        .value("Not Found"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Business not found with id: 999999"
                        ));
    }

    @Test
    void shouldGetBranchById() throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}",
                                business.getId(),
                                branch.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.id")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Downtown Branch"))
                .andExpect(jsonPath("$.address")
                        .value("123 Main Street"))
                .andExpect(jsonPath("$.latitude")
                        .value(1.352100))
                .andExpect(jsonPath("$.longitude")
                        .value(103.819800))
                .andExpect(jsonPath("$.createdAt")
                        .exists());
    }

    @Test
    void shouldReturnNotFoundWhenBranchDoesNotBelongToBusiness()
            throws Exception {

        Business firstBusiness = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        Business secondBusiness = businessRepository.save(
                new Business(
                        "QueueFlow Bank",
                        "Banking services"
                )
        );

        Branch branch = branchRepository.save(
                new Branch(
                        secondBusiness,
                        "Bank Branch",
                        "789 Bank Street",
                        null,
                        null
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}",
                                firstBusiness.getId(),
                                branch.getId()
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.error")
                        .value("Not Found"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Branch not found with id: "
                                        + branch.getId()
                        ));
    }

    @Test
    void shouldRequireAuthenticationToCreateBranch()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        String requestBody = validBranchRequest();

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(requestBody)
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldCreateBranchWhenUserHasBusinessMembership()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        UserAccount user = createUser(
                "member@example.com",
                "password123"
        );

        StaffMembership membership =
                new StaffMembership(
                        user,
                        business,
                        null,
                        StaffRole.STAFF
                );

        staffMembershipRepository.save(membership);

        String token = loginAndGetToken(
                "member@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(validBranchRequest())
                )
                .andExpect(status().isCreated())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.id").isNumber())
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.name")
                        .value("Downtown Branch"))
                .andExpect(jsonPath("$.address")
                        .value("123 Main Street"))
                .andExpect(jsonPath("$.latitude")
                        .value(1.3521))
                .andExpect(jsonPath("$.longitude")
                        .value(103.8198))
                .andExpect(jsonPath("$.timezone")
                        .value("Asia/Singapore"))
                .andExpect(jsonPath("$.createdAt")
                        .exists());

        assertThat(branchRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectBranchCreationWhenUserHasNoBusinessMembership()
            throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

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
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(validBranchRequest())
                )
                .andExpect(status().isForbidden());

        assertThat(branchRepository.count())
                .isZero();
    }

    @Test
    void shouldRejectInvalidTimezone() throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        UserAccount user = createUser(
                "timezone@example.com",
                "password123"
        );

        StaffMembership membership =
                new StaffMembership(
                        user,
                        business,
                        null,
                        StaffRole.STAFF
                );

        staffMembershipRepository.save(membership);

        String token = loginAndGetToken(
                "timezone@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/businesses/{businessId}/branches",
                                business.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(MediaType.APPLICATION_JSON)
                                .content("""
                                        {
                                            "name": "Downtown Branch",
                                            "address": "123 Main Street",
                                            "latitude": 1.3521,
                                            "longitude": 103.8198,
                                            "timezone": "Not/A_Real_Timezone"
                                        }
                                        """)
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status").value(400))
                .andExpect(jsonPath("$.error").value("Bad Request"))
                .andExpect(
                        jsonPath("$.message")
                                .value(
                                        "Invalid timezone: Not/A_Real_Timezone"
                                )
                );

        assertThat(branchRepository.count()).isZero();
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

    private String validBranchRequest() {
        return """
                {
                    "name": "Downtown Branch",
                    "address": "123 Main Street",
                    "latitude": 1.3521,
                    "longitude": 103.8198,
                    "timezone": "Asia/Singapore"
                }
                """;
    }
}