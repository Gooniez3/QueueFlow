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
import org.springframework.test.context.TestContext;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;

import static org.assertj.core.api.Assertions.assertThat;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class QueueStaffControllerTest {

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

    // =========================================================
    // CALL NEXT
    // =========================================================

    @Test
    void shouldRequireAuthenticationToCallNext()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        createEntry(
                queue,
                service,
                1
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
                        )
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldCallOldestWaitingEntry()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry first = createEntry(
                queue,
                service,
                1
        );

        QueueEntry second = createEntry(
                queue,
                service,
                2
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "staff@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.entryId")
                        .value(first.getId()))
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("CALLED"))
                .andExpect(jsonPath("$.calledAt")
                        .isNotEmpty());

        QueueEntry updatedFirst =
                queueEntryRepository
                        .findById(first.getId())
                        .orElseThrow();

        QueueEntry unchangedSecond =
                queueEntryRepository
                        .findById(second.getId())
                        .orElseThrow();

        assertThat(updatedFirst.getStatus())
                .isEqualTo(QueueEntryStatus.CALLED);

        assertThat(updatedFirst.getCalledAt())
                .isNotNull();

        assertThat(unchangedSecond.getStatus())
                .isEqualTo(QueueEntryStatus.WAITING);

        assertThat(unchangedSecond.getCalledAt())
                .isNull();
    }

    @Test
    void shouldSkipAlreadyCalledEntryWhenCallingNext()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
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

        String token = createMemberAndLogin(
                business,
                branch,
                "next@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.entryId")
                        .value(second.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(2))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A002"))
                .andExpect(jsonPath("$.status")
                        .value("CALLED"));
    }

    @Test
    void shouldRejectCallNextWhenQueueIsPaused()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.PAUSED
        );

        createEntry(
                queue,
                service,
                1
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "paused@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
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
    void shouldRejectCallNextWhenQueueIsClosed()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.CLOSED
        );

        createEntry(
                queue,
                service,
                1
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "closed@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
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
    void shouldRejectCallNextWhenNoWaitingEntriesExist()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "empty@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
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
    void shouldRejectUserWithoutBusinessMembership()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        createEntry(
                queue,
                service,
                1
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
                                "/api/v1/queues/{queueId}/staff/call-next",
                                queue.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }

    @Test
    void shouldReturnNotFoundForUnknownQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        String token = createMemberAndLogin(
                business,
                branch,
                "missing@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/call-next",
                                999999L
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404));
    }

    // =========================================================
    // START SERVING
    // =========================================================

    @Test
    void shouldStartCalledEntryServing()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "start@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
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
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("SERVING"))
                .andExpect(jsonPath("$.calledAt")
                        .isNotEmpty())
                .andExpect(jsonPath("$.servingAt")
                        .isNotEmpty());

        QueueEntry updated =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(updated.getStatus())
                .isEqualTo(QueueEntryStatus.SERVING);

        assertThat(updated.getServingAt())
                .isNotNull();

        assertThat(updated.getCompletedAt())
                .isNull();
    }

    @Test
    void shouldRequireAuthenticationToStartServing()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
                                queue.getId(),
                                entry.getId()
                        )
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldRejectWaitingEntryWhenStartingServing()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "waiting-start@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.WAITING);

        assertThat(unchanged.getServingAt())
                .isNull();
    }

    @Test
    void shouldRejectAlreadyServingEntryWhenStartingServing()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "already-serving@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
                                queue.getId(),
                                entry.getId()
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
    void shouldRejectStartServingForEntryFromDifferentQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        Service firstService = createService(
                branch,
                "First Service"
        );

        Service secondService = createService(
                branch,
                "Second Service"
        );

        Queue firstQueue = createQueue(
                branch,
                firstService,
                QueueStatus.OPEN
        );

        Queue secondQueue = createQueue(
                branch,
                secondService,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                secondQueue,
                secondService,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "wrong-queue@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
                                firstQueue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.CALLED);

        assertThat(unchanged.getServingAt())
                .isNull();
    }

    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenStartingServing()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        createUser(
                "start-outsider@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "start-outsider@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/start",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }

        // =========================================================
    // COMPLETE SERVING
    // =========================================================

    @Test
    void shouldCompleteServingEntry()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "complete@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
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
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("COMPLETED"))
                .andExpect(jsonPath("$.calledAt")
                        .isNotEmpty())
                .andExpect(jsonPath("$.servingAt")
                        .isNotEmpty())
                .andExpect(jsonPath("$.completedAt")
                        .isNotEmpty());

        QueueEntry updated =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(updated.getStatus())
                .isEqualTo(QueueEntryStatus.COMPLETED);

        assertThat(updated.getCompletedAt())
                .isNotNull();

        assertThat(updated.getCancelledAt())
                .isNull();
    }

    @Test
    void shouldRequireAuthenticationToCompleteEntry()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
                                queue.getId(),
                                entry.getId()
                        )
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldRejectCalledEntryWhenCompleting()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "called-complete@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.CALLED);

        assertThat(unchanged.getCompletedAt())
                .isNull();
    }

    @Test
    void shouldRejectAlreadyCompletedEntryWhenCompleting()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.COMPLETED);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        entry.setCompletedAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "already-completed@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
                                queue.getId(),
                                entry.getId()
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
    void shouldRejectCompleteForEntryFromDifferentQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        Service firstService = createService(
                branch,
                "Complete First Service"
        );

        Service secondService = createService(
                branch,
                "Complete Second Service"
        );

        Queue firstQueue = createQueue(
                branch,
                firstService,
                QueueStatus.OPEN
        );

        Queue secondQueue = createQueue(
                branch,
                secondService,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                secondQueue,
                secondService,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "complete-wrong-queue@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
                                firstQueue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.SERVING);

        assertThat(unchanged.getCompletedAt())
                .isNull();
    }

    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenCompleting()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        createUser(
                "complete-outsider@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "complete-outsider@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/complete",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }

        // =========================================================
    // SKIP CALLED ENTRY
    // =========================================================

    @Test
    void shouldSkipCalledEntry()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "skip@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
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
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("SKIPPED"))
                .andExpect(jsonPath("$.calledAt")
                        .isNotEmpty());

        QueueEntry updated =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(updated.getStatus())
                .isEqualTo(QueueEntryStatus.SKIPPED);

        assertThat(updated.getServingAt())
                .isNull();

        assertThat(updated.getCompletedAt())
                .isNull();

        assertThat(updated.getCancelledAt())
                .isNull();
    }

    @Test
    void shouldRequireAuthenticationToSkipEntry()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
                                queue.getId(),
                                entry.getId()
                        )
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void shouldRejectWaitingEntryWhenSkipping()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "waiting-skip@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.WAITING);
    }

    @Test
    void shouldRejectServingEntryWhenSkipping()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.SERVING);
        entry.setCalledAt(OffsetDateTime.now());
        entry.setServingAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "serving-skip@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.SERVING);
    }

    @Test
    void shouldRejectSkipForEntryFromDifferentQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        Service firstService = createService(
                branch,
                "Skip First Service"
        );

        Service secondService = createService(
                branch,
                "Skip Second Service"
        );

        Queue firstQueue = createQueue(
                branch,
                firstService,
                QueueStatus.OPEN
        );

        Queue secondQueue = createQueue(
                branch,
                secondService,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                secondQueue,
                secondService,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "skip-wrong-queue@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
                                firstQueue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status")
                        .value(400));

        QueueEntry unchanged =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(unchanged.getStatus())
                .isEqualTo(QueueEntryStatus.CALLED);
    }

    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenSkipping()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        createUser(
                "skip-outsider@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "skip-outsider@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/skip",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }
    // =========================================================
    // RECALL
    // =========================================================

    @Test
    void shouldRecallCalledEntry() throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        OffsetDateTime originalCalledAt =
                OffsetDateTime.now().minusMinutes(5);

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(originalCalledAt);
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "recall@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/recall",
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
                .andExpect(jsonPath("$.ticketSequence")
                        .value(1))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("CALLED"));

        QueueEntry updated =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(updated.getStatus())
                .isEqualTo(QueueEntryStatus.CALLED);

        assertThat(updated.getTicketSequence())
                .isEqualTo(1);

        assertThat(updated.getCalledAt())
                .isAfter(originalCalledAt);
    }

    @Test
    void shouldRejectRecallWhenEntryIsNotCalled()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        String token = createMemberAndLogin(
                business,
                branch,
                "recall-waiting@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/recall",
                                queue.getId(),
                                entry.getId()
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
    void shouldRejectRecallForEntryFromDifferentQueue()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);

        Service firstService = createService(
                branch,
                "Recall First Service"
        );

        Service secondService = createService(
                branch,
                "Recall Second Service"
        );

        Queue firstQueue = createQueue(
                branch,
                firstService,
                QueueStatus.OPEN
        );

        Queue secondQueue = createQueue(
                branch,
                secondService,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                secondQueue,
                secondService,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(
                OffsetDateTime.now().minusMinutes(5)
        );
        queueEntryRepository.save(entry);

        String token = createMemberAndLogin(
                business,
                branch,
                "recall-wrong-queue@example.com"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/recall",
                                firstQueue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isNotFound());
    }

    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenRecalling()
            throws Exception {

        Business business = createBusiness();
        Branch branch = createBranch(business);
        Service service = createService(branch);

        Queue queue = createQueue(
                branch,
                service,
                QueueStatus.OPEN
        );

        QueueEntry entry = createEntry(
                queue,
                service,
                1
        );

        entry.setStatus(QueueEntryStatus.CALLED);
        entry.setCalledAt(OffsetDateTime.now());
        queueEntryRepository.save(entry);

        createUser(
                "recall-outsider@example.com",
                "password123"
        );

        String token = loginAndGetToken(
                "recall-outsider@example.com",
                "password123"
        );

        mockMvc.perform(
                        post(
                                "/api/v1/queues/{queueId}/staff/entries/{entryId}/recall",
                                queue.getId(),
                                entry.getId()
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                )
                .andExpect(status().isForbidden());
    }

    // =========================================================
// PAUSE QUEUE
// =========================================================

    @Test
    void shouldPauseOpenQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "pause@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/pause",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.status")
                    .value("PAUSED"));

    Queue updated =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(updated.getStatus())
            .isEqualTo(QueueStatus.PAUSED);
 }
    @Test
    void shouldRejectPausingAlreadyPausedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.PAUSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "pause-already-paused@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/pause",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409));

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.PAUSED);
   }
    @Test
    void shouldRejectPausingClosedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.CLOSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "pause-closed@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/pause",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409));

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.CLOSED);
  }
    @Test
    void shouldRequireAuthenticationToPauseQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/pause",
                            queue.getId()
                    )
            )
            .andExpect(status().isUnauthorized());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.OPEN);
  }
    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenPausingQueue()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    createUser(
            "pause-outsider@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "pause-outsider@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/pause",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isForbidden());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.OPEN);
    }

    // =========================================================
