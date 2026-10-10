<?php

namespace App\Http\Controllers;

use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use App\Services\QueueFlowQrRenderer;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
        private readonly QueueFlowQrRenderer $qrRenderer,
    ) {}

    public function __invoke(): View
    {
        $businesses = $this->apiClient->businesses();
        $classifiedTickets = $this->customerQueueService->classifiedOwnedTickets();
        $resolvedActiveTicket = $classifiedTickets['active'][0] ?? null;
        $activeTicket = $resolvedActiveTicket?->ownership;
        $position = $resolvedActiveTicket?->position;
        $liveQueue = [];
        $qrDataUri = null;

        if ($position !== null) {
            try {
                $board = $this->apiClient->publicQueueBoard($position->queueId);
                $liveQueue = [
                    'nowServing' => $board->nowServing ?? '—',
                    'calling' => $board->calling ?? '—',
                    'upNext' => $board->upcomingTicketNumbers[0] ?? '—',
                ];
            } catch (QueueFlowApiException) {
                $liveQueue = ['nowServing' => '—', 'calling' => '—', 'upNext' => '—'];
            } catch (\Throwable) {
                $liveQueue = ['nowServing' => '—', 'calling' => '—', 'upNext' => '—'];
            }

            if (in_array($position->status, ['WAITING', 'CALLED', 'SERVING'], true)) {
                try {
                    $credential = $this->customerQueueService->issueQrCredential(
                        $position->queueId,
                        $position->entryId,
                    );
                    $qrDataUri = $this->qrRenderer->render($credential->credential);
                } catch (\Throwable) {
                    // The active ticket remains usable when QR issuance is unavailable.
                }
            }
        }
        $activeBusiness = $activeTicket === null
            ? null
            : collect($businesses)->first(
                fn ($business): bool => $business->id === $activeTicket->businessId,
            );

        return view('home', [
            'businesses' => $businesses,
            'activeTicket' => $activeTicket,
            'activeBusiness' => $activeBusiness,
            'position' => $position,
            'statusLabel' => $position === null ? null : $this->statusLabel($position->status),
            'liveQueue' => $liveQueue,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'WAITING' => 'Waiting',
            'CALLED' => 'Called',
            'SERVING' => 'Serving',
            'COMPLETED' => 'Completed',
            'CANCELLED' => 'Cancelled',
            'SKIPPED' => 'Skipped',
            default => 'Status update',
        };
    }
}
