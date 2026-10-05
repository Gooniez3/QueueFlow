<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\ServiceData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreQueueRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowQueueService $queueService,
    ) {}

    public function create(Request $request, int $businessId, int $branchId): View
    {
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        $services = $this->apiClient->services($businessId, $branchId);

        foreach ($services as $service) {
            $this->ensureServiceBelongsToBranch($service, $branchId);
        }

        return view('staff.queues.create', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'services' => $services,
        ]);
    }

    public function store(
        StoreQueueRequest $request,
        int $businessId,
        int $branchId,
    ): RedirectResponse {
        $data = $request->validated();
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        try {
            $queue = $this->queueService->createQueue(
                $businessId,
                $branchId,
                $data['queueType'] === 'service' ? (int) $data['serviceId'] : null,
                $data['name'],
                $data['ticketPrefix'],
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 400) {
                return redirect()
                    ->route('staff.live-queues.create', [$businessId, $branchId])
                    ->withErrors($this->validationErrors($exception))
                    ->withInput();
            }

            if ($exception->status === 409) {
                return redirect()
                    ->route('staff.live-queues.create', [$businessId, $branchId])
                    ->withErrors([
                        'queue' => 'The queue could not be opened because today\'s queue state changed or a queue already exists.',
                    ])
                    ->withInput();
            }

            throw $exception;
        }

        return redirect()
            ->route('staff.live-queues.index', [
                'businessId' => $businessId,
                'branchId' => $branchId,
                'queue' => $queue->id,
            ])
            ->with('status', 'Queue opened successfully.');
    }

    private function ensureBranchBelongsToBusiness(BranchData $branch, int $businessId): void
    {
        abort_if(
            $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );
    }

    private function ensureServiceBelongsToBranch(ServiceData $service, int $branchId): void
    {
        abort_if(
            $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );
    }

    /**
     * @return array<string, string>
     */
    private function validationErrors(QueueFlowApiException $exception): array
    {
        $errors = [];

        foreach (['serviceId', 'name', 'ticketPrefix'] as $field) {
            $message = $exception->validationErrors[$field] ?? null;

            if (is_string($message) && $message !== '') {
                $errors[$field] = $message;
            }
        }

        return $errors !== []
            ? $errors
            : ['queue' => 'We could not open the queue. Please review the details and try again.'];
    }
}
