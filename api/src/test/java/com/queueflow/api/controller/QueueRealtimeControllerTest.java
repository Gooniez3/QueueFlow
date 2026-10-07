package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.StaffMutationIdempotencyRepository;
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
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.request;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class QueueRealtimeControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

    @Autowired
    private StaffMutationIdempotencyRepository staffMutationIdempotencyRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        staffMutationIdempotencyRepository.deleteAll();
        guestJoinIdempotencyRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldRejectUnauthenticatedQueueSubscription()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Queue queue = createQueue(branch);

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/events",
                                queue.getId()
                        )
                                .accept(
                                        MediaType.TEXT_EVENT_STREAM
                                )
                )
                .andExpect(status().isUnauthorized())
                .andExpect(request().asyncNotStarted());
    }

    @Test
    void shouldAllowAuthorizedStaffSubscription()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Queue queue = createQueue(branch);

        String token =
                createMemberAndLogin(
                        business,
                        branch,
                        "queue-staff@example.com"
                );

        MvcResult result =
                mockMvc.perform(
                                get(
                                        "/api/v1/queues/{queueId}/events",
                                        queue.getId()
                                )
                                        .header(
                                                "Authorization",
                                                "Bearer " + token
                                        )
                                        .accept(
                                                MediaType.TEXT_EVENT_STREAM
                                        )
                        )
                        .andExpect(status().isOk())
                        .andExpect(request().asyncStarted())
                        .andReturn();

        assertThat(
                result.getResponse()
                        .getContentType()
        ).startsWith(
                MediaType.TEXT_EVENT_STREAM_VALUE
        );
    }

    @Test
    void shouldRejectStaffWithoutBranchAccess()
            throws Exception {

        Business business = createBusiness();

        Branch allowedBranch = createBranch(
                business,
                "Asia/Singapore"
        );

        Branch otherBranch = createBranch(
                business,
                "Asia/Singapore"
        );

        Queue queue = createQueue(otherBranch);

        String token =
                createMemberAndLogin(
                        business,
                        allowedBranch,
                        "restricted-staff@example.com"
                );

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/events",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .accept(
                                        MediaType.TEXT_EVENT_STREAM
                                )
                )
                .andExpect(status().isForbidden())
                .andExpect(request().asyncNotStarted());
    }

    @Test
    void shouldReturnNotFoundForUnknownQueueForAuthorizedStaff()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        String token =
                createMemberAndLogin(
                        business,
                        branch,
                        "notfound-staff@example.com"
                );

        mockMvc.perform(
                        get(
                                "/api/v1/queues/{queueId}/events",
                                999999L
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .accept(
                                        MediaType.TEXT_EVENT_STREAM
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(request().asyncNotStarted());
    }

    private Queue createQueue(
            Branch branch
    ) {
        return queueRepository.save(
                new Queue(
                        branch,
                        null,
                        "Main Queue",
                        LocalDate.now(
                                ZoneId.of(
                                        branch.getTimezone()
                                )
                        ),
                        "A"
                )
        );
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
            Branch branch,
            String email
    ) throws Exception {

        UserAccount user =
                createUser(
                        email,
                        "password123"
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        user,
                        business,
                        branch,
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
                        passwordEncoder.encode(rawPassword),
                        "Test",
                        "User",
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
                                        ))
                        )
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
            String field
    ) {

        String search =
                "\"" + field + "\":\"";

        int start =
                json.indexOf(search);

        if (start < 0) {
            throw new IllegalStateException(
                    "Field not found in JSON: " + field
            );
        }

        start += search.length();

        int end =
                json.indexOf("\"", start);

        return json.substring(
                start,
                end
        );
    }
}
