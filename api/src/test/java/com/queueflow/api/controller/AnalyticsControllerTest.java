package com.queueflow.api.controller;

import com.queueflow.api.entity.*;
import com.queueflow.api.repository.*;
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

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class AnalyticsControllerTest {

    @Autowired MockMvc mockMvc;
    @Autowired QueueEntryRepository entryRepository;
    @Autowired QueueRepository queueRepository;
    @Autowired ServiceRepository serviceRepository;
    @Autowired StaffMembershipRepository membershipRepository;
    @Autowired BranchRepository branchRepository;
    @Autowired BusinessRepository businessRepository;
    @Autowired UserAccountRepository userRepository;
    @Autowired AuthSessionRepository authSessionRepository;
    @Autowired PasswordEncoder passwordEncoder;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        entryRepository.deleteAll();
        queueRepository.deleteAll();
        membershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userRepository.deleteAll();
    }

    @Test
    void shouldCalculateBranchAnalytics() throws Exception {
        Business business = businessRepository.save(
                new Business("Analytics Test", "Test business")
        );

        Branch branch = new Branch(
                business, "Analytics Branch", "1 Test Street",
                new BigDecimal("1.3000"),
                new BigDecimal("103.8000")
        );
        branch = branchRepository.save(branch);

        Service service = serviceRepository.save(
                new Service(branch, "Consultation", "Test service", 20)
        );

        LocalDate date = LocalDate.of(2026, 10, 12);

        Queue queue = new Queue(
                branch, service, "Analytics Queue", date, "A"
        );
        queue.setStatus(QueueStatus.OPEN);
        queue = queueRepository.save(queue);

        OffsetDateTime base = OffsetDateTime.parse(
                "2026-10-12T09:00:00+08:00"
        );

        createEntry(queue, service, 1, QueueEntryStatus.COMPLETED,
                base, 10, 15);
        createEntry(queue, service, 2, QueueEntryStatus.COMPLETED,
                base.plusHours(1), 14, 25);
        createEntry(queue, service, 3, QueueEntryStatus.CANCELLED,
                base.plusHours(2), null, null);
        createEntry(queue, service, 4, QueueEntryStatus.WAITING,
                base.plusHours(3), null, null);
        createEntry(queue, service, 5, QueueEntryStatus.SKIPPED,
                base.plusHours(4), null, null);

        String email = "analytics-manager@example.com";

        UserAccount manager = userRepository.save(
                new UserAccount(
                        email,
                        passwordEncoder.encode("password123"),
                        "Analytics", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, branch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "analytics-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", date.toString())
                        .param("to", date.toString())
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.totalEntries").value(5))
                .andExpect(jsonPath("$.completedEntries").value(2))
                .andExpect(jsonPath("$.cancelledEntries").value(1))
                .andExpect(jsonPath("$.skippedEntries").value(1))
                .andExpect(jsonPath("$.waitingEntries").value(1))
                .andExpect(jsonPath("$.completionRatePercent").value(40.0))
                .andExpect(jsonPath("$.cancellationRatePercent").value(20.0))
                .andExpect(jsonPath("$.averageWaitMinutes").value(12.0))
                .andExpect(jsonPath("$.averageServiceMinutes").value(20.0));
    }

    @Test
    void shouldReturnZeroMetricsForEmptyDateRange() throws Exception {
        Business business = businessRepository.save(
                new Business("Empty Analytics", "Empty test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Empty Branch", "2 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        String email = "empty-analytics-manager@example.com";

        UserAccount manager = userRepository.save(
                new UserAccount(
                        email,
                        passwordEncoder.encode("password123"),
                        "Empty", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, branch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "empty-analytics-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.totalEntries").value(0))
                .andExpect(jsonPath("$.completedEntries").value(0))
                .andExpect(jsonPath("$.cancelledEntries").value(0))
                .andExpect(jsonPath("$.skippedEntries").value(0))
                .andExpect(jsonPath("$.completionRatePercent").value(0.0))
                .andExpect(jsonPath("$.cancellationRatePercent").value(0.0))
                .andExpect(jsonPath("$.averageWaitMinutes").value(org.hamcrest.Matchers.nullValue()))
                .andExpect(jsonPath("$.averageServiceMinutes").value(org.hamcrest.Matchers.nullValue()));
    }
    @Test
    void shouldRejectRegularStaffFromBranchAnalytics() throws Exception {
        Business business = businessRepository.save(
                new Business("Staff Analytics Test", "Authorization test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Staff Branch", "3 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount staff = userRepository.save(
                new UserAccount(
                        "analytics-staff@example.com",
                        passwordEncoder.encode("password123"),
                        "Regular", "Staff", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        staff, business, branch, StaffRole.STAFF
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "analytics-staff@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.status").value(403));
    }
    @Test
    void shouldRejectManagerFromDifferentBranch() throws Exception {
        Business business = businessRepository.save(
                new Business("Multi Branch Analytics", "Isolation test")
        );

        Branch firstBranch = branchRepository.save(
                new Branch(
                        business, "First Branch", "1 First Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        Branch secondBranch = branchRepository.save(
                new Branch(
                        business, "Second Branch", "2 Second Street",
                        new BigDecimal("1.3100"),
                        new BigDecimal("103.8100")
                )
        );

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "branch-isolation@example.com",
                        passwordEncoder.encode("password123"),
                        "Branch", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, firstBranch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "branch-isolation@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), secondBranch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.status").value(403));
    }
    @Test
    void shouldRequireAuthenticationForAnalytics() throws Exception {
        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        999999L, 999999L)
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
        )
                .andExpect(status().isUnauthorized());
    }
    @Test
    void shouldRejectReversedAnalyticsDateRange() throws Exception {
        Business business = businessRepository.save(
                new Business("Date Validation Analytics", "Validation test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Date Validation Branch", "4 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "analytics-date-manager@example.com",
                        passwordEncoder.encode("password123"),
                        "Date", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, branch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "analytics-date-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-12")
                        .param("to", "2026-10-01")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status").value(400))
                .andExpect(jsonPath("$.message")
                        .value("From date cannot be after to date"));
    }
    @Test
    void shouldRejectAnalyticsDateRangeOver366Days() throws Exception {
        Business business = businessRepository.save(
                new Business("Long Range Analytics", "Range validation test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Long Range Branch", "5 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "analytics-range-manager@example.com",
                        passwordEncoder.encode("password123"),
                        "Range", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, branch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "analytics-range-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", "2025-01-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.status").value(400))
                .andExpect(jsonPath("$.message")
                        .value("Analytics date range cannot exceed 366 days"));
    }
    @Test
    void shouldRejectAnalyticsAccessAcrossBusinesses() throws Exception {
        Business firstBusiness = businessRepository.save(
                new Business("First Analytics Business", "First business")
        );

        Branch firstBranch = branchRepository.save(
                new Branch(
                        firstBusiness, "First Business Branch", "1 Main Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        Business secondBusiness = businessRepository.save(
                new Business("Second Analytics Business", "Second business")
        );

        Branch secondBranch = branchRepository.save(
                new Branch(
                        secondBusiness, "Second Business Branch", "2 Main Street",
                        new BigDecimal("1.3100"),
                        new BigDecimal("103.8100")
                )
        );

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "cross-business-analytics@example.com",
                        passwordEncoder.encode("password123"),
                        "Cross", "Business", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, firstBusiness, firstBranch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "cross-business-analytics@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        secondBusiness.getId(), secondBranch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.status").value(403));
    }
    @Test
    void shouldAllowBusinessOwnerToAccessBranchAnalytics() throws Exception {
        Business business = businessRepository.save(
                new Business("Owner Analytics Business", "Owner access test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Owner Test Branch", "6 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount owner = userRepository.save(
                new UserAccount(
                        "analytics-owner@example.com",
                        passwordEncoder.encode("password123"),
                        "Analytics", "Owner", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        owner, business, null, StaffRole.OWNER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "analytics-owner@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.businessId").value(business.getId()))
                .andExpect(jsonPath("$.branchId").value(branch.getId()))
                .andExpect(jsonPath("$.totalEntries").value(0));
    }
    @Test
    void shouldRejectBranchBelongingToAnotherBusiness() throws Exception {
        Business firstBusiness = businessRepository.save(
                new Business("Mismatch First Business", "First business")
        );

        Business secondBusiness = businessRepository.save(
                new Business("Mismatch Second Business", "Second business")
        );

        Branch secondBranch = branchRepository.save(
                new Branch(
                        secondBusiness, "Other Business Branch", "7 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount owner = userRepository.save(
                new UserAccount(
                        "mismatch-analytics-owner@example.com",
                        passwordEncoder.encode("password123"),
                        "Mismatch", "Owner", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        owner, firstBusiness, null, StaffRole.OWNER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "mismatch-analytics-owner@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics",
                        firstBusiness.getId(), secondBranch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status").value(404));
    }
    @Test
    void shouldReturnDailyTrendsInDateOrder() throws Exception {
        Business business = businessRepository.save(
                new Business("Daily Analytics Business", "Daily trend test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Daily Branch", "8 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        Service service = serviceRepository.save(
                new Service(branch, "Daily Service", "Trend testing", 20)
        );

        Queue firstQueue = new Queue(
                branch, service, "First Daily Queue",
                LocalDate.of(2026, 10, 10), "D"
        );
        firstQueue.setStatus(QueueStatus.OPEN);
        firstQueue = queueRepository.save(firstQueue);

        Queue secondQueue = new Queue(
                branch, service, "Second Daily Queue",
                LocalDate.of(2026, 10, 12), "D"
        );
        secondQueue.setStatus(QueueStatus.OPEN);
        secondQueue = queueRepository.save(secondQueue);

        OffsetDateTime base = OffsetDateTime.parse(
                "2026-10-10T09:00:00+08:00"
        );

        createEntry(firstQueue, service, 1, QueueEntryStatus.COMPLETED,
                base, 10, 15);
        createEntry(firstQueue, service, 2, QueueEntryStatus.CANCELLED,
                base.plusMinutes(30), null, null);
        createEntry(secondQueue, service, 1, QueueEntryStatus.COMPLETED,
                base.plusDays(2), 5, 10);

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "daily-analytics-manager@example.com",
                        passwordEncoder.encode("password123"),
                        "Daily", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, branch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "daily-analytics-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics/daily",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-10")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()").value(2))
                .andExpect(jsonPath("$[0].date").value("2026-10-10"))
                .andExpect(jsonPath("$[0].totalEntries").value(2))
                .andExpect(jsonPath("$[0].completedEntries").value(1))
                .andExpect(jsonPath("$[0].cancelledEntries").value(1))
                .andExpect(jsonPath("$[1].date").value("2026-10-12"))
                .andExpect(jsonPath("$[1].totalEntries").value(1))
                .andExpect(jsonPath("$[1].completedEntries").value(1))
                .andExpect(jsonPath("$[1].cancelledEntries").value(0));
    }
    @Test
    void shouldRequireAuthenticationForDailyTrends() throws Exception {
        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics/daily",
                        999999L, 999999L)
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
        )
                .andExpect(status().isUnauthorized());
    }
    @Test
    void shouldRejectRegularStaffFromDailyTrends() throws Exception {
        Business business = businessRepository.save(
                new Business("Daily Staff Security", "Daily access test")
        );

        Branch branch = branchRepository.save(
                new Branch(
                        business, "Daily Security Branch", "9 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        UserAccount staff = userRepository.save(
                new UserAccount(
                        "daily-staff-security@example.com",
                        passwordEncoder.encode("password123"),
                        "Daily", "Staff", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        staff, business, branch, StaffRole.STAFF
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "daily-staff-security@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics/daily",
                        business.getId(), branch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.status").value(403));
    }
    @Test
    void shouldRejectDifferentBranchManagerFromDailyTrends() throws Exception {
        Business business = businessRepository.save(
                new Business("Daily Branch Isolation", "Daily security test")
        );

        Branch firstBranch = branchRepository.save(
                new Branch(
                        business, "First Daily Branch", "10 Test Street",
                        new BigDecimal("1.3000"),
                        new BigDecimal("103.8000")
                )
        );

        Branch secondBranch = branchRepository.save(
                new Branch(
                        business, "Second Daily Branch", "11 Test Street",
                        new BigDecimal("1.3100"),
                        new BigDecimal("103.8100")
                )
        );

        UserAccount manager = userRepository.save(
                new UserAccount(
                        "daily-branch-manager@example.com",
                        passwordEncoder.encode("password123"),
                        "Daily", "Manager", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        manager, business, firstBranch, StaffRole.MANAGER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "daily-branch-manager@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/branches/{branchId}/analytics/daily",
                        business.getId(), secondBranch.getId())
                        .param("from", "2026-10-01")
                        .param("to", "2026-10-12")
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.status").value(403));
    }
    @Test
    void shouldReturnServicePeakHourAndBusinessAnalytics() throws Exception {
        Business business = businessRepository.save(
                new Business("Combined Analytics Test", "Reporting test")
        );

        Branch branch = new Branch(
                business, "Reporting Branch", "12 Test Street",
                new BigDecimal("1.3000"),
                new BigDecimal("103.8000")
        );
        branch.setTimezone("Asia/Singapore");
        branch = branchRepository.save(branch);

        Service service = serviceRepository.save(
                new Service(branch, "Reporting Service", "Test", 20)
        );

        LocalDate date = LocalDate.of(2026, 10, 12);

        Queue queue = new Queue(
                branch, service, "Reporting Queue", date, "R"
        );
        queue.setStatus(QueueStatus.OPEN);
        queue = queueRepository.save(queue);

        OffsetDateTime base = OffsetDateTime.parse(
                "2026-10-12T09:00:00+08:00"
        );

        createEntry(queue, service, 1, QueueEntryStatus.COMPLETED,
                base, 10, 20);
        createEntry(queue, service, 2, QueueEntryStatus.CANCELLED,
                base.plusMinutes(30), null, null);

        UserAccount owner = userRepository.save(
                new UserAccount(
                        "combined-analytics-owner@example.com",
                        passwordEncoder.encode("password123"),
                        "Reporting", "Owner", null
                )
        );

        membershipRepository.save(
                new StaffMembership(
                        owner, business, null, StaffRole.OWNER
                )
        );

        MvcResult login = mockMvc.perform(
                post("/api/v1/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("""
                                {
                                  "email": "combined-analytics-owner@example.com",
                                  "password": "password123"
                                }
                                """)
        ).andExpect(status().isOk()).andReturn();

        String body = login.getResponse().getContentAsString();
        String marker = "\"token\":\"";
        int start = body.indexOf(marker) + marker.length();
        int end = body.indexOf("\"", start);
        String token = body.substring(start, end);

        String branchUrl =
                "/api/v1/businesses/{businessId}/branches/{branchId}/analytics";

        mockMvc.perform(
                get(branchUrl + "/services", business.getId(), branch.getId())
                        .param("from", date.toString())
                        .param("to", date.toString())
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()").value(1))
                .andExpect(jsonPath("$[0].serviceId").value(service.getId()))
                .andExpect(jsonPath("$[0].totalEntries").value(2))
                .andExpect(jsonPath("$[0].completedEntries").value(1))
                .andExpect(jsonPath("$[0].cancelledEntries").value(1))
                .andExpect(jsonPath("$[0].averageWaitMinutes").value(10.0))
                .andExpect(jsonPath("$[0].averageServiceMinutes").value(20.0));

        mockMvc.perform(
                get(branchUrl + "/peak-hours", business.getId(), branch.getId())
                        .param("from", date.toString())
                        .param("to", date.toString())
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()").value(1))
                .andExpect(jsonPath("$[0].hour").value(9))
                .andExpect(jsonPath("$[0].totalEntries").value(2));

        mockMvc.perform(
                get("/api/v1/businesses/{businessId}/analytics/branches",
                        business.getId())
                        .param("from", date.toString())
                        .param("to", date.toString())
                        .header("Authorization", "Bearer " + token)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()").value(1))
                .andExpect(jsonPath("$[0].branchId").value(branch.getId()))
                .andExpect(jsonPath("$[0].totalEntries").value(2))
                .andExpect(jsonPath("$[0].completedEntries").value(1))
                .andExpect(jsonPath("$[0].cancelledEntries").value(1));
    }
    private void createEntry(
            Queue queue,
            Service service,
            int sequence,
            QueueEntryStatus status,
            OffsetDateTime joinedAt,
            Integer waitMinutes,
            Integer serviceMinutes
    ) {
        QueueEntry entry = new QueueEntry(
                queue, service, null, sequence,
                "analytics-guest-" + sequence
        );

        entry.setStatus(status);
        entry.setJoinedAt(joinedAt);

        if (waitMinutes != null) {
            OffsetDateTime servingAt =
                    joinedAt.plusMinutes(waitMinutes);
            entry.setServingAt(servingAt);
            entry.setCalledAt(servingAt.minusMinutes(1));

            if (serviceMinutes != null) {
                entry.setCompletedAt(
                        servingAt.plusMinutes(serviceMinutes)
                );
            }
        }

        if (status == QueueEntryStatus.CANCELLED) {
            entry.setCancelledAt(joinedAt.plusMinutes(2));
        }

        entryRepository.save(entry);
    }
}