<?php

namespace App\Services;

use App\Data\QueueEntryQrVerificationData;

class QueueFlowQrService
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function verify(string $credential): QueueEntryQrVerificationData
    {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueEntryQrVerificationData => $this->apiClient->verifyQueueEntryQrCredential(
                $token,
                $credential,
            ),
        );
    }
}
