package com.queueflow.api.repository;

import com.queueflow.api.entity.Business;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class BusinessRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Test
    void shouldSaveAndFindBusiness() {
        Business business = new Business(
                "QueueFlow Test Business",
                "Business created by persistence integration test"
        );

        Business savedBusiness = businessRepository.saveAndFlush(business);

        assertThat(savedBusiness.getId()).isNotNull();
        assertThat(savedBusiness.getCreatedAt()).isNotNull();
        assertThat(savedBusiness.getUpdatedAt()).isNotNull();

        Business foundBusiness = businessRepository
                .findById(savedBusiness.getId())
                .orElseThrow();

        assertThat(foundBusiness.getName())
                .isEqualTo("QueueFlow Test Business");

        assertThat(foundBusiness.getDescription())
                .isEqualTo("Business created by persistence integration test");
    }
}