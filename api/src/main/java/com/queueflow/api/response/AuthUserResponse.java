package com.queueflow.api.response;

public record AuthUserResponse(
        Long id,
        String email,
        String firstName,
        String lastName,
        String phone
) {
}