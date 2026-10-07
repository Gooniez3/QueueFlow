<?php

namespace App\Http\Controllers;

use App\Services\QueueFlowApiClient;
use Illuminate\View\View;

class QueueBoardController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    public function show(string $publicCode): View
    {
        $resolvedQueue = $this->apiClient->resolvePublicQueue($publicCode);
        $board = $this->apiClient->publicQueueBoard($resolvedQueue->queueId);

        return view('queue-board.show', [
            'resolvedQueue' => $resolvedQueue,
            'board' => $board,
            'refreshedAt' => now(),
        ]);
    }
}
