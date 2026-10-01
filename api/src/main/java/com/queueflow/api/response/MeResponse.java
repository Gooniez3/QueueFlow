package com.queueflow.api.response;

import java.util.List;

public record MeResponse(
        AuthUserResponse user,
        List<MembershipResponse> memberships
) {
}