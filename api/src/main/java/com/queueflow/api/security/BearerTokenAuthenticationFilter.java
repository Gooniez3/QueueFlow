package com.queueflow.api.security;

import com.queueflow.api.entity.AuthSession;
import com.queueflow.api.entity.UserAccount;
import com.queueflow.api.repository.AuthSessionRepository;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.time.OffsetDateTime;
import java.util.List;
import java.util.Optional;

@Component
public class BearerTokenAuthenticationFilter
        extends OncePerRequestFilter {

    private static final String BEARER_PREFIX = "Bearer ";

    private final AuthSessionRepository authSessionRepository;
    private final AuthTokenService authTokenService;

    public BearerTokenAuthenticationFilter(
            AuthSessionRepository authSessionRepository,
            AuthTokenService authTokenService
    ) {
        this.authSessionRepository = authSessionRepository;
        this.authTokenService = authTokenService;
    }

    @Override
    protected void doFilterInternal(
            HttpServletRequest request,
            HttpServletResponse response,
            FilterChain filterChain
    ) throws ServletException, IOException {

        String authorizationHeader =
                request.getHeader("Authorization");

        if (authorizationHeader == null
                || !authorizationHeader.startsWith(BEARER_PREFIX)) {

            filterChain.doFilter(request, response);
            return;
        }

        String rawToken = authorizationHeader
                .substring(BEARER_PREFIX.length())
                .trim();

        if (rawToken.isEmpty()) {
            filterChain.doFilter(request, response);
            return;
        }

        String tokenHash =
                authTokenService.hashToken(rawToken);

        Optional<AuthSession> sessionOptional =
                authSessionRepository
                        .findByTokenHashAndRevokedAtIsNull(tokenHash);

        if (sessionOptional.isEmpty()) {
            filterChain.doFilter(request, response);
            return;
        }

        AuthSession session = sessionOptional.get();

        if (session.getExpiresAt()
                .isBefore(OffsetDateTime.now())) {

            filterChain.doFilter(request, response);
            return;
        }

        UserAccount user = session.getUser();

        if (!user.isActive()) {
            filterChain.doFilter(request, response);
            return;
        }

        AuthUserPrincipal principal =
                new AuthUserPrincipal(
                        user.getId(),
                        user.getEmail(),
                        session.getId()
                );

        UsernamePasswordAuthenticationToken authentication =
                new UsernamePasswordAuthenticationToken(
                        principal,
                        null,
                        List.of()
                );

        SecurityContextHolder
                .getContext()
                .setAuthentication(authentication);

        filterChain.doFilter(request, response);
    }
}