package com.queueflow.api.controller;

import com.queueflow.api.entity.AuthSession;
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
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.time.OffsetDateTime;
import java.util.HexFormat;
import java.util.List;
import java.util.Optional;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.content;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class AuthControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

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
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void setUp() {
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
    void shouldRegisterUser() throws Exception {

        mockMvc.perform(post("/api/v1/auth/register")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "staff@example.com",
                                    "password": "password123",
                                    "firstName": "Queue",
                                    "lastName": "Staff",
                                    "phone": "12345678"
                                }
                                """))
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.email")
                        .value("staff@example.com"))
                .andExpect(jsonPath("$.firstName")
                        .value("Queue"))
                .andExpect(jsonPath("$.lastName")
                        .value("Staff"))
                .andExpect(jsonPath("$.phone")
                        .value("12345678"))
                .andExpect(jsonPath("$.id").isNumber());
    }

    @Test
    void shouldStoreHashedPassword() throws Exception {

        String rawPassword = "password123";

        mockMvc.perform(post("/api/v1/auth/register")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "secure@example.com",
                                    "password": "password123",
                                    "firstName": "Secure",
                                    "lastName": "User"
                                }
                                """))
                .andExpect(status().isCreated());

        Optional<UserAccount> savedUser =
                userAccountRepository.findByEmailIgnoreCase(
                        "secure@example.com"
                );

        assertThat(savedUser).isPresent();

        assertThat(savedUser.get().getPasswordHash())
                .isNotEqualTo(rawPassword);

        assertThat(passwordEncoder.matches(
                rawPassword,
                savedUser.get().getPasswordHash()
        )).isTrue();
    }

    @Test
    void shouldRejectInvalidRegistration()
            throws Exception {

        mockMvc.perform(post("/api/v1/auth/register")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "not-an-email",
                                    "password": "short",
                                    "firstName": "",
                                    "lastName": ""
                                }
                                """))
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message")
                        .value("Request validation failed"));
    }

    @Test
    void shouldRejectDuplicateEmail()
            throws Exception {

        String requestBody = """
                {
                    "email": "duplicate@example.com",
                    "password": "password123",
                    "firstName": "Queue",
                    "lastName": "User"
                }
                """;

        mockMvc.perform(post("/api/v1/auth/register")
                        .contentType("application/json")
                        .content(requestBody))
                .andExpect(status().isCreated());

        mockMvc.perform(post("/api/v1/auth/register")
                        .contentType("application/json")
                        .content(requestBody))
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.error")
                        .value("Conflict"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "An account with this email already exists"
                        ));
    }

    @Test
    void shouldLoginWithValidCredentials()
            throws Exception {

        createUser(
                "login@example.com",
                "password123"
        );

        mockMvc.perform(post("/api/v1/auth/login")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "login@example.com",
                                    "password": "password123"
                                }
                                """))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.token")
                        .isNotEmpty())
                .andExpect(jsonPath("$.tokenType")
                        .value("Bearer"))
                .andExpect(jsonPath("$.expiresAt")
                        .isNotEmpty())
                .andExpect(jsonPath("$.user.email")
                        .value("login@example.com"))
                .andExpect(jsonPath("$.user.firstName")
                        .value("Queue"))
                .andExpect(jsonPath("$.user.lastName")
                        .value("Staff"))
                .andExpect(jsonPath("$.memberships")
                        .isArray());
    }

    @Test
    void shouldStoreOnlyHashedLoginToken()
            throws Exception {

        createUser(
                "token@example.com",
                "password123"
        );

        MvcResult result = mockMvc.perform(
                        post("/api/v1/auth/login")
                                .contentType("application/json")
                                .content("""
                                        {
                                            "email": "token@example.com",
                                            "password": "password123"
                                        }
                                        """))
                .andExpect(status().isOk())
                .andReturn();

        String responseBody =
                result.getResponse().getContentAsString();

        String rawToken = extractJsonString(
                responseBody,
                "token"
        );

        List<AuthSession> sessions =
                authSessionRepository.findAll();

        assertThat(sessions).hasSize(1);

        AuthSession session =
                sessions.getFirst();

        assertThat(session.getTokenHash())
                .isNotEqualTo(rawToken);

        assertThat(session.getTokenHash())
                .isEqualTo(sha256(rawToken));
    }

    @Test
    void shouldRejectWrongPassword()
            throws Exception {

        createUser(
                "wrong@example.com",
                "password123"
        );

        mockMvc.perform(post("/api/v1/auth/login")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "wrong@example.com",
                                    "password": "wrong-password"
                                }
                                """))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.status")
                        .value(401))
                .andExpect(jsonPath("$.error")
                        .value("Unauthorized"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Invalid email or password"
                        ));
    }

    @Test
    void shouldRejectUnknownEmail()
            throws Exception {

        mockMvc.perform(post("/api/v1/auth/login")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "missing@example.com",
                                    "password": "password123"
                                }
                                """))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.status")
                        .value(401))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Invalid email or password"
                        ));
    }

    @Test
    void loginResponseShouldNotContainPassword()
            throws Exception {

        createUser(
                "safe@example.com",
                "password123"
        );

        mockMvc.perform(post("/api/v1/auth/login")
                        .contentType("application/json")
                        .content("""
                                {
                                    "email": "safe@example.com",
                                    "password": "password123"
                                }
                                """))
                .andExpect(status().isOk())
                .andExpect(content().string(
                        org.hamcrest.Matchers.not(
                                org.hamcrest.Matchers.containsString(
                                        "password123"
                                )
                        )
                ));
    }

    @Test
    void shouldReturnCurrentUserWithValidBearerToken()
            throws Exception {

        createUser(
                "me@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "me@example.com",
                "password123"
        );

        mockMvc.perform(get("/api/v1/auth/me")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.user.email")
                        .value("me@example.com"))
                .andExpect(jsonPath("$.user.firstName")
                        .value("Queue"))
                .andExpect(jsonPath("$.user.lastName")
                        .value("Staff"))
                .andExpect(jsonPath("$.memberships")
                        .isArray());
    }

    @Test
    void shouldRejectMeWithoutBearerToken()
            throws Exception {

        mockMvc.perform(
                        get("/api/v1/auth/me")
                )
                .andExpect(
                        status().isUnauthorized()
                );
    }

    @Test
    void shouldRejectMeWithInvalidBearerToken()
            throws Exception {

        mockMvc.perform(get("/api/v1/auth/me")
                        .header(
                                "Authorization",
                                "Bearer invalid-token"
                        ))
                .andExpect(
                        status().isUnauthorized()
                );
    }

    @Test
    void shouldLogoutWithValidBearerToken()
            throws Exception {

        createUser(
                "logout@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "logout@example.com",
                "password123"
        );

        mockMvc.perform(post("/api/v1/auth/logout")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(status().isOk());

        List<AuthSession> sessions =
                authSessionRepository.findAll();

        assertThat(sessions).hasSize(1);

        assertThat(
                sessions
                        .getFirst()
                        .getRevokedAt()
        ).isNotNull();
    }

    @Test
    void shouldRejectRevokedTokenAfterLogout()
            throws Exception {

        createUser(
                "revoked@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "revoked@example.com",
                "password123"
        );

        mockMvc.perform(post("/api/v1/auth/logout")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(status().isOk());

        mockMvc.perform(get("/api/v1/auth/me")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(
                        status().isUnauthorized()
                );
    }

    @Test
    void shouldReturnStaffMembershipWithCurrentUser()
            throws Exception {

        UserAccount user = createUser(
                "member@example.com",
                "password123"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Test Business",
                                "Authentication membership test"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Main Branch",
                                "123 Test Street",
                                null,
                                null
                        )
                );

        StaffMembership membership =
                new StaffMembership(
                        user,
                        business,
                        branch,
                        StaffRole.STAFF
                );

        staffMembershipRepository.save(
                membership
        );

        String token = loginAndGetToken(
                "member@example.com",
                "password123"
        );

        mockMvc.perform(get("/api/v1/auth/me")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.user.email")
                        .value("member@example.com"))
                .andExpect(jsonPath(
                        "$.memberships.length()"
                ).value(1))
                .andExpect(jsonPath(
                        "$.memberships[0].businessId"
                ).value(business.getId()))
                .andExpect(jsonPath(
                        "$.memberships[0].branchId"
                ).value(branch.getId()))
                .andExpect(jsonPath(
                        "$.memberships[0].role"
                ).value("STAFF"));
    }

    @Test
    void shouldRejectExpiredBearerToken()
            throws Exception {

        createUser(
                "expired@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "expired@example.com",
                "password123"
        );

        List<AuthSession> sessions =
                authSessionRepository.findAll();

        assertThat(sessions).hasSize(1);

        AuthSession session =
                sessions.getFirst();

        session.setExpiresAt(
                OffsetDateTime.now().minusMinutes(1)
        );

        authSessionRepository.saveAndFlush(
                session
        );

        mockMvc.perform(get("/api/v1/auth/me")
                        .header(
                                "Authorization",
                                "Bearer " + token
                        ))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.status")
                        .value(401))
                .andExpect(jsonPath("$.error")
                        .value("Unauthorized"))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Authentication is required"
                        ));
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
                                                "application/json"
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
                        .andExpect(
                                status().isOk()
                        )
                        .andReturn();

        return extractJsonString(
                result
                        .getResponse()
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

    private String sha256(String value)
            throws Exception {

        MessageDigest digest =
                MessageDigest.getInstance(
                        "SHA-256"
                );

        byte[] hash =
                digest.digest(
                        value.getBytes(
                                StandardCharsets.UTF_8
                        )
                );

        return HexFormat
                .of()
                .formatHex(hash);
    }
}