package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.support.QueueEntryCode;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.Locale;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
@Transactional
class QueueEntryCodeResolveControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Test
    void shouldResolveActiveServiceWithoutAuthentication() throws Exception {
        Fixture fixture = createFixture(true);
        String formattedCode = QueueEntryCode.format(
                fixture.service().getQueueEntryCode()
        );

        assertThat(fixture.service().getQueueEntryCode())
                .matches("QF[A-HJ-NP-Z2-9]{12}");

        mockMvc.perform(get(
                        "/api/v1/public/services/resolve-entry-code/{entryCode}",
                        formattedCode.toLowerCase(Locale.ROOT)
                ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.entryCode").value(formattedCode))
                .andExpect(jsonPath("$.businessId").value(fixture.business().getId()))
                .andExpect(jsonPath("$.branchId").value(fixture.branch().getId()))
                .andExpect(jsonPath("$.serviceId").value(fixture.service().getId()));
    }

    @Test
    void shouldResolveInactiveServiceToPublicPreview() throws Exception {
        Fixture fixture = createFixture(false);

        mockMvc.perform(get(
                        "/api/v1/public/services/resolve-entry-code/{entryCode}",
                        QueueEntryCode.format(fixture.service().getQueueEntryCode())
                ))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.serviceId").value(fixture.service().getId()));
    }

    @Test
    void shouldReturnNotFoundForUnknownValidCode() throws Exception {
        mockMvc.perform(get(
                        "/api/v1/public/services/resolve-entry-code/{entryCode}",
                        "QF-ABCD-EFGH-JKLM"
                ))
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status").value(404));
    }

    @Test
    void shouldReturnNotFoundForMalformedCode() throws Exception {
        mockMvc.perform(get(
                        "/api/v1/public/services/resolve-entry-code/{entryCode}",
                        "QF-ABCD-EFGH-JKLO"
                ))
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status").value(404));
    }

    @Test
    void shouldRequireAuthenticationForUnsupportedHttpMethod() throws Exception {
        mockMvc.perform(post(
                        "/api/v1/public/services/resolve-entry-code/{entryCode}",
                        "QF-ABCD-EFGH-JKLM"
                ))
                .andExpect(status().isUnauthorized());
    }

    private Fixture createFixture(boolean active) {
        Business business = businessRepository.save(
                new Business("QueueFlow Clinic", "Medical clinic")
        );
        Branch branch = branchRepository.save(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );
        com.queueflow.api.entity.Service service =
                new com.queueflow.api.entity.Service(
                        branch,
                        "General Consultation",
                        "General consultation service",
                        15
                );
        service.setActive(active);

        return new Fixture(
                business,
                branch,
                serviceRepository.saveAndFlush(service)
        );
    }

    private record Fixture(
            Business business,
            Branch branch,
            com.queueflow.api.entity.Service service
    ) {
    }
}
