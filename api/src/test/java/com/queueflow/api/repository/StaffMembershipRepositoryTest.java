package com.queueflow.api.repository;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.StaffRole;
import com.queueflow.api.entity.UserAccount;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest
@Transactional
class StaffMembershipRepositoryTest {

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Test
    void shouldSaveStaffMembershipWithBranch() {

        Business business = businessRepository.saveAndFlush(
                new Business(
                        "QueueFlow Staff Test Business",
                        "Business for staff membership test"
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

        UserAccount user = userAccountRepository.saveAndFlush(
                new UserAccount(
                        "staff@queueflow.com",
                        "hashed-password-example",
                        "Test",
                        "Staff",
                        "+6512345678"
                )
        );

        StaffMembership membership = new StaffMembership(
                user,
                business,
                branch,
                StaffRole.STAFF
        );

        StaffMembership savedMembership =
                staffMembershipRepository.saveAndFlush(membership);

        assertThat(savedMembership.getId()).isNotNull();
        assertThat(savedMembership.getCreatedAt()).isNotNull();
        assertThat(savedMembership.getUpdatedAt()).isNotNull();
        assertThat(savedMembership.isActive()).isTrue();

        StaffMembership foundMembership = staffMembershipRepository
                .findById(savedMembership.getId())
                .orElseThrow();

        assertThat(foundMembership.getUser().getId())
                .isEqualTo(user.getId());

        assertThat(foundMembership.getBusiness().getId())
                .isEqualTo(business.getId());

        assertThat(foundMembership.getBranch().getId())
                .isEqualTo(branch.getId());

        assertThat(foundMembership.getRole())
                .isEqualTo(StaffRole.STAFF);
    }
}