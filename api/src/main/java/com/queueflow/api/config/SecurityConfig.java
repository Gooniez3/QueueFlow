package com.queueflow.api.config;

import com.queueflow.api.security.BearerTokenAuthenticationFilter;
import com.queueflow.api.security.RestAccessDeniedHandler;
import com.queueflow.api.security.RestAuthenticationEntryPoint;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.http.HttpMethod;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter;

@Configuration
public class SecurityConfig {

    private final BearerTokenAuthenticationFilter
            bearerTokenAuthenticationFilter;

    private final RestAuthenticationEntryPoint
            restAuthenticationEntryPoint;

    private final RestAccessDeniedHandler
            restAccessDeniedHandler;

    public SecurityConfig(
            BearerTokenAuthenticationFilter bearerTokenAuthenticationFilter,
            RestAuthenticationEntryPoint restAuthenticationEntryPoint,
            RestAccessDeniedHandler restAccessDeniedHandler
    ) {
        this.bearerTokenAuthenticationFilter =
                bearerTokenAuthenticationFilter;

        this.restAuthenticationEntryPoint =
                restAuthenticationEntryPoint;

        this.restAccessDeniedHandler =
                restAccessDeniedHandler;
    }

    @Bean
    public SecurityFilterChain securityFilterChain(
            HttpSecurity http
    ) throws Exception {

        http
                .csrf(csrf -> csrf.disable())

                .sessionManagement(session -> session
                        .sessionCreationPolicy(
                                SessionCreationPolicy.STATELESS
                        )
                )

                .exceptionHandling(exception -> exception
                        .authenticationEntryPoint(
                                restAuthenticationEntryPoint
                        )
                        .accessDeniedHandler(
                                restAccessDeniedHandler
                        )
                )

                .authorizeHttpRequests(auth -> auth

                        // Public health + authentication endpoints
                        .requestMatchers(
                                "/api/v1/health",
                                "/api/v1/auth/register",
                                "/api/v1/auth/login"
                        ).permitAll()

                        // Staff dashboard requires authentication.
                        .requestMatchers(
                                HttpMethod.GET,
                                "/api/v1/businesses/*/branches/*/staff/dashboard"
                        ).authenticated()
                        // Public business/branch/service discovery
                        .requestMatchers(
                                HttpMethod.GET,
                                "/api/v1/businesses",
                                "/api/v1/businesses/**"
                        ).permitAll()

                        // Public queue position lookup.
                        // QueueService verifies ownership using either
                        // the authenticated user or X-Guest-Token.
                        .requestMatchers(
                                HttpMethod.GET,
                                "/api/v1/queues/*/entries/*/position",
                                "/api/v1/queues/{queueId}/board",
                                "/api/v1/public/queues/resolve/{publicCode}",
                                "/api/v1/public/discovery"
                        ).permitAll()

                        // Public queue joining and cancellation.
                        // Guests can use these without authentication.
                        // If a valid bearer token is supplied,
                        // the bearer filter still identifies the user.
                        .requestMatchers(
                                HttpMethod.POST,
                                "/api/v1/queues/*/entries",
                                "/api/v1/queues/*/entries/*/cancel"
                        ).permitAll()

                        // Business creation and all other mutations
                        // require authentication.
                        .anyRequest().authenticated()
                )

                .addFilterBefore(
                        bearerTokenAuthenticationFilter,
                        UsernamePasswordAuthenticationFilter.class
                );

        return http.build();
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }
}
