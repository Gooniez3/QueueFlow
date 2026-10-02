package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
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
    private QueueEntryRepository
            queueEntryRepository;

    @Autowired
    private QueueRepository
            queueRepository;

    @Autowired
    private StaffMembershipRepository
            staffMembershipRepository;

    @Autowired
    private ServiceRepository
            serviceRepository;

    @Autowired
    private BranchRepository
            branchRepository;

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
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
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

    @Test
    void shouldAllowMembershipForAssignedBranch() {

        UserAccount user = createUser(
                "branch-staff@example.com"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "Branch Business",
                                "Authorization test"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Main Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        StaffMembership membership =
                staffMembershipRepository.save(
                        new StaffMembership(
                                user,
                                business,
                                branch,
                                StaffRole.STAFF
                        )
                );

        StaffMembership result =
                businessAuthorizationService
                        .requireBranchAccess(
                                user.getId(),
                                business.getId(),
                                branch.getId()
                        );

        assertThat(result.getId())
                .isEqualTo(membership.getId());
    }

    @Test
    void shouldDenyMembershipForDifferentBranch() {

        UserAccount user = createUser(
                "restricted-staff@example.com"
        );

        Business business =
                businessRepository.save(
                        new Business(
                                "Multi Branch Business",
                                "Authorization test"
                        )
                );

        Branch assignedBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Branch A",
                                "123 Branch A Street",
                                null,
                                null
                        )
                );

        Branch otherBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Branch B",
                                "456 Branch B Street",
                                null,
                                null
                        )
                );

        staffMembershipRepository.save(
                new StaffMembership(
                        user,
                        business,
                        assignedBranch,
                        StaffRole.STAFF
                )
        );

        assertThatThrownBy(() ->
                businessAuthorizationService
                        .requireBranchAccess(
                                user.getId(),
                                business.getId(),
                                otherBranch.getId()
                        )
        )
                .isInstanceOf(
                        AccessDeniedException.class
                )
                .hasMessage(
                        "You do not have access to this branch"
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