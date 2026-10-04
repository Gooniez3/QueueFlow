package com.queueflow.api.controller;

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
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.EnumSource;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;
import org.springframework.test.web.servlet.ResultActions;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.put;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class ManagementUpdateControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void cleanDatabase() {
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
    void businessWideOwnerCanUpdateBusinessAndBlankDescriptionBecomesNull()
            throws Exception {
        Business business = createBusiness("Original Business");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "business-owner@example.com"
        );

        updateBusiness(business.getId(), token, """
                {
                    "name": "Updated Business",
                    "description": "   "
                }
                """)
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.id").value(business.getId()))
                .andExpect(jsonPath("$.name").value("Updated Business"))
                .andExpect(jsonPath("$.description").value((Object) null));

        Business persisted = businessRepository
                .findById(business.getId())
                .orElseThrow();

        assertThat(persisted.getName()).isEqualTo("Updated Business");
        assertThat(persisted.getDescription()).isNull();
    }

    @ParameterizedTest
    @EnumSource(value = StaffRole.class, names = {"MANAGER", "STAFF"})
    void nonOwnerRolesCannotUpdateBusiness(StaffRole role)
            throws Exception {
        Business business = createBusiness("Protected Business");
        String token = createMemberAndLogin(
                business,
                null,
                role,
                "business-" + role.name().toLowerCase() + "@example.com"
        );

        updateBusiness(business.getId(), token, validBusinessUpdate())
                .andExpect(status().isForbidden());

        assertThat(reloadBusiness(business).getName())
                .isEqualTo("Protected Business");
    }

    @Test
    void branchScopedOwnerCannotUpdateBusiness() throws Exception {
        Business business = createBusiness("Protected Business");
        Branch branch = createBranch(business, "Assigned Branch");
        String token = createMemberAndLogin(
                business,
                branch,
                StaffRole.OWNER,
                "scoped-owner@example.com"
        );

        updateBusiness(business.getId(), token, validBusinessUpdate())
                .andExpect(status().isForbidden());
    }

    @Test
    void userWithoutMembershipCannotUpdateBusiness() throws Exception {
        Business business = createBusiness("Protected Business");
        String token = createUserAndLogin("business-outsider@example.com");

        updateBusiness(business.getId(), token, validBusinessUpdate())
                .andExpect(status().isForbidden());
    }

    @Test
    void unauthenticatedBusinessUpdateIsDenied() throws Exception {
        Business business = createBusiness("Protected Business");

        mockMvc.perform(
                        put("/api/v1/businesses/{businessId}", business.getId())
                                .contentType(MediaType.APPLICATION_JSON)
                                .content(validBusinessUpdate())
                )
                .andExpect(status().isUnauthorized());
    }

    @Test
    void missingBusinessUpdateReturnsNotFound() throws Exception {
        Business ownedBusiness = createBusiness("Owned Business");
        String token = createMemberAndLogin(
                ownedBusiness,
                null,
                StaffRole.OWNER,
                "missing-business-owner@example.com"
        );

        updateBusiness(999999L, token, validBusinessUpdate())
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.message")
                        .value("Business not found with id: 999999"));
    }

    @Test
    void invalidBusinessUpdateIsRejected() throws Exception {
        Business business = createBusiness("Protected Business");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "validation-business-owner@example.com"
        );

        updateBusiness(business.getId(), token, """
                {
                    "name": "",
                    "description": null
                }
                """)
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.validationErrors.name")
                        .value("Business name is required"));
    }

    @Test
    void businessWideOwnerCanUpdateBranchWithoutChangingParent()
            throws Exception {
        Business business = createBusiness("Branch Business");
        Business otherBusiness = createBusiness("Other Business");
        Branch branch = createBranch(business, "Original Branch");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "branch-owner@example.com"
        );

        updateBranch(business.getId(), branch.getId(), token, """
                {
                    "businessId": %d,
                    "name": " Updated Branch ",
                    "address": " 20 Updated Street ",
                    "latitude": 1.3,
                    "longitude": 103.8,
                    "timezone": "Asia/Singapore"
                }
                """.formatted(otherBusiness.getId()))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.businessId").value(business.getId()))
                .andExpect(jsonPath("$.name").value("Updated Branch"))
                .andExpect(jsonPath("$.address").value("20 Updated Street"))
                .andExpect(jsonPath("$.timezone").value("Asia/Singapore"));

        Branch persisted = reloadBranch(branch);
        assertThat(persisted.getBusiness().getId()).isEqualTo(business.getId());
        assertThat(persisted.getLatitude()).isNotNull();
        assertThat(persisted.getLongitude()).isNotNull();
    }

    @Test
    void businessWideManagerCanUpdateBranchAndBlankTimezoneUsesDefault()
            throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Original Branch");
        branch.setTimezone("Australia/Perth");
        branchRepository.save(branch);
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.MANAGER,
                "branch-manager@example.com"
        );

        updateBranch(
                business.getId(),
                branch.getId(),
                token,
                validBranchUpdate("Manager Branch", "   ")
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.timezone").value("Asia/Singapore"));
    }

    @Test
    void branchScopedManagerCanUpdateAssignedBranch() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Assigned Branch");
        String token = createMemberAndLogin(
                business,
                branch,
                StaffRole.MANAGER,
                "assigned-manager@example.com"
        );

        updateBranch(
                business.getId(),
                branch.getId(),
                token,
                validBranchUpdate("Managed Branch", "Asia/Singapore")
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.name").value("Managed Branch"));
    }

    @Test
    void branchScopedManagerCannotUpdateAnotherBranch() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch assignedBranch = createBranch(business, "Assigned Branch");
        Branch otherBranch = createBranch(business, "Other Branch");
        String token = createMemberAndLogin(
                business,
                assignedBranch,
                StaffRole.MANAGER,
                "restricted-manager@example.com"
        );

        updateBranch(
                business.getId(),
                otherBranch.getId(),
                token,
                validBranchUpdate("Forbidden Update", "Asia/Singapore")
        ).andExpect(status().isForbidden());

        assertThat(reloadBranch(otherBranch).getName()).isEqualTo("Other Branch");
    }

    @Test
    void staffCannotUpdateBranch() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Protected Branch");
        String token = createMemberAndLogin(
                business,
                branch,
                StaffRole.STAFF,
                "branch-staff@example.com"
        );

        updateBranch(
                business.getId(),
                branch.getId(),
                token,
                validBranchUpdate("Forbidden Update", "Asia/Singapore")
        ).andExpect(status().isForbidden());
    }

    @Test
    void userWithoutMembershipCannotUpdateBranch() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Protected Branch");
        String token = createUserAndLogin("branch-outsider@example.com");

        updateBranch(
                business.getId(),
                branch.getId(),
                token,
                validBranchUpdate("Forbidden Update", "Asia/Singapore")
        ).andExpect(status().isForbidden());
    }

    @Test
    void branchUpdateRejectsBusinessHierarchyMismatch() throws Exception {
        Business firstBusiness = createBusiness("First Business");
        Business secondBusiness = createBusiness("Second Business");
        Branch secondBranch = createBranch(secondBusiness, "Second Branch");
        String token = createMemberAndLogin(
                firstBusiness,
                null,
                StaffRole.OWNER,
                "hierarchy-owner@example.com"
        );

        updateBranch(
                firstBusiness.getId(),
                secondBranch.getId(),
                token,
                validBranchUpdate("Wrong Parent", "Asia/Singapore")
        ).andExpect(status().isNotFound());
    }

    @Test
    void missingBranchUpdateReturnsNotFound() throws Exception {
        Business business = createBusiness("Branch Business");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "missing-branch-owner@example.com"
        );

        updateBranch(
                business.getId(),
                999999L,
                token,
                validBranchUpdate("Missing Branch", "Asia/Singapore")
        )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.message")
                        .value("Branch not found with id: 999999"));
    }

    @Test
    void branchUpdateRejectsInvalidCoordinates() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Protected Branch");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "coordinate-owner@example.com"
        );

        updateBranch(business.getId(), branch.getId(), token, """
                {
                    "name": "Updated Branch",
                    "address": "20 Updated Street",
                    "latitude": 91,
                    "longitude": -181,
                    "timezone": "Asia/Singapore"
                }
                """)
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.validationErrors.latitude")
                        .value("Latitude must not exceed 90"))
                .andExpect(jsonPath("$.validationErrors.longitude")
                        .value("Longitude must be at least -180"));
    }

    @Test
    void branchUpdateRejectsInvalidTimezone() throws Exception {
        Business business = createBusiness("Branch Business");
        Branch branch = createBranch(business, "Protected Branch");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "timezone-owner@example.com"
        );

        updateBranch(
                business.getId(),
                branch.getId(),
                token,
                validBranchUpdate("Updated Branch", "Not/A_Real_Timezone")
        )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message")
                        .value("Invalid timezone: Not/A_Real_Timezone"));
    }

    @Test
    void businessWideOwnerCanDeactivateServiceAndBlankDescriptionBecomesNull()
            throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Original Service");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "service-owner@example.com"
        );

        updateService(business.getId(), branch.getId(), service.getId(), token, """
                {
                    "branchId": 999999,
                    "name": " Updated Service ",
                    "description": "   ",
                    "durationMinutes": 45,
                    "active": false
                }
                """)
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.branchId").value(branch.getId()))
                .andExpect(jsonPath("$.name").value("Updated Service"))
                .andExpect(jsonPath("$.description").value((Object) null))
                .andExpect(jsonPath("$.durationMinutes").value(45))
                .andExpect(jsonPath("$.active").value(false));

        com.queueflow.api.entity.Service persisted = reloadService(service);
        assertThat(persisted.getBranch().getId()).isEqualTo(branch.getId());
        assertThat(persisted.isActive()).isFalse();
        assertThat(persisted.getDescription()).isNull();
    }

    @Test
    void businessWideManagerCanReactivateService() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Inactive Service");
        service.setActive(false);
        serviceRepository.save(service);
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.MANAGER,
                "service-manager@example.com"
        );

        updateService(
                business.getId(),
                branch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Reactivated Service", true)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.active").value(true));

        assertThat(reloadService(service).isActive()).isTrue();
    }

    @Test
    void branchScopedManagerCanUpdateServiceAtAssignedBranch()
            throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Assigned Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Managed Service");
        String token = createMemberAndLogin(
                business,
                branch,
                StaffRole.MANAGER,
                "service-branch-manager@example.com"
        );

        updateService(
                business.getId(),
                branch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Updated By Manager", true)
        )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.name")
                        .value("Updated By Manager"));
    }

    @Test
    void branchScopedManagerCannotUpdateServiceAtAnotherBranch()
            throws Exception {
        Business business = createBusiness("Service Business");
        Branch assignedBranch = createBranch(business, "Assigned Branch");
        Branch otherBranch = createBranch(business, "Other Branch");
        com.queueflow.api.entity.Service service =
                createService(otherBranch, "Protected Service");
        String token = createMemberAndLogin(
                business,
                assignedBranch,
                StaffRole.MANAGER,
                "restricted-service-manager@example.com"
        );

        updateService(
                business.getId(),
                otherBranch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Forbidden Update", false)
        ).andExpect(status().isForbidden());

        assertThat(reloadService(service).getName())
                .isEqualTo("Protected Service");
    }

    @Test
    void staffCannotUpdateService() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Protected Service");
        String token = createMemberAndLogin(
                business,
                branch,
                StaffRole.STAFF,
                "service-staff@example.com"
        );

        updateService(
                business.getId(),
                branch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Forbidden Update", false)
        ).andExpect(status().isForbidden());
    }

    @Test
    void userWithoutMembershipCannotUpdateService() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Protected Service");
        String token = createUserAndLogin("service-outsider@example.com");

        updateService(
                business.getId(),
                branch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Forbidden Update", false)
        ).andExpect(status().isForbidden());
    }

    @Test
    void serviceUpdateRejectsBusinessAndBranchHierarchyMismatch()
            throws Exception {
        Business firstBusiness = createBusiness("First Business");
        Business secondBusiness = createBusiness("Second Business");
        Branch firstBranch = createBranch(firstBusiness, "First Branch");
        Branch secondBranch = createBranch(secondBusiness, "Second Branch");
        com.queueflow.api.entity.Service service =
                createService(secondBranch, "Second Service");
        String token = createMemberAndLogin(
                firstBusiness,
                null,
                StaffRole.OWNER,
                "service-hierarchy-owner@example.com"
        );

        updateService(
                firstBusiness.getId(),
                firstBranch.getId(),
                service.getId(),
                token,
                validServiceUpdate("Wrong Hierarchy", true)
        ).andExpect(status().isNotFound());
    }

    @Test
    void missingServiceUpdateReturnsNotFound() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "missing-service-owner@example.com"
        );

        updateService(
                business.getId(),
                branch.getId(),
                999999L,
                token,
                validServiceUpdate("Missing Service", true)
        )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.message")
                        .value("Service not found with id: 999999"));
    }

    @Test
    void serviceUpdateRejectsInvalidDuration() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Protected Service");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "duration-owner@example.com"
        );

        updateService(business.getId(), branch.getId(), service.getId(), token, """
                {
                    "name": "Updated Service",
                    "description": null,
                    "durationMinutes": 0,
                    "active": true
                }
                """)
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.validationErrors.durationMinutes")
                        .value("Duration must be greater than 0"));
    }

    @Test
    void serviceUpdateRequiresActiveStatus() throws Exception {
        Business business = createBusiness("Service Business");
        Branch branch = createBranch(business, "Service Branch");
        com.queueflow.api.entity.Service service =
                createService(branch, "Protected Service");
        String token = createMemberAndLogin(
                business,
                null,
                StaffRole.OWNER,
                "active-owner@example.com"
        );

        updateService(business.getId(), branch.getId(), service.getId(), token, """
                {
                    "name": "Updated Service",
                    "description": null,
                    "durationMinutes": 30
                }
                """)
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.validationErrors.active")
                        .value("Active status is required"));
    }

    private ResultActions updateBusiness(
            Long businessId,
            String token,
            String body
    ) throws Exception {
        return mockMvc.perform(
                put("/api/v1/businesses/{businessId}", businessId)
                        .header("Authorization", "Bearer " + token)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(body)
        );
    }

    private ResultActions updateBranch(
            Long businessId,
            Long branchId,
            String token,
            String body
    ) throws Exception {
        return mockMvc.perform(
                put(
                        "/api/v1/businesses/{businessId}/branches/{branchId}",
                        businessId,
                        branchId
                )
                        .header("Authorization", "Bearer " + token)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(body)
        );
    }

    private ResultActions updateService(
            Long businessId,
            Long branchId,
            Long serviceId,
            String token,
            String body
    ) throws Exception {
        return mockMvc.perform(
                put(
                        "/api/v1/businesses/{businessId}/branches/{branchId}/services/{serviceId}",
                        businessId,
                        branchId,
                        serviceId
                )
                        .header("Authorization", "Bearer " + token)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(body)
        );
    }

    private Business createBusiness(String name) {
        return businessRepository.save(
                new Business(name, "Original description")
        );
    }

    private Branch createBranch(Business business, String name) {
        return branchRepository.save(
                new Branch(
                        business,
                        name,
                        "10 Original Street",
                        null,
                        null
                )
        );
    }

    private com.queueflow.api.entity.Service createService(
            Branch branch,
            String name
    ) {
        return serviceRepository.save(
                new com.queueflow.api.entity.Service(
                        branch,
                        name,
                        "Original description",
                        30
                )
        );
    }

    private String createMemberAndLogin(
            Business business,
            Branch branch,
            StaffRole role,
            String email
    ) throws Exception {
        UserAccount user = createUser(email);

        staffMembershipRepository.save(
                new StaffMembership(user, business, branch, role)
        );

        return loginAndGetToken(email, "password123");
    }

    private String createUserAndLogin(String email) throws Exception {
        createUser(email);
        return loginAndGetToken(email, "password123");
    }

    private UserAccount createUser(String email) {
        return userAccountRepository.save(
                new UserAccount(
                        email,
                        passwordEncoder.encode("password123"),
                        "Queue",
                        "Staff",
                        null
                )
        );
    }

    private String loginAndGetToken(
            String email,
            String password
    ) throws Exception {
        MvcResult result = mockMvc.perform(
                        post("/api/v1/auth/login")
                                .contentType(MediaType.APPLICATION_JSON)
                                .content("""
                                        {
                                            "email": "%s",
                                            "password": "%s"
                                        }
                                        """.formatted(email, password))
                )
                .andExpect(status().isOk())
                .andReturn();

        return extractJsonString(
                result.getResponse().getContentAsString(),
                "token"
        );
    }

    private String extractJsonString(String json, String fieldName) {
        String marker = "\"" + fieldName + "\":\"";
        int start = json.indexOf(marker);

        assertThat(start).isGreaterThanOrEqualTo(0);
        start += marker.length();

        int end = json.indexOf("\"", start);
        assertThat(end).isGreaterThan(start);

        return json.substring(start, end);
    }

    private Business reloadBusiness(Business business) {
        return businessRepository.findById(business.getId()).orElseThrow();
    }

    private Branch reloadBranch(Branch branch) {
        return branchRepository.findById(branch.getId()).orElseThrow();
    }

    private com.queueflow.api.entity.Service reloadService(
            com.queueflow.api.entity.Service service
    ) {
        return serviceRepository.findById(service.getId()).orElseThrow();
    }

    private String validBusinessUpdate() {
        return """
                {
                    "name": "Updated Business",
                    "description": "Updated description"
                }
                """;
    }

    private String validBranchUpdate(String name, String timezone) {
        return """
                {
                    "name": "%s",
                    "address": "20 Updated Street",
                    "latitude": null,
                    "longitude": null,
                    "timezone": "%s"
                }
                """.formatted(name, timezone);
    }

    private String validServiceUpdate(String name, boolean active) {
        return """
                {
                    "name": "%s",
                    "description": "Updated description",
                    "durationMinutes": 45,
                    "active": %s
                }
                """.formatted(name, active);
    }
}