// RESUME QUEUE
// =========================================================

    @Test
    void shouldResumePausedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.PAUSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "resume@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/resume",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.status")
                    .value("OPEN"));

    Queue updated =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(updated.getStatus())
            .isEqualTo(QueueStatus.OPEN);
   }
    @Test
    void shouldRejectResumingOpenQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "resume-open@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/resume",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409));

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.OPEN);
   }
    @Test
    void shouldRejectResumingClosedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.CLOSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "resume-closed@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/resume",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409));

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.CLOSED);
   }
    @Test
    void shouldRequireAuthenticationToResumeQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.PAUSED
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/resume",
                            queue.getId()
                    )
            )
            .andExpect(status().isUnauthorized());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.PAUSED);
 }
    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenResumingQueue()
        throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.PAUSED
    );

    createUser(
            "resume-outsider@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "resume-outsider@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/resume",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isForbidden());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.PAUSED);
   }
    // =========================================================
// CLOSE QUEUE
// =========================================================

    @Test
    void shouldCloseOpenQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "close-open@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/close",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.status")
                    .value("CLOSED"))
            .andExpect(jsonPath("$.closedAt")
                    .isNotEmpty());

    Queue updated =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(updated.getStatus())
            .isEqualTo(QueueStatus.CLOSED);

    assertThat(updated.getClosedAt())
            .isNotNull();
 }
    @Test
    void shouldClosePausedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.PAUSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "close-paused@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/close",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.status")
                    .value("CLOSED"))
            .andExpect(jsonPath("$.closedAt")
                    .isNotEmpty());

    Queue updated =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(updated.getStatus())
            .isEqualTo(QueueStatus.CLOSED);

    assertThat(updated.getClosedAt())
            .isNotNull();
  }
    @Test
    void shouldRejectClosingAlreadyClosedQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.CLOSED
    );

    String token = createMemberAndLogin(
            business,
            branch,
            "close-already-closed@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/close",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409));

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.CLOSED);
 }
    @Test
    void shouldRequireAuthenticationToCloseQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/close",
                            queue.getId()
                    )
            )
            .andExpect(status().isUnauthorized());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.OPEN);

    assertThat(unchanged.getClosedAt())
            .isNull();
  }
    @Test
    void shouldRejectUserWithoutBusinessMembershipWhenClosingQueue() throws Exception {

    Business business = createBusiness();
    Branch branch = createBranch(business);
    Service service = createService(branch);

    Queue queue = createQueue(
            branch,
            service,
            QueueStatus.OPEN
    );

    createUser(
            "close-outsider@example.com",
            "password123"
    );

    String token = loginAndGetToken(
            "close-outsider@example.com",
            "password123"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/close",
                            queue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isForbidden());

    Queue unchanged =
            queueRepository
                    .findById(queue.getId())
                    .orElseThrow();

    assertThat(unchanged.getStatus())
            .isEqualTo(QueueStatus.OPEN);

    assertThat(unchanged.getClosedAt())
            .isNull();
  }
    @Test
    void shouldRejectCallNextFromStaffAssignedToDifferentBranch() throws Exception {

    Business business = createBusiness();

    Branch firstBranch = createBranch(business);
    Branch secondBranch = createBranch(business);

    Service secondService = createService(
            secondBranch,
            "Second Branch Service"
    );

    Queue secondQueue = createQueue(
            secondBranch,
            secondService,
            QueueStatus.OPEN
    );

    createEntry(
            secondQueue,
            secondService,
            1
    );

    String token = createMemberAndLogin(
            business,
            firstBranch,
            "different-branch-staff@example.com"
    );

    mockMvc.perform(
                    post(
                            "/api/v1/queues/{queueId}/staff/call-next",
                            secondQueue.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isForbidden());
  }
    // =========================================================
    // HELPERS
    // =========================================================

    private Business createBusiness() {

        Business business =
                new Business(
                        "QueueFlow Test Business",
                        "Test business"
                );

        return businessRepository.save(business);
    }

    private Branch createBranch(
            Business business
    ) {

        Branch branch =
                new Branch(
                        business,
                        "Main Branch",
                        "1 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                );

        branch.setTimezone("Asia/Singapore");

        return branchRepository.save(branch);
    }

    private Service createService(
            Branch branch
    ) {

        return createService(
                branch,
                "General Service"
        );
    }

    private Service createService(
            Branch branch,
            String name
    ) {

        Service service =
                new Service(
                        branch,
                        name,
                        "General queue service",
                        20
                );

        return serviceRepository.save(service);
    }

    private Queue createQueue(
            Branch branch,
            Service service,
            QueueStatus status
    ) {

        Queue queue =
                new Queue(
                        branch,
                        service,
                        "Main Queue",
                        LocalDate.now(),
                        "A"
                );

        queue.setStatus(status);

        return queueRepository.save(queue);
    }

    private QueueEntry createEntry(
            Queue queue,
            Service service,
            int ticketSequence
    ) {

        QueueEntry entry =
                new QueueEntry(
                        queue,
                        service,
                        null,
                        ticketSequence,
                        "guest-hash-"
                                + queue.getId()
                                + "-"
                                + ticketSequence
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
