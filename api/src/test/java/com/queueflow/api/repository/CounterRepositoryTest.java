package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.Counter;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class CounterRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private CounterRepository counterRepository;

    @Test
    void shouldSaveCounterWithBranch() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "QueueFlow Counter Test Business",
                        "Business for counter persistence test"
                )
        );

        Branch branch = branchRepository.saveAndFlush(
                new Branch(
                        business,
                        "Downtown Branch",
                        "123 Test Street",
                        new BigDecimal("1.352100"),
                        new BigDecimal("103.819800")
                )
        );

        Counter counter = new Counter(
                branch,
                "Counter 1"
        );

        Counter savedCounter = counterRepository.saveAndFlush(counter);

        assertThat(savedCounter.getId()).isNotNull();
        assertThat(savedCounter.getCreatedAt()).isNotNull();
        assertThat(savedCounter.getUpdatedAt()).isNotNull();
        assertThat(savedCounter.isActive()).isTrue();

        Counter foundCounter = counterRepository
                .findById(savedCounter.getId())
                .orElseThrow();

        assertThat(foundCounter.getName())
                .isEqualTo("Counter 1");

        assertThat(foundCounter.getBranch().getId())
                .isEqualTo(branch.getId());

        assertThat(foundCounter.getBranch().getBusiness().getId())
                .isEqualTo(business.getId());
    }
}