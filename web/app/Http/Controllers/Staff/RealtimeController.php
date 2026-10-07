<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealtimeController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function branch(int $businessId, int $branchId): StreamedResponse
    {
        $upstream = $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token) => $this->apiClient->streamStaffBranchEvents(
                $businessId,
                $branchId,
                $token,
            ),
        );

        return response()->stream(
            function () use ($upstream): void {
                $stream = $upstream->toPsrResponse()->getBody();
                $buffer = '';

                try {
                    while (! $stream->eof()) {
                        if (connection_aborted()) {
                            break;
                        }

                        $chunk = $stream->read(8192);

                        if ($chunk === '') {
                            usleep(10_000);

                            continue;
                        }

                        $buffer .= $chunk;

                        while (preg_match("/\r?\n\r?\n/", $buffer, $separator, PREG_OFFSET_CAPTURE)) {
                            if (connection_aborted()) {
                                break 2;
                            }

                            $frameLength = $separator[0][1] + strlen($separator[0][0]);
                            $frame = substr($buffer, 0, $frameLength);
                            $buffer = substr($buffer, $frameLength);
                            $this->forwardFrame($frame);
                        }
                    }
                } finally {
                    $stream->close();
                }
            },
            IlluminateResponse::HTTP_OK,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ],
        );
    }

    private function forwardFrame(string $frame): void
    {
        if (! preg_match("/\r?\n\r?\n$/", $frame)) {
            return;
        }

        preg_match('/^event:\s*(connected|branch-update)\s*$/m', $frame, $matches);

        if (! isset($matches[1]) || ! preg_match('/^data:/m', $frame)) {
            return;
        }

        echo $frame;

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
