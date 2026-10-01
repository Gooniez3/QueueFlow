package com.queueflow.api.response;

import java.time.OffsetDateTime;
import java.util.List;

public record LoginResponse(
        String token,
        String tokenType,
        OffsetDateTime expiresAt,
        AuthUserResponse user,
        List<MembershipResponse> memberships
) {
}