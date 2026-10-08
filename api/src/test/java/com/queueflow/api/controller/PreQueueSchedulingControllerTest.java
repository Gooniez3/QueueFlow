package com.queueflow.api.controller;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.entity.Business;
import com.queueflow.api.entity.PreQueueReservation;
import com.queueflow.api.entity.PreQueueReservationStatus;
import com.queueflow.api.entity.Queue;
import com.queueflow.api.entity.ServiceSession;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.repository.GuestJoinIdempotencyRepository;
import com.queueflow.api.repository.PreQueueReservationRepository;
import com.queueflow.api.repository.QueueEntryRepository;
import com.queueflow.api.repository.QueueRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.repository.ServiceSessionRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.StaffMutationIdempotencyRepository;
import com.queueflow.api.repository.UserAccountRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.ZoneId;

import static org.assertj.core.api.Assertions.assertThat;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.patch;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
class PreQueueSchedulingControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private PreQueueReservationRepository preQueueReservationRepository;

    @Autowired
    private ServiceSessionRepository serviceSessionRepository;

    @Autowired
    private StaffMutationIdempotencyRepository staffMutationIdempotencyRepository;

    @Autowired
    private GuestJoinIdempotencyRepository guestJoinIdempotencyRepository;

    @Autowired
    private QueueEntryRepository queueEntryRepository;

    @Autowired
    private QueueRepository queueRepository;

    @Autowired
    private StaffMembershipRepository staffMembershipRepository;

    @Autowired
    private AuthSessionRepository authSessionRepository;

    @Autowired
    private ServiceRepository serviceRepository;

    @Autowired
    private BranchRepository branchRepository;

    @Autowired
    private BusinessRepository businessRepository;

    @Autowired
    private UserAccountRepository userAccountRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @BeforeEach
    void cleanDatabase() {
        preQueueReservationRepository.deleteAll();
        serviceSessionRepository.deleteAll();
        staffMutationIdempotencyRepository.deleteAll();
        guestJoinIdempotencyRepository.deleteAll();
        queueEntryRepository.deleteAll();
        queueRepository.deleteAll();
        staffMembershipRepository.deleteAll();
        authSessionRepository.deleteAll();
        serviceRepository.deleteAll();
        branchRepository.deleteAll();
        businessRepository.deleteAll();
        userAccountRepository.deleteAll();
    }

    @Test
    void shouldReturnAvailableSlotsWithoutAuthentication()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                new BigDecimal("1.352100"),
                                new BigDecimal("103.819800")
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        LocalDate date =
                LocalDate.now().plusDays(3);

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                date,
                                LocalTime.of(9, 0),
                                LocalTime.of(9, 30),
                                2
                        )
                );

        preQueueReservationRepository.save(
                new PreQueueReservation(
                        session,
                        null,
                        "guest-token-hash"
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/branches/{branchId}/services/{serviceId}/slots",
                                branch.getId(),
                                service.getId()
                        )
                                .param(
                                        "date",
                                        date.toString()
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(1))
                .andExpect(jsonPath("$[0].id")
                        .value(session.getId()))
                .andExpect(jsonPath("$[0].serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$[0].localDate")
                        .value(date.toString()))
                .andExpect(jsonPath("$[0].capacity")
                        .value(2))
                .andExpect(jsonPath("$[0].reservedCount")
                        .value(1))
                .andExpect(jsonPath("$[0].remainingCapacity")
                        .value(1))
                .andExpect(jsonPath("$[0].bookingOpen")
                        .value(true));
    }

    @Test
    void shouldExcludeFullyBookedSlots()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        LocalDate date =
                LocalDate.now().plusDays(3);

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                date,
                                LocalTime.of(10, 0),
                                LocalTime.of(10, 30),
                                1
                        )
                );

        preQueueReservationRepository.save(
                new PreQueueReservation(
                        session,
                        null,
                        "guest-token-hash"
                )
        );

        mockMvc.perform(
                        get(
                                "/api/v1/branches/{branchId}/services/{serviceId}/slots",
                                branch.getId(),
                                service.getId()
                        )
                                .param(
                                        "date",
                                        date.toString()
                                )
                )
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.length()")
                        .value(0));
    }

    @Test
    void shouldReturnNotFoundWhenServiceDoesNotBelongToBranch()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch firstBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        Branch secondBranch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Uptown Branch",
                                "456 High Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                firstBranch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        mockMvc.perform(
                        get(
                                "/api/v1/branches/{branchId}/services/{serviceId}/slots",
                                secondBranch.getId(),
                                service.getId()
                        )
                                .param(
                                        "date",
                                        LocalDate.now()
                                                .plusDays(3)
                                                .toString()
                                )
                )
                .andExpect(status().isNotFound())
                .andExpect(jsonPath("$.status")
                        .value(404))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service not found for branch"
                        ));
    }

    @Test
    void shouldCreateGuestReservation()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now().plusDays(3),
                                LocalTime.of(9, 0),
                                LocalTime.of(9, 30),
                                2
                        )
                );

        mockMvc.perform(
                        post("/api/v1/pre-queue/reservations")
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceSessionId": %d
                                        }
                                        """.formatted(
                                        session.getId()
                                ))
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.serviceSessionId")
                        .value(session.getId()))
                .andExpect(jsonPath("$.serviceId")
                        .value(service.getId()))
                .andExpect(jsonPath("$.branchId")
                        .value(branch.getId()))
                .andExpect(jsonPath("$.status")
                        .value("RESERVED"))
                .andExpect(jsonPath("$.reservationCode")
                        .isNotEmpty())
                .andExpect(jsonPath("$.guestToken")
                        .isNotEmpty())
                .andExpect(jsonPath("$.queueEntryId")
                        .doesNotExist());

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findAll()
                        .get(0);

        assertThat(reservation.getUser())
                .isNull();

        assertThat(reservation.getGuestTokenHash())
                .isNotBlank();

        assertThat(reservation.getQueueEntry())
                .isNull();
    }

    @Test
    void shouldCreateAuthenticatedUserReservation()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now().plusDays(3),
                                LocalTime.of(10, 0),
                                LocalTime.of(10, 30),
                                2
                        )
                );

        UserAccount user =
                createUser(
                        "reservation@example.com",
                        "password123"
                );

        String token =
                loginAndGetToken(
                        "reservation@example.com",
                        "password123"
                );

        mockMvc.perform(
                        post("/api/v1/pre-queue/reservations")
                                .header(
                                        "Authorization",
                                        "Bearer " + token
                                )
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceSessionId": %d
                                        }
                                        """.formatted(
                                        session.getId()
                                ))
                )
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.status")
                        .value("RESERVED"))
                .andExpect(jsonPath("$.guestToken")
                        .doesNotExist());

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findAll()
                        .get(0);

        assertThat(reservation.getUser())
                .isNotNull();

        assertThat(reservation.getUser().getId())
                .isEqualTo(user.getId());

        assertThat(reservation.getGuestTokenHash())
                .isNull();
    }

    @Test
    void shouldRejectReservationWhenSessionIsFullyBooked()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now().plusDays(3),
                                LocalTime.of(11, 0),
                                LocalTime.of(11, 30),
                                1
                        )
                );

        preQueueReservationRepository.save(
                new PreQueueReservation(
                        session,
                        null,
                        "existing-guest-token-hash"
                )
        );

        mockMvc.perform(
                        post("/api/v1/pre-queue/reservations")
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceSessionId": %d
                                        }
                                        """.formatted(
                                        session.getId()
                                ))
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service session is fully booked"
                        ));
    }

    @Test
    void shouldRejectReservationWhenSessionBookingIsClosed()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        ServiceSession session =
                new ServiceSession(
                        service,
                        LocalDate.now().plusDays(3),
                        LocalTime.of(12, 0),
                        LocalTime.of(12, 30),
                        2
                );

        session.setBookingOpen(false);

        session =
                serviceSessionRepository.save(
                        session
                );

        mockMvc.perform(
                        post("/api/v1/pre-queue/reservations")
                                .contentType(
                                        MediaType.APPLICATION_JSON
                                )
                                .content("""
                                        {
                                            "serviceSessionId": %d
                                        }
                                        """.formatted(
                                        session.getId()
                                ))
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.status")
                        .value(409))
                .andExpect(jsonPath("$.message")
                        .value(
                                "Service session is not open for booking"
                        ));
    }
    @Test
void shouldAllowGuestToViewOwnReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    MvcResult result =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String responseBody =
            result.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    responseBody,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    responseBody,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    get(
                            "/api/v1/pre-queue/reservations/{reservationId}",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(reservation.getId()))
            .andExpect(jsonPath("$.reservationCode")
                    .value(reservationCode))
            .andExpect(jsonPath("$.status")
                    .value("RESERVED"))
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());
}

@Test
void shouldReturnExpiredStatusWhenViewingPastCheckInWindow()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalTime now =
            LocalTime.now(
                    ZoneId.of(
                            branch.getTimezone()
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ),
                            now.minusMinutes(60),
                            now.minusMinutes(30),
                            2
                    )
            );

    MvcResult result =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String responseBody =
            result.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    responseBody,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    responseBody,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    get(
                            "/api/v1/pre-queue/reservations/{reservationId}",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.status")
                    .value("EXPIRED"));

    PreQueueReservation expiredReservation =
            preQueueReservationRepository
                    .findById(reservation.getId())
                    .orElseThrow();

    org.junit.jupiter.api.Assertions.assertEquals(
            PreQueueReservationStatus.EXPIRED,
            expiredReservation.getStatus()
    );
}

@Test
void shouldRejectGuestWithWrongReservationToken()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    PreQueueReservation reservation =
            preQueueReservationRepository.save(
                    new PreQueueReservation(
                            session,
                            null,
                            "some-stored-hash"
                    )
            );

    mockMvc.perform(
                    get(
                            "/api/v1/pre-queue/reservations/{reservationId}",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    "wrong-token"
                            )
            )
            .andExpect(status().isForbidden());
}

@Test
void shouldAllowAuthenticatedUserToViewOwnReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(11, 0),
                            LocalTime.of(11, 30),
                            2
                    )
            );

    UserAccount user =
            createUser(
                    "view-reservation@example.com",
                    "password123"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository.save(
                    new PreQueueReservation(
                            session,
                            user,
                            null
                    )
            );

    String token =
            loginAndGetToken(
                    "view-reservation@example.com",
                    "password123"
            );

    mockMvc.perform(
                    get(
                            "/api/v1/pre-queue/reservations/{reservationId}",
                            reservation.getId()
                    )
                            .header(
                                    "Authorization",
                                    "Bearer " + token
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.id")
                    .value(reservation.getId()))
            .andExpect(jsonPath("$.status")
                    .value("RESERVED"))
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());
  }

    @Test
void shouldAllowGuestToCancelOwnReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.status")
                    .value("CANCELLED"))
            .andExpect(jsonPath("$.cancelledAt")
                    .isNotEmpty())
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());

    PreQueueReservation cancelled =
            preQueueReservationRepository
                    .findById(
                            reservation.getId()
                    )
                    .orElseThrow();

    assertThat(cancelled.getStatus().name())
            .isEqualTo("CANCELLED");

    assertThat(cancelled.getCancelledAt())
            .isNotNull();
}

@Test
void shouldRejectCancellationWithWrongGuestToken()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    PreQueueReservation reservation =
            preQueueReservationRepository.save(
                    new PreQueueReservation(
                            session,
                            null,
                            "stored-hash"
                    )
            );

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    "wrong-token"
                            )
            )
            .andExpect(status().isForbidden());
}

@Test
void shouldRejectCancellingExpiredReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalTime now =
            LocalTime.now(
                    ZoneId.of(
                            branch.getTimezone()
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ),
                            now.minusMinutes(60),
                            now.minusMinutes(30),
                            2
                    )
            );

    MvcResult result =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(MediaType.APPLICATION_JSON)
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String responseBody =
            result.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    responseBody,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    responseBody,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Only a reserved reservation can be cancelled"
                    ));

    PreQueueReservation expiredReservation =
            preQueueReservationRepository
                    .findById(reservation.getId())
                    .orElseThrow();

    org.junit.jupiter.api.Assertions.assertEquals(
            PreQueueReservationStatus.EXPIRED,
            expiredReservation.getStatus()
    );
}

@Test
void shouldRejectCancellingReservationTwice()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now().plusDays(3),
                            LocalTime.of(11, 0),
                            LocalTime.of(11, 30),
                            2
                    )
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk());

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.status")
                    .value(409))
            .andExpect(jsonPath("$.message")
                    .value(
                            "Only a reserved reservation can be cancelled"
                    ));
   }

    @Test
void shouldAllowGuestToRescheduleOwnReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalDate date =
            LocalDate.now().plusDays(3);

    ServiceSession originalSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession newSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            originalSession.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    newSession.getId()
                            ))
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.serviceSessionId")
                    .value(newSession.getId()))
            .andExpect(jsonPath("$.status")
                    .value("RESERVED"))
            .andExpect(jsonPath("$.guestToken")
                    .doesNotExist());

    PreQueueReservation updated =
            preQueueReservationRepository
                    .findById(
                            reservation.getId()
                    )
                    .orElseThrow();

    assertThat(updated.getServiceSession().getId())
            .isEqualTo(newSession.getId());
}

@Test
void shouldRejectRescheduleWithWrongGuestToken()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalDate date =
            LocalDate.now().plusDays(3);

    ServiceSession originalSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession newSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    PreQueueReservation reservation =
            preQueueReservationRepository.save(
                    new PreQueueReservation(
                            originalSession,
                            null,
                            "stored-hash"
                    )
            );

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    "wrong-token"
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    newSession.getId()
                            ))
            )
            .andExpect(status().isForbidden());
}

@Test
void shouldRejectRescheduleIntoFullSession()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalDate date =
            LocalDate.now().plusDays(3);

    ServiceSession originalSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession fullSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            1
                    )
            );

    preQueueReservationRepository.save(
            new PreQueueReservation(
                    fullSession,
                    null,
                    "existing-hash"
            )
    );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            originalSession.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    fullSession.getId()
                            ))
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Service session is fully booked"
                    ));
}

@Test
void shouldRejectRescheduleToDifferentService()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service firstService =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    com.queueflow.api.entity.Service secondService =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "Dental Consultation",
                            "Dental service",
                            30
                    )
            );

    LocalDate date =
            LocalDate.now().plusDays(3);

    ServiceSession originalSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            firstService,
                            date,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession differentServiceSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            secondService,
                            date,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            originalSession.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    differentServiceSession.getId()
                            ))
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Reservation can only be rescheduled within the same service"
                    ));
}

@Test
void shouldRejectReschedulingExpiredReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalTime now =
            LocalTime.now(
                    ZoneId.of(
                            branch.getTimezone()
                    )
            );

    ServiceSession expiredSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ),
                            now.minusMinutes(60),
                            now.minusMinutes(30),
                            2
                    )
            );

    ServiceSession futureSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ).plusDays(1),
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    MvcResult result =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(MediaType.APPLICATION_JSON)
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            expiredSession.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String responseBody =
            result.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    responseBody,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    responseBody,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
                            .contentType(MediaType.APPLICATION_JSON)
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    futureSession.getId()
                            ))
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Only a reserved reservation can be rescheduled"
                    ));

    PreQueueReservation expiredReservation =
            preQueueReservationRepository
                    .findById(reservation.getId())
                    .orElseThrow();

    org.junit.jupiter.api.Assertions.assertEquals(
            PreQueueReservationStatus.EXPIRED,
            expiredReservation.getStatus()
    );
}

@Test
void shouldPreventConcurrentReschedulesFromOverbookingLastSlot()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalDate reservationDate =
            LocalDate.now(
                    ZoneId.of(
                            branch.getTimezone()
                    )
            ).plusDays(1);

    ServiceSession sourceSessionOne =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            reservationDate,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession sourceSessionTwo =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            reservationDate,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    ServiceSession targetSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            reservationDate,
                            LocalTime.of(11, 0),
                            LocalTime.of(11, 30),
                            1
                    )
            );

    MvcResult firstCreate =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(MediaType.APPLICATION_JSON)
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            sourceSessionOne.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    MvcResult secondCreate =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(MediaType.APPLICATION_JSON)
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            sourceSessionTwo.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String firstBody =
            firstCreate.getResponse()
                    .getContentAsString();

    String secondBody =
            secondCreate.getResponse()
                    .getContentAsString();

    String firstGuestToken =
            extractJsonString(
                    firstBody,
                    "guestToken"
            );

    String secondGuestToken =
            extractJsonString(
                    secondBody,
                    "guestToken"
            );

    String firstReservationCode =
            extractJsonString(
                    firstBody,
                    "reservationCode"
            );

    String secondReservationCode =
            extractJsonString(
                    secondBody,
                    "reservationCode"
            );

    PreQueueReservation firstReservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            firstReservationCode
                    )
                    .orElseThrow();

    PreQueueReservation secondReservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            secondReservationCode
                    )
                    .orElseThrow();

    java.util.concurrent.ExecutorService executor =
            java.util.concurrent.Executors
                    .newFixedThreadPool(2);

    java.util.concurrent.CountDownLatch ready =
            new java.util.concurrent.CountDownLatch(2);

    java.util.concurrent.CountDownLatch start =
            new java.util.concurrent.CountDownLatch(1);

    try {
        java.util.concurrent.Future<Integer> firstFuture =
                executor.submit(() -> {
                    ready.countDown();
                    start.await();

                    return mockMvc.perform(
                                    patch(
                                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                                            firstReservation.getId()
                                    )
                                            .header(
                                                    "X-Guest-Token",
                                                    firstGuestToken
                                            )
                                            .contentType(
                                                    MediaType.APPLICATION_JSON
                                            )
                                            .content("""
                                                    {
                                                        "serviceSessionId": %d
                                                    }
                                                    """.formatted(
                                                    targetSession.getId()
                                            ))
                            )
                            .andReturn()
                            .getResponse()
                            .getStatus();
                });

        java.util.concurrent.Future<Integer> secondFuture =
                executor.submit(() -> {
                    ready.countDown();
                    start.await();

                    return mockMvc.perform(
                                    patch(
                                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                                            secondReservation.getId()
                                    )
                                            .header(
                                                    "X-Guest-Token",
                                                    secondGuestToken
                                            )
                                            .contentType(
                                                    MediaType.APPLICATION_JSON
                                            )
                                            .content("""
                                                    {
                                                        "serviceSessionId": %d
                                                    }
                                                    """.formatted(
                                                    targetSession.getId()
                                            ))
                            )
                            .andReturn()
                            .getResponse()
                            .getStatus();
                });

        org.junit.jupiter.api.Assertions.assertTrue(
                ready.await(
                        5,
                        java.util.concurrent.TimeUnit.SECONDS
                )
        );

        start.countDown();

        int firstStatus =
                firstFuture.get(
                        10,
                        java.util.concurrent.TimeUnit.SECONDS
                );

        int secondStatus =
                secondFuture.get(
                        10,
                        java.util.concurrent.TimeUnit.SECONDS
                );

        java.util.List<Integer> statuses =
                java.util.List.of(
                        firstStatus,
                        secondStatus
                );

        org.junit.jupiter.api.Assertions.assertEquals(
                1,
                statuses.stream()
                        .filter(status -> status == 200)
                        .count()
        );

        org.junit.jupiter.api.Assertions.assertEquals(
                1,
                statuses.stream()
                        .filter(status -> status == 409)
                        .count()
        );

        long targetReservedCount =
                preQueueReservationRepository
                        .countByServiceSessionIdAndStatus(
                                targetSession.getId(),
                                PreQueueReservationStatus.RESERVED
                        );

        org.junit.jupiter.api.Assertions.assertEquals(
                1,
                targetReservedCount
        );
    } finally {
        executor.shutdownNow();
    }
}

@Test
void shouldRejectReschedulingCancelledReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    LocalDate date =
            LocalDate.now().plusDays(3);

    ServiceSession originalSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(9, 0),
                            LocalTime.of(9, 30),
                            2
                    )
            );

    ServiceSession newSession =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            date,
                            LocalTime.of(10, 0),
                            LocalTime.of(10, 30),
                            2
                    )
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            originalSession.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/cancel",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk());

    mockMvc.perform(
                    patch(
                            "/api/v1/pre-queue/reservations/{reservationId}/reschedule",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
                            .contentType(
                                    MediaType.APPLICATION_JSON
                            )
                            .content("""
                                    {
                                        "serviceSessionId": %d
                                    }
                                    """.formatted(
                                    newSession.getId()
                            ))
            )
            .andExpect(status().isConflict())
            .andExpect(jsonPath("$.message")
                    .value(
                            "Only a reserved reservation can be rescheduled"
                    ));
  }

    @Test
    void shouldAllowGuestToCheckInReservation()
        throws Exception {

    Business business =
            businessRepository.save(
                    new Business(
                            "QueueFlow Clinic",
                            "Medical clinic"
                    )
            );

    Branch branch =
            branchRepository.save(
                    new Branch(
                            business,
                            "Downtown Branch",
                            "123 Main Street",
                            null,
                            null
                    )
            );

    com.queueflow.api.entity.Service service =
            serviceRepository.save(
                    new com.queueflow.api.entity.Service(
                            branch,
                            "General Consultation",
                            "General consultation service",
                            15
                    )
            );

    ServiceSession session =
            serviceSessionRepository.save(
                    new ServiceSession(
                            service,
                            LocalDate.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ),
                            LocalTime.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ).minusMinutes(5),
                            LocalTime.now(
                                    ZoneId.of(
                                            branch.getTimezone()
                                    )
                            ).plusMinutes(5),
                            2
                    )
            );

    Queue queue =
            createServiceQueue(
                    branch,
                    service,
                    "A"
            );

    MvcResult createResult =
            mockMvc.perform(
                            post("/api/v1/pre-queue/reservations")
                                    .contentType(
                                            MediaType.APPLICATION_JSON
                                    )
                                    .content("""
                                            {
                                                "serviceSessionId": %d
                                            }
                                            """.formatted(
                                            session.getId()
                                    )))
                    .andExpect(status().isCreated())
                    .andReturn();

    String body =
            createResult.getResponse()
                    .getContentAsString();

    String guestToken =
            extractJsonString(
                    body,
                    "guestToken"
            );

    String reservationCode =
            extractJsonString(
                    body,
                    "reservationCode"
            );

    PreQueueReservation reservation =
            preQueueReservationRepository
                    .findByReservationCode(
                            reservationCode
                    )
                    .orElseThrow();

    mockMvc.perform(
                    post(
                            "/api/v1/pre-queue/reservations/{reservationId}/check-in",
                            reservation.getId()
                    )
                            .header(
                                    "X-Guest-Token",
                                    guestToken
                            )
            )
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.reservation.status")
                    .value("CHECKED_IN"))
            .andExpect(jsonPath("$.reservation.checkedInAt")
                    .isNotEmpty())
            .andExpect(jsonPath("$.reservation.queueEntryId")
                    .isNumber())
            .andExpect(jsonPath("$.queueEntry.queueId")
                    .value(queue.getId()))
            .andExpect(jsonPath("$.queueEntry.serviceId")
                    .value(service.getId()))
            .andExpect(jsonPath("$.queueEntry.ticketNumber")
                    .value("A001"))
            .andExpect(jsonPath("$.queueEntry.status")
                    .value("WAITING"))
            .andExpect(jsonPath("$.queueEntry.guestToken")
                    .isNotEmpty());

    PreQueueReservation checkedIn =
            preQueueReservationRepository
                    .findById(
                            reservation.getId()
                    )
                    .orElseThrow();

    assertThat(checkedIn.getStatus().name())
            .isEqualTo("CHECKED_IN");

    assertThat(checkedIn.getQueueEntry())
            .isNotNull();

    assertThat(checkedIn.getQueueEntry().getId())
            .isNotNull();
  }


    @Test
    void shouldRejectCheckInBeforeWindowOpens()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        LocalTime now =
                LocalTime.now(
                        ZoneId.of(
                                branch.getTimezone()
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now(
                                        ZoneId.of(
                                                branch.getTimezone()
                                        )
                                ),
                                now.plusMinutes(30),
                                now.plusMinutes(60),
                                2
                        )
                );

        createServiceQueue(
                branch,
                service,
                "A"
        );

        MvcResult createResult =
                mockMvc.perform(
                                post("/api/v1/pre-queue/reservations")
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("""
                                                {
                                                    "serviceSessionId": %d
                                                }
                                                """.formatted(
                                                session.getId()
                                        )))
                        .andExpect(status().isCreated())
                        .andReturn();

        String body =
                createResult.getResponse()
                        .getContentAsString();

        String guestToken =
                extractJsonString(
                        body,
                        "guestToken"
                );

        String reservationCode =
                extractJsonString(
                        body,
                        "reservationCode"
                );

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findByReservationCode(
                                reservationCode
                        )
                        .orElseThrow();

        mockMvc.perform(
                        post(
                                "/api/v1/pre-queue/reservations/{reservationId}/check-in",
                                reservation.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Reservation check-in window has not opened"
                        ));
    }

    @Test
    void shouldRejectCheckInAfterWindowCloses()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        LocalTime now =
                LocalTime.now(
                        ZoneId.of(
                                branch.getTimezone()
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now(
                                        ZoneId.of(
                                                branch.getTimezone()
                                        )
                                ),
                                now.minusMinutes(60),
                                now.minusMinutes(30),
                                2
                        )
                );

        createServiceQueue(
                branch,
                service,
                "A"
        );

        MvcResult createResult =
                mockMvc.perform(
                                post("/api/v1/pre-queue/reservations")
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("""
                                                {
                                                    "serviceSessionId": %d
                                                }
                                                """.formatted(
                                                session.getId()
                                        )))
                        .andExpect(status().isCreated())
                        .andReturn();

        String body =
                createResult.getResponse()
                        .getContentAsString();

        String guestToken =
                extractJsonString(
                        body,
                        "guestToken"
                );

        String reservationCode =
                extractJsonString(
                        body,
                        "reservationCode"
                );

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findByReservationCode(
                                reservationCode
                        )
                        .orElseThrow();

        mockMvc.perform(
                        post(
                                "/api/v1/pre-queue/reservations/{reservationId}/check-in",
                                reservation.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Reservation check-in window has closed"
                        ));
        PreQueueReservation expiredReservation =
                preQueueReservationRepository
                        .findById(reservation.getId())
                        .orElseThrow();

        org.junit.jupiter.api.Assertions.assertEquals(
                PreQueueReservationStatus.EXPIRED,
                expiredReservation.getStatus()
        );
    }

    @Test
    void shouldRejectDuplicateCheckIn()
            throws Exception {

        Business business =
                businessRepository.save(
                        new Business(
                                "QueueFlow Clinic",
                                "Medical clinic"
                        )
                );

        Branch branch =
                branchRepository.save(
                        new Branch(
                                business,
                                "Downtown Branch",
                                "123 Main Street",
                                null,
                                null
                        )
                );

        com.queueflow.api.entity.Service service =
                serviceRepository.save(
                        new com.queueflow.api.entity.Service(
                                branch,
                                "General Consultation",
                                "General consultation service",
                                15
                        )
                );

        ServiceSession session =
                serviceSessionRepository.save(
                        new ServiceSession(
                                service,
                                LocalDate.now(
                                        ZoneId.of(
                                                branch.getTimezone()
                                        )
                                ),
                                LocalTime.now(
                                        ZoneId.of(
                                                branch.getTimezone()
                                        )
                                ).minusMinutes(5),
                                LocalTime.now(
                                        ZoneId.of(
                                                branch.getTimezone()
                                        )
                                ).plusMinutes(5),
                                2
                        )
                );

        createServiceQueue(
                branch,
                service,
                "A"
        );

        MvcResult createResult =
                mockMvc.perform(
                                post("/api/v1/pre-queue/reservations")
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("""
                                                {
                                                    "serviceSessionId": %d
                                                }
                                                """.formatted(
                                                session.getId()
                                        )))
                        .andExpect(status().isCreated())
                        .andReturn();

        String body =
                createResult.getResponse()
                        .getContentAsString();

        String guestToken =
                extractJsonString(
                        body,
                        "guestToken"
                );

        String reservationCode =
                extractJsonString(
                        body,
                        "reservationCode"
                );

        PreQueueReservation reservation =
                preQueueReservationRepository
                        .findByReservationCode(
                                reservationCode
                        )
                        .orElseThrow();

        mockMvc.perform(
                        post(
                                "/api/v1/pre-queue/reservations/{reservationId}/check-in",
                                reservation.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isOk());

        mockMvc.perform(
                        post(
                                "/api/v1/pre-queue/reservations/{reservationId}/check-in",
                                reservation.getId()
                        )
                                .header(
                                        "X-Guest-Token",
                                        guestToken
                                )
                )
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.message")
                        .value(
                                "Only a reserved reservation can be checked in"
                        ));

        assertThat(
                queueEntryRepository.findAll()
        ).hasSize(1);
    }
    private UserAccount createUser(
            String email,
            String rawPassword
    ) {

        UserAccount user =
                new UserAccount(
                        email,
                        passwordEncoder.encode(
                                rawPassword
                        ),
                        "Queue",
                        "Customer",
                        null
                );

        return userAccountRepository.save(
                user
        );
    }

    private String loginAndGetToken(
            String email,
            String password
    ) throws Exception {

        MvcResult result =
                mockMvc.perform(
                                post("/api/v1/auth/login")
                                        .contentType(
                                                MediaType.APPLICATION_JSON
                                        )
                                        .content("""
                                                {
                                                    "email": "%s",
                                                    "password": "%s"
                                                }
                                                """.formatted(
                                                email,
                                                password
                                        )))
                        .andExpect(status().isOk())
                        .andReturn();

        return extractJsonString(
                result.getResponse()
                        .getContentAsString(),
                "token"
        );
    }

    private String extractJsonString(
            String json,
            String fieldName
    ) {

        String marker =
                "\"" + fieldName + "\":\"";

        int start =
                json.indexOf(marker);

        assertThat(start)
                .isGreaterThanOrEqualTo(0);

        start += marker.length();

        int end =
                json.indexOf(
                        "\"",
                        start
                );

        assertThat(end)
                .isGreaterThan(start);

        return json.substring(
                start,
                end
        );
    }

    private Queue createServiceQueue(
            Branch branch,
            com.queueflow.api.entity.Service service,
            String prefix
    ) {

        Queue queue =
                new Queue(
                        branch,
                        service,
                        service.getName() + " Queue",
                        LocalDate.now(
                                ZoneId.of(
                                        branch.getTimezone()
                                )
                        ),
                        prefix
                );

        return queueRepository.save(
                queue
        );
    }
}




