<?php

namespace App\Http\Controllers;

use App\Exceptions\GuestQueueOwnershipException;
use App\Presentation\CustomerDemoPresentation;
use App\Services\GuestQueueOwnershipStore;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\View\View;

class CustomerTicketController extends Controller
{
    public function __construct(
        private readonly GuestQueueOwnershipStore $ownershipStore,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
        private readonly CustomerDemoPresentation $demoPresentation,
    ) {}

    public function index(): View
    {
        return view('tickets.index', [
            'tickets' => $this->ownershipStore->all(),
            'presentation' => $this->demoPresentation->ticketDetails(),
        ]);
    }

    public function show(int $queueId, int $entryId): View
    {
        $ownership = $this->ownershipStore->find($queueId, $entryId);

        if ($ownership === null) {
            throw GuestQueueOwnershipException::missing();
        }

        $position = $this->customerQueueService->position($queueId, $entryId);

        abort_if(
            $position->queueId !== $queueId || $position->entryId !== $entryId,
            404,
            'The requested ticket was not found.',
        );

        return view('tickets.show', [
            'ownership' => $ownership,
            'position' => $position,
            'status' => $this->statusPresentation($position->status),
            'presentation' => $this->demoPresentation->ticketDetails(),
        ]);
    }

    /**
     * @return array{label: string, heading: string, message: string, showWaitingPosition: bool, canCancel: bool}
     */
    private function statusPresentation(string $status): array
    {
        return match ($status) {
            'WAITING' => [
                'label' => 'WAITING',
                'heading' => "You're in the queue",
                'message' => 'Stay nearby and refresh this page for the latest position.',
                'showWaitingPosition' => true,
                'canCancel' => true,
            ],
            'CALLED' => [
                'label' => 'CALLED',
                'heading' => "It's your turn",
                'message' => 'Please make your way to the service area.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
            'SERVING' => [
                'label' => 'SERVING',
                'heading' => 'Now serving',
                'message' => 'Your service is now in progress.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
            'COMPLETED' => [
                'label' => 'COMPLETED',
                'heading' => 'Completed',
                'message' => 'Your service has been completed.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
            'CANCELLED' => [
                'label' => 'CANCELLED',
                'heading' => 'Cancelled',
                'message' => 'This ticket is no longer active.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
            'SKIPPED' => [
                'label' => 'SKIPPED',
                'heading' => 'Skipped',
                'message' => 'Please speak with staff if you still need assistance.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
            default => [
                'label' => 'STATUS UPDATE',
                'heading' => 'Status unavailable',
                'message' => 'Refresh this page or speak with staff for the latest update.',
                'showWaitingPosition' => false,
                'canCancel' => false,
            ],
        };
    }
}
