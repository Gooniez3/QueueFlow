<?php

namespace App\Http\Controllers;

use App\Presentation\CustomerDemoPresentation;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
        private readonly CustomerDemoPresentation $demoPresentation,
    ) {}

    public function __invoke(): View
    {
        $businesses = $this->apiClient->businesses();
        $classifiedTickets = $this->customerQueueService->classifiedOwnedTickets();
        $resolvedActiveTicket = $classifiedTickets['active'][0] ?? null;
        $activeTicket = $resolvedActiveTicket?->ownership;
        $position = $resolvedActiveTicket?->position;
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
            'presentation' => [
                'liveQueues' => $this->demoPresentation->liveQueues(),
                'activeTicket' => $this->demoPresentation->activeTicketSummary(),
                'ticket' => $this->demoPresentation->ticketDetails(),
            ],
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
