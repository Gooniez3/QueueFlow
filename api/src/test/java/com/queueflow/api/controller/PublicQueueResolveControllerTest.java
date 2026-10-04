package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
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
import org.springframework.test.web.servlet.MockMvc;

import java.math.BigDecimal;
import java.time.LocalDate;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class PublicQueueResolveControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
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
    void shouldResolveServiceQueueWithoutAuthentication()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                new BigDecimal("1.352100"),
                                new BigDecimal("103.819800")
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        Queue queue =
                queueRepository.save(
                        new Queue(
                                branch,
                                service,
                                "Consultation Queue",
                                LocalDate.now(),
                                "A"
                        )
                );

        assertThat(queue.getPublicCode())
                .isNotBlank();

        mockMvc.perform(
                        get(
                                "/api/v1/public/queues/resolve/{publicCode}",
                                queue.getPublicCode()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.publicCode")
                        .value(queue.getPublicCode()))
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.businessName")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.branchName")
                        .value("Downtown Branch"))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.serviceName")
                        .value("General Consultation"))
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queueName")
                        .value("Consultation Queue"))
                .andExpect(jsonPath("$.queueStatus")
                        .value("OPEN"))
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist())
                .andExpect(jsonPath("$.userId")
                        .doesNotExist());

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldResolveSharedQueueWithoutService()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        Queue queue =
                queueRepository.save(
                        new Queue(
                                branch,
                                null,
                                "Main Queue",
                                LocalDate.now(),
                                "A"
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/public/queues/resolve/{publicCode}",
                                queue.getPublicCode()
                        )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.publicCode")
                        .value(queue.getPublicCode()))
                .andExpect(jsonPath("$.businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .doesNotExist())
                .andExpect(jsonPath("$.serviceName")
                        .doesNotExist())
                .andExpect(jsonPath("$.queueId")
                        .value(queue.getId()))
                .andExpect(jsonPath("$.queueName")
                        .value("Main Queue"))
                .andExpect(jsonPath("$.queueStatus")
                        .value("OPEN"));

        assertThat(queueEntryRepository.count())
                .isZero();
    }

    @Test
    void shouldReturnNotFoundForUnknownPublicCode()
            throws Exception {

        mockMvc.perform(
                        get(
                                "/api/v1/public/queues/resolve/{publicCode}",
                                "00000000-0000-0000-0000-000000000000"
                        )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Queue not found for public code"
                        ));
    }
}