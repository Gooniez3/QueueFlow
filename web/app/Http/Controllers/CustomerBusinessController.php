<?php

namespace App\Http\Controllers;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Services\QueueFlowApiClient;
use Illuminate\View\View;

class CustomerBusinessController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    public function show(int $businessId): View
    {
        $business = $this->apiClient->business($businessId);
        $branches = $this->apiClient->branches($businessId);

        $this->ensureBusinessMatchesRoute($business, $businessId);

        foreach ($branches as $branch) {
            $this->ensureBranchBelongsToBusiness($branch, $businessId);
        }

        return view('businesses.show', [
            'business' => $business,
            'branches' => $branches,
        ]);
    }

    private function ensureBusinessMatchesRoute(BusinessData $business, int $businessId): void
    {
        abort_if(
            $business->id !== $businessId,
            404,
            'The requested business was not found.',
        );
    }

    private function ensureBranchBelongsToBusiness(BranchData $branch, int $businessId): void
    {
        abort_if(
            $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );
    }
}
