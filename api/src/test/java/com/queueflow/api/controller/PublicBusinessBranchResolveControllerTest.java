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
import com.queueflow.api.repository.UserAccountRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.test.web.servlet.MockMvc;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class PublicBusinessBranchResolveControllerTest {

    @Autowired private MockMvc mockMvc;
    @Autowired private BusinessRepository businessRepository;
    @Autowired private BranchRepository branchRepository;
    @Autowired private QueueRepository queueRepository;
    @Autowired private QueueEntryRepository queueEntryRepository;
    @Autowired private ServiceRepository serviceRepository;
    @Autowired private StaffMembershipRepository staffMembershipRepository;
    @Autowired private AuthSessionRepository authSessionRepository;
    @Autowired private UserAccountRepository userAccountRepository;
    @Autowired private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

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
    void shouldResolveBusinessWithoutAuthentication() throws Exception {
        Business business = businessRepository.save(
                new Business("QueueFlow Clinic", "Medical clinic")
        );

        assertThat(business.getPublicCode())
                .startsWith("biz_")
                .hasSize(36);

        mockMvc.perform(get(
                        "/api/v1/public/businesses/resolve/{publicCode}",
                        business.getPublicCode()
                ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.publicCode").value(business.getPublicCode()))
                .andExpect(jsonPath("$.businessId").value(business.getId()))
                .andExpect(jsonPath("$.businessName").value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.guestToken").doesNotExist());
    }

    @Test
    void shouldResolveBranchWithoutAuthentication() throws Exception {
        Business business = businessRepository.save(
                new Business("QueueFlow Clinic", "Medical clinic")
        );
        Branch branch = branchRepository.save(
                new Branch(business, "Downtown Branch", "123 Main Street", null, null)
        );

        assertThat(branch.getPublicCode())
                .startsWith("br_")
                .hasSize(35 + 0);

        mockMvc.perform(get(
                        "/api/v1/public/branches/resolve/{publicCode}",
                        branch.getPublicCode()
                ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.publicCode").value(branch.getPublicCode()))
                .andExpect(jsonPath("$.businessId").value(business.getId()))
                .andExpect(jsonPath("$.businessName").value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.branchId").value(branch.getId()))
                .andExpect(jsonPath("$.branchName").value("Downtown Branch"))
                .andExpect(jsonPath("$.guestToken").doesNotExist());
    }

    @Test
    void shouldReturnNotFoundForUnknownCodes() throws Exception {
        mockMvc.perform(get(
                        "/api/v1/public/businesses/resolve/{publicCode}",
                        "biz_00000000000000000000000000000000"
                ))
                .andExpect(status().isNotFound());

        mockMvc.perform(get(
                        "/api/v1/public/branches/resolve/{publicCode}",
                        "br_00000000000000000000000000000000"
                ))
                .andExpect(status().isNotFound());
    }

    @Test
    void shouldKeepCodesStableAfterUpdates() {
        Business business = businessRepository.saveAndFlush(
                new Business("Original Business", "Description")
        );
        Branch branch = branchRepository.saveAndFlush(
                new Branch(business, "Original Branch", "123 Main Street", null, null)
        );

        String businessCode = business.getPublicCode();
        String branchCode = branch.getPublicCode();

        business.setName("Updated Business");
        branch.setName("Updated Branch");

        businessRepository.saveAndFlush(business);
        branchRepository.saveAndFlush(branch);

        assertThat(businessRepository.findById(business.getId()).orElseThrow().getPublicCode())
                .isEqualTo(businessCode);
        assertThat(branchRepository.findById(branch.getId()).orElseThrow().getPublicCode())
                .isEqualTo(branchCode);
    }

    @Test
    void shouldGenerateUniqueCodes() {
        Business first = businessRepository.save(
                new Business("First Business", null)
        );
        Business second = businessRepository.save(
                new Business("Second Business", null)
        );

        assertThat(first.getPublicCode()).isNotEqualTo(second.getPublicCode());

        Branch firstBranch = branchRepository.save(
                new Branch(first, "First Branch", "Address A", null, null)
        );
        Branch secondBranch = branchRepository.save(
                new Branch(first, "Second Branch", "Address B", null, null)
        );

        assertThat(firstBranch.getPublicCode()).isNotEqualTo(secondBranch.getPublicCode());
    }
}