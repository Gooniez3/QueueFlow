package com.queueflow.api.response;

public record RegisterResponse(
        Long id,
        String email,
        String firstName,
        String lastName,
        String phone
) {
}