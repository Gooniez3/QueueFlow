<?php

namespace App\Services;

use App\Data\StaffDashboardData;
use App\Data\StaffDashboardQueueData;
use App\Exceptions\QueueFlowApiException;

class StaffDashboardPresentationService
{
    public function __construct(
        private readonly QueueFlowQueueService $queueService,
    ) {}

    /**
     * @return array{dashboard: ?StaffDashboardData, unavailable: bool}
     */
    public function forBranch(int $businessId, int $branchId): array
    {
        try {
            $dashboard = $this->queueService->staffDashboard($businessId, $branchId);
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 401) {
                throw $exception;
            }

            if (! in_array($exception->status, [403, 404], true)) {
                report($exception);
            }

            return ['dashboard' => null, 'unavailable' => true];
        }

        if ($dashboard->businessId !== $businessId || $dashboard->branchId !== $branchId) {
            report(new \UnexpectedValueException('QueueFlow dashboard hierarchy did not match the requested branch.'));

            return ['dashboard' => null, 'unavailable' => true];
        }

        return ['dashboard' => $dashboard, 'unavailable' => false];
    }

    /**
     * @return array{queue: ?StaffDashboardQueueData, ambiguous: bool}
     */
    public function queueForService(?StaffDashboardData $dashboard, int $serviceId): array
    {
        if ($dashboard === null) {
            return ['queue' => null, 'ambiguous' => false];
        }

        $matches = array_values(array_filter(
            $dashboard->queues,
            static fn (StaffDashboardQueueData $queue): bool => $queue->service?->id === $serviceId,
        ));

        return count($matches) === 1
            ? ['queue' => $matches[0], 'ambiguous' => false]
            : ['queue' => null, 'ambiguous' => count($matches) > 1];
    }
}
