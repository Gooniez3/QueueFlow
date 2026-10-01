package com.queueflow.api.service;

import com.queueflow.api.entity.AuthSession;
import com.queueflow.api.entity.StaffMembership;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.exception.EmailAlreadyExistsException;
import com.queueflow.api.exception.InvalidCredentialsException;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.AuthSessionRepository;
import com.queueflow.api.repository.StaffMembershipRepository;
import com.queueflow.api.repository.UserAccountRepository;
import com.queueflow.api.request.LoginRequest;
import com.queueflow.api.request.RegisterRequest;
import com.queueflow.api.response.AuthUserResponse;
import com.queueflow.api.response.LoginResponse;
import com.queueflow.api.response.MeResponse;
import com.queueflow.api.response.MembershipResponse;
import com.queueflow.api.response.RegisterResponse;
import com.queueflow.api.security.AuthTokenService;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.OffsetDateTime;
import java.util.List;
import java.util.Locale;

@Service
public class AuthService {

    private final UserAccountRepository userAccountRepository;
    private final StaffMembershipRepository staffMembershipRepository;
    private final AuthSessionRepository authSessionRepository;
    private final PasswordEncoder passwordEncoder;
    private final AuthTokenService authTokenService;

    public AuthService(
            UserAccountRepository userAccountRepository,
            StaffMembershipRepository staffMembershipRepository,
            AuthSessionRepository authSessionRepository,
            PasswordEncoder passwordEncoder,
            AuthTokenService authTokenService
    ) {
        this.userAccountRepository = userAccountRepository;
        this.staffMembershipRepository = staffMembershipRepository;
        this.authSessionRepository = authSessionRepository;
        this.passwordEncoder = passwordEncoder;
        this.authTokenService = authTokenService;
    }

    @Transactional
    public RegisterResponse register(RegisterRequest request) {

        String normalizedEmail =
                normalizeEmail(request.email());

        if (userAccountRepository
                .existsByEmailIgnoreCase(normalizedEmail)) {

            throw new EmailAlreadyExistsException(
                    "An account with this email already exists"
            );
        }

        String passwordHash =
                passwordEncoder.encode(request.password());

        UserAccount user = new UserAccount(
                normalizedEmail,
                passwordHash,
                request.firstName().trim(),
                request.lastName().trim(),
                normalizePhone(request.phone())
        );

        UserAccount savedUser =
                userAccountRepository.save(user);

        return new RegisterResponse(
                savedUser.getId(),
                savedUser.getEmail(),
                savedUser.getFirstName(),
                savedUser.getLastName(),
                savedUser.getPhone()
        );
    }

    @Transactional
    public LoginResponse login(LoginRequest request) {

        String normalizedEmail =
                normalizeEmail(request.email());

        UserAccount user = userAccountRepository
                .findByEmailIgnoreCase(normalizedEmail)
                .filter(UserAccount::isActive)
                .orElseThrow(() ->
                        new InvalidCredentialsException(
                                "Invalid email or password"
                        )
                );

        if (!passwordEncoder.matches(
                request.password(),
                user.getPasswordHash()
        )) {
            throw new InvalidCredentialsException(
                    "Invalid email or password"
            );
        }

        String rawToken =
                authTokenService.generateToken();

        String tokenHash =
                authTokenService.hashToken(rawToken);

        /*
         * Phase 6 v1:
         * authentication sessions expire
         * 24 hours after login.
         */
        OffsetDateTime expiresAt =
                OffsetDateTime.now().plusHours(24);

        AuthSession authSession = new AuthSession(
                user,
                tokenHash,
                expiresAt
        );

        authSessionRepository.save(authSession);

        List<StaffMembership> memberships =
                staffMembershipRepository
                        .findByUserIdAndActiveTrue(
                                user.getId()
                        );

        List<MembershipResponse> membershipResponses =
                memberships.stream()
                        .map(membership ->
                                new MembershipResponse(
                                        membership
                                                .getBusiness()
                                                .getId(),
                                        membership.getBranch() == null
                                                ? null
                                                : membership
                                                        .getBranch()
                                                        .getId(),
                                        membership.getRole()
                                )
                        )
                        .toList();

        AuthUserResponse userResponse =
                new AuthUserResponse(
                        user.getId(),
                        user.getEmail(),
                        user.getFirstName(),
                        user.getLastName(),
                        user.getPhone()
                );

        return new LoginResponse(
                rawToken,
                "Bearer",
                expiresAt,
                userResponse,
                membershipResponses
        );
    }

    @Transactional(readOnly = true)
    public MeResponse me(Long userId) {

        UserAccount user = userAccountRepository
                .findById(userId)
                .filter(UserAccount::isActive)
                .orElseThrow(() ->
                        new ResourceNotFoundException(
                                "User account not found"
                        )
                );

        List<StaffMembership> memberships =
                staffMembershipRepository
                        .findByUserIdAndActiveTrue(userId);

        List<MembershipResponse> membershipResponses =
                memberships.stream()
                        .map(membership ->
                                new MembershipResponse(
                                        membership
                                                .getBusiness()
                                                .getId(),
                                        membership.getBranch() == null
                                                ? null
                                                : membership
                                                        .getBranch()
                                                        .getId(),
                                        membership.getRole()
                                )
                        )
                        .toList();

        AuthUserResponse userResponse =
                new AuthUserResponse(
                        user.getId(),
                        user.getEmail(),
                        user.getFirstName(),
                        user.getLastName(),
                        user.getPhone()
                );

        return new MeResponse(
                userResponse,
                membershipResponses
        );
    }

    @Transactional
    public void logout(Long sessionId) {

        AuthSession session = authSessionRepository
                .findById(sessionId)
                .orElseThrow(() ->
                        new InvalidCredentialsException(
                                "Authentication session not found"
                        )
                );

        if (!session.isRevoked()) {
            session.setRevokedAt(
                    OffsetDateTime.now()
            );
        }
    }

    private String normalizeEmail(String email) {

        return email
                .trim()
                .toLowerCase(Locale.ROOT);
    }

    private String normalizePhone(String phone) {

        if (phone == null) {
            return null;
        }

        String trimmedPhone = phone.trim();

        return trimmedPhone.isEmpty()
                ? null
                : trimmedPhone;
    }
}