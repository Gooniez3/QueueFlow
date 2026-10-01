package com.queueflow.api.controller;

import com.queueflow.api.request.LoginRequest;
import com.queueflow.api.request.RegisterRequest;
import com.queueflow.api.response.LoginResponse;
import com.queueflow.api.response.MeResponse;
import com.queueflow.api.response.RegisterResponse;
import com.queueflow.api.security.AuthUserPrincipal;
import com.queueflow.api.service.AuthService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/auth")
public class AuthController {

    private final AuthService authService;

    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    @PostMapping("/register")
    public ResponseEntity<RegisterResponse> register(
            @Valid @RequestBody RegisterRequest request
    ) {
        RegisterResponse response =
                authService.register(request);

        return ResponseEntity
                .status(HttpStatus.CREATED)
                .body(response);
    }

    @PostMapping("/login")
    public ResponseEntity<LoginResponse> login(
            @Valid @RequestBody LoginRequest request
    ) {
        LoginResponse response =
                authService.login(request);

        return ResponseEntity.ok(response);
    }

    @GetMapping("/me")
    public ResponseEntity<MeResponse> me(
            @AuthenticationPrincipal
            AuthUserPrincipal principal
    ) {
        MeResponse response =
                authService.me(principal.userId());

        return ResponseEntity.ok(response);
    }
    @PostMapping ("/logout")
    public ResponseEntity<Void> logout(
            @AuthenticationPrincipal
            AuthUserPrincipal principal
    ) {
        authService.logout(principal.sessionId());

        return ResponseEntity.ok().build();
    }
}