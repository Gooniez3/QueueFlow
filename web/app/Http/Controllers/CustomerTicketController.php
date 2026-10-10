<?php

namespace App\Http\Controllers;

use App\Data\GuestQueueOwnershipData;
use App\Exceptions\GuestQueueOwnershipException;
use App\Exceptions\QueueFlowApiException;
use App\Services\GuestQueueOwnershipStore;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use App\Services\QueueFlowQrRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTicketController extends Controller
{
    public function __construct(
        private readonly GuestQueueOwnershipStore $ownershipStore,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowQrRenderer $qrRenderer,
    ) {}

    public function index(Request $request): View
    {
        $classifiedTickets = $this->customerQueueService->classifiedOwnedTickets();
        $tab = $request->query('tab') === 'history' ? 'history' : 'active';

        return view('tickets.index', [
            'activeTickets' => $classifiedTickets['active'],
            'historyTickets' => $classifiedTickets['history'],
            'unclassifiedTickets' => $classifiedTickets['unclassified'],
            'tab' => $tab,
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

        $qrDataUri = null;

        if (in_array($position->status, ['WAITING', 'CALLED', 'SERVING'], true)) {
            try {
                $credential = $this->customerQueueService->issueQrCredential($queueId, $entryId);
                $qrDataUri = $this->qrRenderer->render($credential->credential);
            } catch (QueueFlowApiException) {
                // The ticket remains usable when QR issuance is unavailable.
            } catch (\Throwable) {
                // Rendering a QR must never make an owned ticket unavailable.
            }
        }

        return view('tickets.show', [
            'ownership' => $ownership,
            'position' => $position,
            'status' => $this->statusPresentation($position->status),
            'context' => $this->ticketContext($ownership),
            'qrDataUri' => $qrDataUri,
        ]);
    }

    /** @return array{business: ?object, branch: ?object, service: ?object} */
    private function ticketContext(GuestQueueOwnershipData $ownership): array
    {
        $context = ['business' => null, 'branch' => null, 'service' => null];

        try {
            $context['business'] = $this->apiClient->business($ownership->businessId);
        } catch (\Throwable) {
        }

        try {
            $context['branch'] = $this->apiClient->branch($ownership->businessId, $ownership->branchId);
        } catch (\Throwable) {
        }

        if ($ownership->serviceId !== null) {
            try {
                $context['service'] = $this->apiClient->service(
                    $ownership->businessId,
                    $ownership->branchId,
                    $ownership->serviceId,
                );
            } catch (\Throwable) {
            }
        }

        return $context;
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
