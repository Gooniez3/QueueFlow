<?php

namespace App\Http\Controllers;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\ServiceData;
use App\Services\QueueFlowApiClient;
use Illuminate\View\View;

class CustomerBranchController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    public function show(int $businessId, int $branchId): View
    {
        $business = $this->apiClient->business($businessId);
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureNestedResourcesMatch($business, $branch, $businessId, $branchId);

        $services = $this->apiClient->services($businessId, $branchId);

        foreach ($services as $service) {
            $this->ensureServiceBelongsToBranch($service, $branchId);
        }

        return view('branches.show', [
            'business' => $business,
            'branch' => $branch,
            'services' => $services,
        ]);
    }

    private function ensureNestedResourcesMatch(
        BusinessData $business,
        BranchData $branch,
        int $businessId,
        int $branchId,
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
    }

    private function ensureServiceBelongsToBranch(ServiceData $service, int $branchId): void
    {
        abort_if(
            $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );
    }
}
