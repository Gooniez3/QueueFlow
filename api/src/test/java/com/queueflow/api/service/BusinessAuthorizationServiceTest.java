package com.queueflow.api.service;

import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.security.crypto.password.PasswordEncoder;

import static org.assertj.core.api.Assertions.assertThat;
import static org.assertj.core.api.Assertions.assertThatThrownBy;

@SpringBootTest
class BusinessAuthorizationServiceTest {

    @Autowired
    private BusinessAuthorizationService
            businessAuthorizationService;

    @Autowired
    private StaffMembershipRepository
            staffMembershipRepository;

    @Autowired
    private BusinessRepository
            businessRepository;

    @Autowired
    private UserAccountRepository
            userAccountRepository;

    @Autowired
    private AuthSessionRepository
            authSessionRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void setUp() {
        authSessionRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldAllowActiveBusinessMembership() {

        UserAccount user = createUser(
                "allowed@example.com"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "Allowed Business",
                                "Authorization test"
                        )
                );

        StaffMembership membership =
                staffMembershipRepository.save(
                        new StaffMembership(
                                user,
                                business,
                                null,
                                StaffRole.STAFF
                        )
                );

        StaffMembership result =
                businessAuthorizationService
                        .requireMembership(
                                user.getId(),
                                business.getId()
                        );

        assertThat(result.getId())
                .isEqualTo(membership.getId());

        assertThat(result.getRole())
                .isEqualTo(StaffRole.STAFF);
    }

    @Test
    void shouldDenyUserWithoutBusinessMembership() {

        UserAccount user = createUser(
                "denied@example.com"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "Protected Business",
                                "Authorization test"
                        )
                );

        assertThatThrownBy(() ->
                businessAuthorizationService
                        .requireMembership(
                                user.getId(),
                                business.getId()
                        )
        )
                .isInstanceOf(
                        AccessDeniedException.class
                )
                .hasMessage(
                        "You do not have access to this business"
                );
    }

    @Test
    void shouldDenyInactiveBusinessMembership() {

        UserAccount user = createUser(
                "inactive@example.com"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "Inactive Membership Business",
                                "Authorization test"
                        )
                );

        StaffMembership membership =
                new StaffMembership(
                        user,
                        business,
                        null,
                        StaffRole.STAFF
                );

        membership.setActive(false);

        staffMembershipRepository.save(
                membership
        );

        assertThatThrownBy(() ->
                businessAuthorizationService
                        .requireMembership(
                                user.getId(),
                                business.getId()
                        )
        )
                .isInstanceOf(
                        AccessDeniedException.class
                )
                .hasMessage(
                        "You do not have access to this business"
                );
    }

    private UserAccount createUser(
            String email
    ) {

        UserAccount user =
                new UserAccount(
                        email,
                        passwordEncoder.encode(
                                "password123"
                        ),
                        "Queue",
                        "Staff",
                        null
                );

        return userAccountRepository.save(
                user
        );
    }
}