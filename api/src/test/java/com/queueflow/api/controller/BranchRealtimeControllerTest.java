package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
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
import com.queueflow.api.entity.Queue;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.request;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc

class BranchRealtimeControllerTest {

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
    void shouldAllowPublicSubscriptionToExistingBranch()
            throws Exception {

        Business business = createBusiness();

        Branch branch = createBranch(
                business,
                "Asia/Singapore"
        );

        MvcResult result =
                mockMvc.perform(
                                get(
                                        "/api/v1/businesses/{businessId}/branches/{branchId}/events",
                                        business.getId(),
                                        branch.getId()
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
    void shouldReturnNotFoundForUnknownBranch()
            throws Exception {

        Business business = createBusiness();

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/events",
                                business.getId(),
                                999999L
                        )
                                .accept(
                                        MediaType.TEXT_EVENT_STREAM
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(request().asyncNotStarted());
    }

    @Test
    void shouldReturnNotFoundWhenBranchBelongsToDifferentBusiness()
            throws Exception {

        Business firstBusiness = createBusiness();

        Business secondBusiness =
                businessRepository.save(
                        new Business(
                                "Other Business",
                                "Another business"
                        )
                );

        Branch branch = createBranch(
                secondBusiness,
                "Asia/Singapore"
        );

        mockMvc.perform(
                        get(
                                "/api/v1/businesses/{businessId}/branches/{branchId}/events",
                                firstBusiness.getId(),
                                branch.getId()
                        )
                                .accept(
                                        MediaType.TEXT_EVENT_STREAM
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(request().asyncNotStarted());
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
}