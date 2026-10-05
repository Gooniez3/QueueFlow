<?php

namespace App\Services;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\ServiceData;
use App\Data\StaffMembershipData;

final class StaffCatalogService
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    /**
     * @param  list<StaffMembershipData>  $memberships
     * @return list<array{business: BusinessData, branches: list<BranchData>}>
     */
    public function accessibleBusinesses(
        array $memberships,
        bool $includeBusinessesWithoutBranches = true,
    ): array {
        $membershipsByBusinessId = $this->membershipsByBusinessId($memberships);
        $businesses = [];

        foreach ($membershipsByBusinessId as $businessId => $businessMemberships) {
            $branches = $this->accessibleBranches(
                $this->apiClient->branches($businessId),
                $businessMemberships,
            );

            if ($branches === [] && ! $includeBusinessesWithoutBranches) {
                continue;
            }

            $businesses[] = [
                'business' => $this->apiClient->business($businessId),
                'branches' => $branches,
            ];
        }

        return $businesses;
    }

    /**
     * @param  list<StaffMembershipData>  $memberships
     * @return list<array{
     *     business: BusinessData,
     *     branches: list<BranchData>,
     *     servicesByBranchId: array<int, list<ServiceData>>
     * }>
     */
    public function accessibleBusinessesWithServices(array $memberships): array
    {
        return array_map(function (array $businessContext): array {
            $business = $businessContext['business'];
            $servicesByBranchId = [];

            foreach ($businessContext['branches'] as $branch) {
                $servicesByBranchId[$branch->id] = $this->apiClient->services(
                    $business->id,
                    $branch->id,
                );
            }

            return [
                ...$businessContext,
                'servicesByBranchId' => $servicesByBranchId,
            ];
        }, $this->accessibleBusinesses($memberships));
    }

    /**
     * @param  list<StaffMembershipData>  $memberships
     * @return array<int, list<StaffMembershipData>>
     */
    private function membershipsByBusinessId(array $memberships): array
    {
        $membershipsByBusinessId = [];

        foreach ($memberships as $membership) {
            $membershipsByBusinessId[$membership->businessId][] = $membership;
        }

        return $membershipsByBusinessId;
    }

    /**
     * @param  list<BranchData>  $branches
     * @param  list<StaffMembershipData>  $memberships
     * @return list<BranchData>
     */
    private function accessibleBranches(array $branches, array $memberships): array
    {
        $hasBusinessWideMembership = collect($memberships)
            ->contains(static fn (StaffMembershipData $membership): bool => $membership->branchId === null);

        if ($hasBusinessWideMembership) {
            return $branches;
        }

        $accessibleBranchIds = array_values(array_unique(array_filter(array_map(
            static fn (StaffMembershipData $membership): ?int => $membership->branchId,
            $memberships,
        ))));

        return array_values(array_filter(
            $branches,
            static fn (BranchData $branch): bool => in_array($branch->id, $accessibleBranchIds, true),
        ));
    }
}
