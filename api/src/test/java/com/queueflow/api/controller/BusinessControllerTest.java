package com.queueflow.api.controller;

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

import java.util.List;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class BusinessControllerTest {

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
    void shouldRejectUnauthenticatedBusinessCreation()
            throws Exception {

        mockMvc.perform(
                        post("/api/v1/businesses")
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(validBusinessRequest())
                )
                .andExpect(status().isUnauthorized());

        assertThat(businessRepository.count())
                .isZero();

        assertThat(staffMembershipRepository.count())
                .isZero();
    }

    @Test
    void shouldCreateBusinessAndOwnerMembership()
            throws Exception {

        UserAccount user = createUser(
                "owner@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "owner@example.com",
                "password123"
        );

        mockMvc.perform(
                        post("/api/v1/businesses")
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(validBusinessRequest())
                )
                .andExpect(status().isCreated())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.id").isNumber())
                .andExpect(jsonPath("$.name")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.description")
                        .value("Medical clinic"))
                .andExpect(jsonPath("$.createdAt").exists());

        assertThat(businessRepository.count())
                .isEqualTo(1);

        assertThat(staffMembershipRepository.count())
                .isEqualTo(1);

        List<StaffMembership> memberships =
                staffMembershipRepository
                        .findByUserIdAndActiveTrue(
                                user.getId()
                        );

        assertThat(memberships)
                .hasSize(1);

        StaffMembership membership =
                memberships.getFirst();

        assertThat(membership.getUser().getId())
                .isEqualTo(user.getId());

        assertThat(membership.getBusiness().getId())
                .isNotNull();

        assertThat(membership.getRole())
                .isEqualTo(StaffRole.OWNER);

        assertThat(membership.getBranch())
                .isNull();

        assertThat(membership.isActive())
                .isTrue();
    }

    @Test
    void shouldRejectBlankBusinessName()
            throws Exception {

        createUser(
                "owner@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "owner@example.com",
                "password123"
        );

        String requestBody = """
                {
                    "name": "",
                    "description": "Medical clinic"
                }
                """;

        mockMvc.perform(
                        post("/api/v1/businesses")
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
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.status")
                        .value(400))
                .andExpect(jsonPath("$.error")
                        .value("Bad Request"))
                .andExpect(jsonPath("$.message")
                        .value("Request validation failed"))
                .andExpect(jsonPath("$.path")
                        .value("/api/v1/businesses"))
                .andExpect(jsonPath("$.validationErrors.name")
                        .value("Business name is required"));

        assertThat(businessRepository.count())
                .isZero();

        assertThat(staffMembershipRepository.count())
                .isZero();
    }

    @Test
    void shouldGetBusinessById() throws Exception {

        Business business = businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{id}",
                                business.getId()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.id")
                        .value(business.getId()))
                .andExpect(jsonPath("$.name")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.description")
                        .value("Medical clinic"))
                .andExpect(jsonPath("$.createdAt").exists());
    }

    @Test
    void shouldReturnNotFoundWhenBusinessDoesNotExist()
            throws Exception {

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{id}",
                                999999L
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.error")
                        .value("Not Found"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Business not found with id: 999999"
                        ))
                .andExpect(jsonPath("$.path")
                        .value(
                                "/api/v1/businesses/999999"
                        ));
    }

    @Test
    void shouldGetAllBusinesses() throws Exception {

        businessRepository.save(
                new Business(
                        "QueueFlow Clinic",
                        "Medical clinic"
                )
        );

        businessRepository.save(
                new Business(
                        "QueueFlow Bank",
                        "Banking services"
                )
        );

        mockMvc.perform(get("/api/v1/businesses"))
                .andExpect(status().isOk())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.length()")
                        .value(2))
                .andExpect(jsonPath("$[0].id")
                        .isNumber())
                .andExpect(jsonPath("$[0].name")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$[1].id")
                        .isNumber())
                .andExpect(jsonPath("$[1].name")
                        .value("QueueFlow Bank"));
    }

    @Test
    void shouldReturnEmptyListWhenNoBusinessesExist()
            throws Exception {

        mockMvc.perform(get("/api/v1/businesses"))
                .andExpect(status().isOk())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$").isArray())
                .andExpect(jsonPath("$").isEmpty());
    }

    @Test
    void shouldRejectMalformedJson()
            throws Exception {

        createUser(
                "owner@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "owner@example.com",
                "password123"
        );

        String malformedJson = """
                {
                    "name": "QueueFlow Clinic",
                    "description": "Medical clinic
                }
                """;

        mockMvc.perform(
                        post("/api/v1/businesses")
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content(malformedJson)
                )
                .andExpect(status().isBadRequest())
                .andExpect(content()
                        .contentTypeCompatibleWith(
                                MediaType.APPLICATION_JSON
                        ))
                .andExpect(jsonPath("$.status")
                        .value(400))
                .andExpect(jsonPath("$.error")
                        .value("Bad Request"))
                .andExpect(jsonPath("$.message")
                        .value("Malformed JSON request"))
                .andExpect(jsonPath("$.path")
                        .value("/api/v1/businesses"))
                .andExpect(jsonPath("$.validationErrors")
                        .isEmpty());

        assertThat(businessRepository.count())
                .isZero();

        assertThat(staffMembershipRepository.count())
                .isZero();
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
                        "Owner",
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

    private String validBusinessRequest() {

        return """
                {
                    "name": "QueueFlow Clinic",
                    "description": "Medical clinic"
                }
                """;
    }
}