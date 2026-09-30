package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class BranchRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Test
    void shouldSaveBranchWithBusiness() {

        Business business = new Business(
                "QueueFlow Test Business",
                "Business for branch persistence test"
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

        assertThat(savedBranch.getId()).isNotNull();
        assertThat(savedBranch.getCreatedAt()).isNotNull();
        assertThat(savedBranch.getUpdatedAt()).isNotNull();

        Branch foundBranch = branchRepository
                .findById(savedBranch.getId())
                .orElseThrow();

        assertThat(foundBranch.getName())
                .isEqualTo("Downtown Branch");

        assertThat(foundBranch.getBusiness().getId())
                .isEqualTo(savedBusiness.getId());

        assertThat(foundBranch.getBusiness().getName())
                .isEqualTo("QueueFlow Test Business");
    }
}