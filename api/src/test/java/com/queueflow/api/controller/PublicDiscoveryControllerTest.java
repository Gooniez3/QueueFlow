package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.test.web.servlet.MockMvc;

import java.math.BigDecimal;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class PublicDiscoveryControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

    @BeforeEach
    void cleanDatabase() {
        authSessionRepository.deleteAll();
        guestJoinIdempotencyRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldReturnDiscoveryResultsWithoutAuthentication()
            throws Exception {

        Business business =
                createBusiness(
                        "QueueFlow Clinic",
                        "Medical clinic",
                        "HEALTH"
                );

        Branch branch =
                createBranch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        "1.352100",
                        "103.819800"
                );

        createService(
                branch,
                "General Consultation",
                "General medical consultation",
                15,
                true
        );

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$[0].businessId")
                        .value(business.getId()))
                .andExpect(jsonPath("$[0].businessName")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$[0].category")
                        .value("HEALTH"))
                .andExpect(jsonPath("$[0].branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$[0].branchName")
                        .value("Downtown Branch"))
                .andExpect(jsonPath("$[0].services[0].name")
                        .value("General Consultation"))
                .andExpect(jsonPath("$[0].distanceKm")
                        .doesNotExist());
    }

    @Test
    void shouldSearchByBusinessBranchAndServiceText()
            throws Exception {

        Business business =
                createBusiness(
                        "TechFix Center",
                        "Device repair specialists",
                        "TECHNOLOGY"
                );

        Branch branch =
                createBranch(
                        business,
                        "Orchard Repair Hub",
                        "88 Orchard Road",
                        null,
                        null
                );

        createService(
                branch,
                "Laptop Repair",
                "Computer repair service",
                30,
                true
        );

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "search",
                                        "laptop"
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(1))
                .andExpect(jsonPath("$[0].businessName")
                        .value("TechFix Center"))
                .andExpect(jsonPath("$[0].branchName")
                        .value("Orchard Repair Hub"))
                .andExpect(jsonPath("$[0].services[0].name")
                        .value("Laptop Repair"));

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "search",
                                        "orchard"
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(1));

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "search",
                                        "techfix"
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(1));
    }

    @Test
    void shouldFilterDiscoveryByCategory()
            throws Exception {

        Business clinic =
                createBusiness(
                        "QueueFlow Clinic",
                        "Medical clinic",
                        "HEALTH"
                );

        Business repairShop =
                createBusiness(
                        "TechFix",
                        "Technology repair",
                        "TECHNOLOGY"
                );

        Branch clinicBranch =
                createBranch(
                        clinic,
                        "Clinic Branch",
                        "1 Health Street",
                        null,
                        null
                );

        Branch repairBranch =
                createBranch(
                        repairShop,
                        "Repair Branch",
                        "2 Tech Street",
                        null,
                        null
                );

        createService(
                clinicBranch,
                "Consultation",
                "Doctor consultation",
                15,
                true
        );

        createService(
                repairBranch,
                "Phone Repair",
                "Phone repair",
                30,
                true
        );

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "category",
                                        "health"
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(1))
                .andExpect(jsonPath("$[0].businessName")
                        .value("QueueFlow Clinic"))
                .andExpect(jsonPath("$[0].category")
                        .value("HEALTH"));
    }

    @Test
    void shouldExcludeInactiveServices()
            throws Exception {

        Business business =
                createBusiness(
                        "QueueFlow Clinic",
                        "Medical clinic",
                        "HEALTH"
                );

        Branch branch =
                createBranch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        null,
                        null
                );

        createService(
                branch,
                "Active Consultation",
                "Available service",
                15,
                true
        );

        createService(
                branch,
                "Old Consultation",
                "Unavailable service",
                20,
                false
        );

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$[0].services.length()")
                        .value(1))
                .andExpect(jsonPath("$[0].services[0].name")
                        .value("Active Consultation"));
    }

    @Test
    void shouldSortNearbyBranchesByDistance()
            throws Exception {

        Business business =
                createBusiness(
                        "QueueFlow Clinics",
                        "Medical clinics",
                        "HEALTH"
                );

        Branch nearby =
                createBranch(
                        business,
                        "Nearby Branch",
                        "Nearby Street",
                        "1.300100",
                        "103.800100"
                );

        Branch farther =
                createBranch(
                        business,
                        "Farther Branch",
                        "Farther Street",
                        "1.400000",
                        "103.900000"
                );

        createService(
                nearby,
                "Consultation",
                "Medical consultation",
                15,
                true
        );

        createService(
                farther,
                "Consultation",
                "Medical consultation",
                15,
                true
        );

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "latitude",
                                        "1.300000"
                                )
                                .param(
                                        "longitude",
                                        "103.800000"
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$[0].branchName")
                        .value("Nearby Branch"))
                .andExpect(jsonPath("$[0].distanceKm")
                        .isNumber())
                .andExpect(jsonPath("$[1].branchName")
                        .value("Farther Branch"))
                .andExpect(jsonPath("$[1].distanceKm")
                        .isNumber());
    }

    @Test
    void shouldRejectIncompleteCoordinates()
            throws Exception {

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "latitude",
                                        "1.3521"
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Latitude and longitude must be provided together"
                        ));
    }

    @Test
    void shouldRejectInvalidCoordinates()
            throws Exception {

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "latitude",
                                        "95"
                                )
                                .param(
                                        "longitude",
                                        "103"
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Latitude must be between -90 and 90"
                        ));

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "latitude",
                                        "1"
                                )
                                .param(
                                        "longitude",
                                        "190"
                                )
                )
                .andExpect(status().isBadRequest())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Longitude must be between -180 and 180"
                        ));
    }

    @Test
    void shouldNotPersistCustomerCoordinates()
            throws Exception {

        Business business =
                createBusiness(
                        "QueueFlow Clinic",
                        "Medical clinic",
                        "HEALTH"
                );

        Branch branch =
                createBranch(
                        business,
                        "Downtown Branch",
                        "123 Main Street",
                        "1.352100",
                        "103.819800"
                );

        createService(
                branch,
                "Consultation",
                "Medical consultation",
                15,
                true
        );

        BigDecimal originalLatitude =
                branch.getLatitude();

        BigDecimal originalLongitude =
                branch.getLongitude();

        mockMvc.perform(
                        get("/api/v1/public/discovery")
                                .param(
                                        "latitude",
                                        "10.000000"
                                )
                                .param(
                                        "longitude",
                                        "20.000000"
                                )
                )
                .andExpect(status().isOk());

        Branch persistedBranch =
                branchRepository
                        .findById(branch.getId())
                        .orElseThrow();

        assertThat(persistedBranch.getLatitude())
                .isEqualByComparingTo(originalLatitude);

        assertThat(persistedBranch.getLongitude())
                .isEqualByComparingTo(originalLongitude);
    }

    private Business createBusiness(
            String name,
            String description,
            String category
    ) {

        Business business =
                new Business(
                        name,
                        description
                );

        business.setCategory(category);

        return businessRepository.save(business);
    }

    private Branch createBranch(
            Business business,
            String name,
            String address,
            String latitude,
            String longitude
    ) {

        BigDecimal latitudeValue =
                latitude == null
                        ? null
                        : new BigDecimal(latitude);

        BigDecimal longitudeValue =
                longitude == null
                        ? null
                        : new BigDecimal(longitude);

        return branchRepository.save(
                new Branch(
                        business,
                        name,
                        address,
                        latitudeValue,
                        longitudeValue
                )
        );
    }

    private com.queueflow.api.entity.Service createService(
            Branch branch,
            String name,
            String description,
            int durationMinutes,
            boolean active
    ) {

        com.queueflow.api.entity.Service service =
                new com.queueflow.api.entity.Service(
                        branch,
                        name,
                        description,
                        durationMinutes
                );

        service.setActive(active);

        return serviceRepository.save(service);
    }
}