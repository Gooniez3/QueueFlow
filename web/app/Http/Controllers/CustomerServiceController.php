<?php

namespace App\Http\Controllers;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\PublicQueueBoardData;
use App\Data\ServiceData;
use App\Data\TodayQueueData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\View\View;

class CustomerServiceController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
    ) {}

    public function show(
        int $businessId,
        int $branchId,
        int $serviceId,
    ): View {
        $business = $this->apiClient->business($businessId);
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureNestedResourcesMatch(
            $business,
            $branch,
            $service,
            $businessId,
            $branchId,
            $serviceId,
        );

        $queue = $this->applicableQueueOrNull($businessId, $branchId, $serviceId);
        $selectedBoard = $service->active && $queue?->status === 'OPEN'
            ? $this->publicBoardOrNull($queue->id)
            : null;

        return view('services.show', [
            'business' => $business,
            'branch' => $branch,
            'service' => $service,
            'queue' => $queue,
            'selectedBoard' => $selectedBoard,
            'alternativeServices' => $service->active && $queue?->status === 'OPEN'
                ? $this->alternativeServices($businessId, $branchId, $serviceId)
                : [],
        ]);
    }

    /** @return list<array{id: int, name: string, description: ?string, durationMinutes: int, waitingCount: ?int}> */
    private function alternativeServices(
        int $businessId,
        int $branchId,
        int $selectedServiceId,
    ): array {
        try {
            $services = $this->apiClient->services($businessId, $branchId);
        } catch (\Throwable) {
            return [];
        }

        $alternatives = [];

        foreach ($services as $service) {
            if ($service->id === $selectedServiceId
                || $service->branchId !== $branchId
                || ! $service->active) {
                continue;
            }

            $waitingCount = null;

            try {
                $queue = $this->customerQueueService->applicableQueue(
                    $businessId,
                    $branchId,
                    $service->id,
                );
                $board = $this->publicBoardOrNull($queue->id);
                $waitingCount = $board?->waitingCount;
            } catch (\Throwable) {
                // Keep the real service visible without fabricating queue data.
            }

            $alternatives[] = [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'durationMinutes' => $service->durationMinutes,
                'waitingCount' => $waitingCount,
            ];
        }

        return $alternatives;
    }

    private function ensureNestedResourcesMatch(
        BusinessData $business,
        BranchData $branch,
        ServiceData $service,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): void {
        abort_if(
            $business->id !== $businessId,
            404,
            'The requested business was not found.',
        );

        abort_if(
            $branch->id !== $branchId || $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );

        abort_if(
            $service->id !== $serviceId || $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );
    }

    private function applicableQueueOrNull(
        int $businessId,
        int $branchId,
        int $serviceId,
    ): ?TodayQueueData {
        try {
            return $this->customerQueueService->applicableQueue(
                $businessId,
                $branchId,
                $serviceId,
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 404
                && $exception->getMessage() === 'Queue not found for today') {
                return null;
            }

            throw $exception;
        }
    }

    private function publicBoardOrNull(int $queueId): ?PublicQueueBoardData
    {
        try {
            return $this->apiClient->publicQueueBoard($queueId);
        } catch (\Throwable) {
            return null;
        }
    }
}
