package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Service;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class ServiceRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Test
    void shouldSaveServiceWithBranch() {

        Business business = new Business(
                "QueueFlow Test Business",
                "Business for service persistence test"
        );

        Business savedBusiness = businessRepository.saveAndFlush(business);

        Branch branch = new Branch(
                savedBusiness,
                "Downtown Branch",
                "123 Test Street",
                new BigDecimal("1.352100"),
                new BigDecimal("103.819800")
        );

        Branch savedBranch = branchRepository.saveAndFlush(branch);

        Service service = new Service(
                savedBranch,
                "General Consultation",
                "General customer service",
                30
        );

        Service savedService = serviceRepository.saveAndFlush(service);

        assertThat(savedService.getId()).isNotNull();
        assertThat(savedService.getCreatedAt()).isNotNull();
        assertThat(savedService.getUpdatedAt()).isNotNull();
        assertThat(savedService.isActive()).isTrue();

        Service foundService = serviceRepository
                .findById(savedService.getId())
                .orElseThrow();

        assertThat(foundService.getName())
                .isEqualTo("General Consultation");

        assertThat(foundService.getDurationMinutes())
                .isEqualTo(30);

        assertThat(foundService.getBranch().getId())
                .isEqualTo(savedBranch.getId());

        assertThat(foundService.getBranch().getBusiness().getId())
                .isEqualTo(savedBusiness.getId());
    }
}