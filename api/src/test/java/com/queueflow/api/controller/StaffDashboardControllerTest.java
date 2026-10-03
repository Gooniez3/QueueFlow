package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.QueueEntry;
import com.queueflow.api.entity.QueueEntryStatus;
import com.queueflow.api.entity.QueueStatus;
import com.queueflow.api.entity.Service;
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
import java.time.OffsetDateTime;
import java.time.ZoneId;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class StaffDashboardControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

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
    void shouldReturnTodayDashboardWithCountsAndOrderedWaiting()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Service service = createService(
                branch,
                "Consultation"
        );

        Queue queue = createQueue(
                branch,
                service,
                "Consultation Queue",
                "A",
                today(branch)
        );

        QueueEntry waitingThree =
                createEntry(queue, service, 3);

        QueueEntry waitingOne =
                createEntry(queue, service, 1);

        QueueEntry called =
                createEntry(queue, service, 4);

        called.setStatus(QueueEntryStatus.CALLED);
        called.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(called);

        QueueEntry serving =
                createEntry(queue, service, 5);

        serving.setStatus(QueueEntryStatus.SERVING);
        serving.setCalledAt(OffsetDateTime.now().minusMinutes(2));
        serving.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(serving);

        String token = createMemberAndLogin(
                business,
                branch,
                "dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.businessDate")
                        .value(today(branch).toString()))
                .andExpect(jsonPath("$.queues.length()")
                        .value(1))
                .andExpect(jsonPath("$.queues[0].queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queues[0].name")
                        .value("Consultation Queue"))
                .andExpect(jsonPath("$.queues[0].status")
                        .value("OPEN"))
                .andExpect(jsonPath("$.queues[0].service.id")
                        .value(service.getId()))
                .andExpect(jsonPath("$.queues[0].service.name")
                        .value("Consultation"))
                .andExpect(jsonPath("$.queues[0].service.durationMinutes")
                        .value(20))
                .andExpect(jsonPath("$.queues[0].counts.waiting")
                        .value(2))
                .andExpect(jsonPath("$.queues[0].counts.called")
                        .value(1))
                .andExpect(jsonPath("$.queues[0].counts.serving")
                        .value(1))
                .andExpect(jsonPath("$.queues[0].called.entryId")
                        .value(called.getId()))
                .andExpect(jsonPath("$.queues[0].serving.entryId")
                        .value(serving.getId()))
                .andExpect(jsonPath("$.queues[0].waiting.length()")
                        .value(2))
                .andExpect(jsonPath("$.queues[0].waiting[0].entryId")
                        .value(waitingOne.getId()))
                .andExpect(jsonPath("$.queues[0].waiting[1].entryId")
                        .value(waitingThree.getId()));
    }

    @Test
    void shouldSupportSharedQueueAndNullOperationalState()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Queue queue = createQueue(
                branch,
                null,
                "Shared Queue",
                "S",
                today(branch)
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "shared-dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.queues.length()")
                        .value(1))
                .andExpect(jsonPath("$.queues[0].queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queues[0].service")
                        .doesNotExist())
                .andExpect(jsonPath("$.queues[0].serving")
                        .doesNotExist())
                .andExpect(jsonPath("$.queues[0].called")
                        .doesNotExist())
                .andExpect(jsonPath("$.queues[0].waiting.length()")
                        .value(0))
                .andExpect(jsonPath("$.queues[0].counts.waiting")
                        .value(0))
                .andExpect(jsonPath("$.queues[0].counts.called")
                        .value(0))
                .andExpect(jsonPath("$.queues[0].counts.serving")
                        .value(0));
    }

    @Test
    void shouldReturnOnlyQueuesForBranchLocalToday()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Pacific/Honolulu"
        );

        Service service = createService(
                branch,
                "Local Date Service"
        );

        Queue todayQueue = createQueue(
                branch,
                service,
                "Today Queue",
                "T",
                today(branch)
        );

        createQueue(
                branch,
                null,
                "Old Queue",
                "O",
                today(branch).minusDays(1)
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "local-date-dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.businessDate")
                        .value(today(branch).toString()))
                .andExpect(jsonPath("$.queues.length()")
                        .value(1))
                .andExpect(jsonPath("$.queues[0].queueId")
                        .value(todayQueue.getId()));
    }

    @Test
    void shouldRejectStaffAssignedToDifferentBranch()
            throws Exception {

        Business business = createBusiness();

        Branch firstBranch = createBranch(
                business,
                "Asia/Singapore"
        );

        Branch secondBranch = createBranch(
                business,
                "Asia/Singapore"
        );

        Service secondService =
                createService(
                        secondBranch,
                        "Second Branch Service"
                );

        createQueue(
                secondBranch,
                secondService,
                "Second Queue",
                "B",
                today(secondBranch)
        );

        String token = createMemberAndLogin(
                business,
                firstBranch,
                "wrong-branch-dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                secondBranch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }

    @Test
    void shouldRequireAuthentication()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldRejectMultipleCalledEntriesAsInvariantViolation()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Service service = createService(
                branch,
                "Invariant Service"
        );

        Queue queue = createQueue(
                branch,
                service,
                "Invariant Queue",
                "I",
                today(branch)
        );

        QueueEntry first = createEntry(
                queue,
                service,
                1
        );

        first.setStatus(QueueEntryStatus.CALLED);
        first.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(first);

        QueueEntry second = createEntry(
                queue,
                service,
                2
        );

        second.setStatus(QueueEntryStatus.CALLED);
        second.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(second);

        String token = createMemberAndLogin(
                business,
                branch,
                "invariant-dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));
    }

    @Test
    void shouldRejectMultipleServingEntriesAsInvariantViolation()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        Service service = createService(
                branch,
                "Serving Invariant Service"
        );

        Queue queue = createQueue(
                branch,
                service,
                "Serving Invariant Queue",
                "V",
                today(branch)
        );

        QueueEntry first = createEntry(
                queue,
                service,
                1
        );

        first.setStatus(QueueEntryStatus.SERVING);
        first.setCalledAt(OffsetDateTime.now().minusMinutes(2));
        first.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(first);

        QueueEntry second = createEntry(
                queue,
                service,
                2
        );

        second.setStatus(QueueEntryStatus.SERVING);
        second.setCalledAt(OffsetDateTime.now().minusMinutes(2));
        second.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(second);

        String token = createMemberAndLogin(
                business,
                branch,
                "serving-invariant-dashboard@example.com"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/staff/dashboard",
                                business.getId(),
                                branch.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));
    }
    private LocalDate today(
            Branch branch
    ) {
        return LocalDate.now(
                ZoneId.of(branch.getTimezone())
        );
    }

    private Business createBusiness() {

        return businessRepository.save(
                new Business(
                        "QueueFlow Dashboard Test",
                        "Dashboard test business"
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
                        "Main Branch",
                        "1 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                );

        branch.setTimezone(timezone);

        return branchRepository.save(branch);
    }

    private Service createService(
            Branch branch,
            String name
    ) {

        Service service =
                new Service(
                        branch,
                        name,
                        "Dashboard service",
                        20
                );

        return serviceRepository.save(service);
    }

    private Queue createQueue(
            Branch branch,
            Service service,
            String name,
            String prefix,
            LocalDate businessDate
    ) {

        Queue queue =
                new Queue(
                        branch,
                        service,
                        name,
                        businessDate,
                        prefix
                );

        queue.setStatus(QueueStatus.OPEN);

        return queueRepository.save(queue);
    }

    private QueueEntry createEntry(
            Queue queue,
            Service service,
            int sequence
    ) {

        QueueEntry entry =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        sequence,
                        "guest-hash-"
                                + queue.getId()
                                + "-"
                                + sequence
                );

        return queueEntryRepository.save(entry);
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

