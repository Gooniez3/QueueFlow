package com.queueflow.api.security;

public record AuthUserPrincipal(
        Long userId,
        String email,
        Long sessionId
) {
}