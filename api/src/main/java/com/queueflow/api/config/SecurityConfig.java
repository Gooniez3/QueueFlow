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

import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.config.Customizer;
import org.springframework.web.cors.CorsConfiguration;
import org.springframework.web.cors.CorsConfigurationSource;
import org.springframework.web.cors.UrlBasedCorsConfigurationSource;

import java.util.List;

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
                .cors(Customizer.withDefaults())

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
                                "/api/v1/businesses/*/branches/*/staff/dashboard",
                                "/api/v1/businesses/*/branches/*/events"
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
                                "/api/v1/public/discovery",
                                "/api/v1/public/queues/{publicCode}/events"
                        ).permitAll()

                        .requestMatchers(
                                HttpMethod.GET,
                                "/api/v1/branches/*/services/*/slots"
                        ).permitAll()

                        .requestMatchers(
                                HttpMethod.POST,
                                "/api/v1/pre-queue/reservations"
                        ).permitAll()

                        .requestMatchers(
                               HttpMethod.GET,
                               "/api/v1/pre-queue/reservations/*"
                        ).permitAll()

                        .requestMatchers(
                              HttpMethod.POST,
                              "/api/v1/pre-queue/reservations/*/cancel"
                        ).permitAll()

                        .requestMatchers(
                                HttpMethod.PATCH,
                                "/api/v1/pre-queue/reservations/*/reschedule"
                        ).permitAll()

                        .requestMatchers(
                                HttpMethod.POST,
                                "/api/v1/pre-queue/reservations/*/check-in"
                        ).permitAll()

                        // Public queue joining and cancellation.
                        // Guests can use these without authentication.
                        // If a valid bearer token is supplied,
                        // the bearer filter still identifies the user.
                        .requestMatchers(
                                HttpMethod.POST,
                                "/api/v1/queues/*/entries",
                                "/api/v1/queues/*/entries/*/cancel",
                                "/api/v1/queues/*/entries/*/qr-credential"
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
    public CorsConfigurationSource corsConfigurationSource(
        @Value("${queueflow.web.allowed-origin}")
        String allowedOrigin
  ) {
    CorsConfiguration configuration =
            new CorsConfiguration();

    configuration.setAllowedOrigins(
            List.of(allowedOrigin)
    );

    configuration.setAllowedMethods(
            List.of("GET", "OPTIONS")
    );

    configuration.setAllowedHeaders(
            List.of("*")
    );

    configuration.setAllowCredentials(false);

    UrlBasedCorsConfigurationSource source =
            new UrlBasedCorsConfigurationSource();

    source.registerCorsConfiguration(
            "/api/v1/public/queues/*/events",
            configuration
    );

    return source;
  }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }
}
