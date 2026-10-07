package com.queueflow.api.controller;

import com.queueflow.api.entity.*;
import com.queueflow.api.repository.*;
import com.queueflow.api.security.AuthTokenService;
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
import java.util.List;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class QueueEntryQrControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private QueueEntryQrCredentialRepository qrCredentialRepository;

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

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        qrCredentialRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldGenerateUniqueCredentialAndPersistOnlyHash()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "customer@example.com",
                        "password123"
                );

        String firstCredential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        List<QueueEntryQrCredential> firstRecords =
                qrCredentialRepository.findAll();

        assertThat(firstRecords).hasSize(1);

        QueueEntryQrCredential firstRecord =
                firstRecords.getFirst();

        assertThat(firstRecord.getCredentialHash())
                .isEqualTo(
                        authTokenService.hashToken(
                                firstCredential
                        )
                );

        assertThat(firstRecord.getCredentialHash())
                .isNotEqualTo(firstCredential);

        assertThat(firstRecord.getCredentialHash())
                .hasSize(64);

        String secondCredential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        assertThat(secondCredential)
                .isNotEqualTo(firstCredential);

        List<QueueEntryQrCredential> records =
                qrCredentialRepository.findAll();

        assertThat(records).hasSize(2);

        assertThat(
                records.stream()
                        .map(
                                QueueEntryQrCredential::getCredentialHash
                        )
        ).doesNotContain(
                firstCredential,
                secondCredential
        );

        assertThat(
                records.stream()
                        .filter(record ->
                                record.getRevokedAt() == null
                        )
        ).hasSize(1);
    }

    @Test
    void shouldVerifyValidCredentialForAuthorizedStaff()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "qr-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "qr-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.entryId")
                        .value(entry.getId()))
                .andExpect(jsonPath("$.queueId")
                        .value(setup.queue().getId()))
                .andExpect(jsonPath("$.businessId")
                        .value(setup.business().getId()))
                .andExpect(jsonPath("$.branchId")
                        .value(setup.branch().getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(setup.service().getId()))
                .andExpect(jsonPath("$.ticketNumber")
                        .value("A001"))
                .andExpect(jsonPath("$.status")
                        .value("WAITING"))
                .andExpect(jsonPath("$.credential")
                        .doesNotExist());
    }

    @Test
    void shouldRejectInvalidCredential()
            throws Exception {

        Setup setup = createSetup();

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "invalid-qr-staff@example.com"
                );

        String invalidCredential =
                authTokenService.generateToken();

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        invalidCredential
                                ))
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.message")
                        .value("QR credential not found"));
    }

    @Test
    void shouldRejectStaffFromWrongBranch()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "branch-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "branch-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        Branch otherBranch =
                createBranch(
                        setup.business(),
                        "Other Branch"
                );

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        otherBranch,
                        "other-branch-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isForbidden());
    }

    @Test
    void shouldRejectRevokedCredential()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "revoked-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "revoked-customer@example.com",
                        "password123"
                );

        String oldCredential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        issueCredential(
                setup.queue().getId(),
                entry.getId(),
                customerToken
        );

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "revoked-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        oldCredential
                                ))
                )
                .andExpect(status().isGone())
                .andExpect(jsonPath("$.message")
                        .value(
                                "QR credential has been revoked"
                        ));
    }

    @Test
    void shouldRejectExpiredCredential()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "expired-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "expired-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        QueueEntryQrCredential record =
                qrCredentialRepository
                        .findByCredentialHash(
                                authTokenService.hashToken(
                                        credential
                                )
                        )
                        .orElseThrow();

        record.setExpiresAt(
                OffsetDateTime.now()
                        .minusMinutes(1)
        );

        qrCredentialRepository.saveAndFlush(
                record
        );

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "expired-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isGone())
                .andExpect(jsonPath("$.message")
                        .value(
                                "QR credential has expired"
                        ));
    }

    @Test
    void shouldRejectTerminalTicket()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "terminal-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "terminal-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        entry.setStatus(
                QueueEntryStatus.COMPLETED
        );

        entry.setCompletedAt(
                OffsetDateTime.now()
        );

        queueEntryRepository.saveAndFlush(
                entry
        );

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "terminal-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "QR verification is unavailable for terminal tickets"
                        ));
    }

    @Test
    void shouldNotMutateQueueEntryWhenVerifying()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "readonly-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "readonly-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        QueueEntry before =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        QueueEntryStatus statusBefore =
                before.getStatus();

        OffsetDateTime calledBefore =
                before.getCalledAt();

        OffsetDateTime servingBefore =
                before.getServingAt();

        OffsetDateTime completedBefore =
                before.getCompletedAt();

        OffsetDateTime cancelledBefore =
                before.getCancelledAt();

        Integer nextSequenceBefore =
                queueRepository
                        .findById(
                                setup.queue().getId()
                        )
                        .orElseThrow()
                        .getNextTicketSequence();

        String staffToken =
                createStaffAndLogin(
                        setup.business(),
                        setup.branch(),
                        "readonly-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isOk());

        QueueEntry after =
                queueEntryRepository
                        .findById(entry.getId())
                        .orElseThrow();

        assertThat(after.getStatus())
                .isEqualTo(statusBefore);

        assertThat(after.getCalledAt())
                .isEqualTo(calledBefore);

        assertThat(after.getServingAt())
                .isEqualTo(servingBefore);

        assertThat(after.getCompletedAt())
                .isEqualTo(completedBefore);

        assertThat(after.getCancelledAt())
                .isEqualTo(cancelledBefore);

        assertThat(
                queueRepository
                        .findById(
                                setup.queue().getId()
                        )
                        .orElseThrow()
                        .getNextTicketSequence()
        ).isEqualTo(nextSequenceBefore);
    }


    @Test
    void shouldRejectStaffFromWrongBusiness()
            throws Exception {

        Setup setup = createSetup();

        UserAccount customer =
                createUser(
                        "wrong-business-customer@example.com",
                        "password123"
                );

        QueueEntry entry =
                createEntry(
                        setup.queue(),
                        setup.service(),
                        customer,
                        1
                );

        String customerToken =
                loginAndGetToken(
                        "wrong-business-customer@example.com",
                        "password123"
                );

        String credential =
                issueCredential(
                        setup.queue().getId(),
                        entry.getId(),
                        customerToken
                );

        Business otherBusiness =
                businessRepository.save(
                        new Business(
                                "Other Business",
                                "Different business"
                        )
                );

        Branch otherBranch =
                createBranch(
                        otherBusiness,
                        "Other Business Branch"
                );

        String staffToken =
                createStaffAndLogin(
                        otherBusiness,
                        otherBranch,
                        "wrong-business-staff@example.com"
                );

        mockMvc.perform(
                        post(
                                "/api/v1/staff/queue-entry-qr/verify"
                        )
                                .header(
                                        "Authorization",
                                        "Bearer " + staffToken
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "credential": "%s"
                                        }
                                        """.formatted(
                                        credential
                                ))
                )
                .andExpect(status().isForbidden());
    }

    @Test
    void shouldAllowGuestOwnerToIssueCredential()
            throws Exception {

        Setup setup = createSetup();

        String guestToken =
                authTokenService.generateToken();

        QueueEntry entry =
                new QueueEntry(
                        setup.queue(),
                        setup.service(),
                        null,
                        1,
                        authTokenService.hashToken(
                                guestToken
                        )
                );

        entry =
                queueEntryRepository.save(
                        entry
                );

        MvcResult result =
                mockMvc.perform(
                                post(
                                        "/api/v1/queues/{queueId}/entries/{entryId}/qr-credential",
                                        setup.queue().getId(),
                                        entry.getId()
                                )
                                        .header(
                                                "X-Guest-Token",
                                                guestToken
                                        )
                        )
                        .andExpect(status().isOk())
                        .andExpect(jsonPath("$.credential")
                                .isString())
                        .andExpect(jsonPath("$.expiresAt")
                                .exists())
                        .andReturn();

        String qrCredential =
                extractJsonString(
                        result.getResponse()
                                .getContentAsString(),
                        "credential"
                );

        QueueEntryQrCredential stored =
                qrCredentialRepository
                        .findAll()
                        .getFirst();

        assertThat(stored.getCredentialHash())
                .isEqualTo(
                        authTokenService.hashToken(
                                qrCredential
                        )
                );

        assertThat(stored.getCredentialHash())
                .isNotEqualTo(qrCredential);
    }
    private String issueCredential(
            Long queueId,
            Long entryId,
            String bearerToken
    ) throws Exception {

        MvcResult result =
                mockMvc.perform(
                                post(
                                        "/api/v1/queues/{queueId}/entries/{entryId}/qr-credential",
                                        queueId,
                                        entryId
                                )
                                        .header(
                                                "Authorization",
                                                "Bearer " + bearerToken
                                        )
                        )
                        .andExpect(status().isOk())
                        .andExpect(jsonPath("$.credential")
                                .isString())
                        .andExpect(jsonPath("$.expiresAt")
                                .exists())
                        .andReturn();

        return extractJsonString(
                result.getResponse()
                        .getContentAsString(),
                "credential"
        );
    }

    private Setup createSetup() {

        Business business =
                new Business(
                        "QR Test Business",
                        "QR verification test"
                );

        business =
                businessRepository.save(
                        business
                );

        Branch branch =
                createBranch(
                        business,
                        "Main Branch"
                );

        com.queueflow.api.entity.Service service =
                new com.queueflow.api.entity.Service(
                        branch,
                        "General Service",
                        "General queue service",
                        20
                );

        service =
                serviceRepository.save(
                        service
                );

        Queue queue =
                new Queue(
                        branch,
                        service,
                        "Main Queue",
                        LocalDate.now(),
                        "A"
                );

        queue.setStatus(
                QueueStatus.OPEN
        );

        queue =
                queueRepository.save(
                        queue
                );

        return new Setup(
                business,
                branch,
                service,
                queue
        );
    }

    private Branch createBranch(
            Business business,
            String name
    ) {

        Branch branch =
                new Branch(
                        business,
                        name,
                        "1 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                );

        branch.setTimezone(
                "Asia/Singapore"
        );

        return branchRepository.save(
                branch
        );
    }

    private QueueEntry createEntry(
            Queue queue,
            com.queueflow.api.entity.Service service,
            UserAccount user,
            int ticketSequence
    ) {

        QueueEntry entry =
                new QueueEntry(
                        queue,
                        service,
                        user,
                        ticketSequence,
                        null
                );

        return queueEntryRepository.save(
                entry
        );
    }

    private String createStaffAndLogin(
            Business business,
            Branch branch,
            String email
    ) throws Exception {

        UserAccount staff =
                createUser(
                        email,
                        "password123"
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        staff,
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
            String password
    ) {

        UserAccount user =
                new UserAccount(
                        email,
                        passwordEncoder.encode(
                                password
                        ),
                        "Test",
                        "User",
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
                                post(
                                        "/api/v1/auth/login"
                                )
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
                    "Field not found in JSON: "
                            + field
            );
        }

        start += search.length();

        int end =
                json.indexOf(
                        "\"",
                        start
                );

        return json.substring(
                start,
                end
        );
    }

    private record Setup(
            Business business,
            Branch branch,
            com.queueflow.api.entity.Service service,
            Queue queue
    ) {
    }
}

