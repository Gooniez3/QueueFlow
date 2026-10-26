<?php

namespace App\Http\Controllers;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Exceptions\QueueFlowApiException;
use App\Services\GuestFavouriteStore;
use App\Services\QueueFlowApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerBusinessController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly GuestFavouriteStore $favouriteStore,
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
            'isFavourite' => $this->favouriteStore->has($businessId),
        ]);
    }

    public function addFavourite(int $businessId): RedirectResponse
    {
        if (! $this->favouriteStore->has($businessId)) {
            try {
                $business = $this->apiClient->business($businessId);
                $this->ensureBusinessMatchesRoute($business, $businessId);
            } catch (QueueFlowApiException $exception) {
                if ($exception->status === 404) {
                    return redirect()->route('account.favourites')->withErrors([
                        'favourite' => 'This place is no longer available and was not saved.',
                    ]);
                }

                report($exception);

                return redirect()->route('account.favourites')->withErrors([
                    'favourite' => 'QueueFlow could not verify this place. Please try again.',
                ]);
            }

            $this->favouriteStore->add($businessId);
        }

        return back()->with('status', 'Place added to favourites.');
    }

    public function removeFavourite(int $businessId): RedirectResponse
    {
        $this->favouriteStore->remove($businessId);

        return back()->with('status', 'Place removed from favourites.');
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
