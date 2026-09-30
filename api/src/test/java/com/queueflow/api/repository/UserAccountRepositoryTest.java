package com.queueflow.api.repository;

import com.queueflow.api.entity.UserAccount;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class UserAccountRepositoryTest {

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Test
    void shouldSaveAndFindUserAccount() {

        UserAccount user = new UserAccount(
                "test@queueflow.com",
                "hashed-password-example",
                "Test",
                "User",
                "+6512345678"
        );

        UserAccount savedUser = userAccountRepository.saveAndFlush(user);

        assertThat(savedUser.getId()).isNotNull();
        assertThat(savedUser.getCreatedAt()).isNotNull();
        assertThat(savedUser.getUpdatedAt()).isNotNull();
        assertThat(savedUser.isActive()).isTrue();

        UserAccount foundUser = userAccountRepository
                .findById(savedUser.getId())
                .orElseThrow();

        assertThat(foundUser.getEmail())
                .isEqualTo("test@queueflow.com");

        assertThat(foundUser.getFirstName())
                .isEqualTo("Test");

        assertThat(foundUser.getLastName())
                .isEqualTo("User");

        assertThat(foundUser.getPasswordHash())
                .isEqualTo("hashed-password-example");
    }
}