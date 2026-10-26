<?php

namespace App\Http\Controllers;

use App\Data\PublicDiscoveryData;
use App\Exceptions\GuestQueueOwnershipException;
use App\Exceptions\QueueFlowApiException;
use App\Presentation\CustomerDemoPresentation;
use App\Services\GuestFavouriteStore;
use App\Services\GuestQueueOwnershipStore;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use App\Support\BusinessCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerExperienceController extends Controller
{
    public function __construct(
        private readonly CustomerDemoPresentation $demoPresentation,
        private readonly QueueFlowApiClient $apiClient,
        private readonly GuestQueueOwnershipStore $ownershipStore,
        private readonly GuestFavouriteStore $favouriteStore,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
    ) {}

    public function places(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $discovery = $this->apiClient->publicDiscovery(search: $search !== '' ? $search : null);

        return view('places.index', [
            'categories' => $this->categories($discovery),
            'businesses' => $this->businesses($discovery),
            'search' => $search,
        ]);
    }

    public function category(Request $request, string $category): View
    {
        $search = trim((string) $request->query('search', ''));
        $apiCategory = BusinessCategories::valueForSlug($category);
        abort_if($apiCategory === null, 404);
        $discovery = $this->apiClient->publicDiscovery(
            search: $search !== '' ? $search : null,
            category: $apiCategory,
        );

        return view('places.show', [
            'category' => [
                'title' => BusinessCategories::labelForValue($apiCategory),
                'slug' => $category,
                'businesses' => $this->businesses($discovery),
                'search' => $search,
            ],
        ]);
    }

    /** @param  list<PublicDiscoveryData>  $discovery */
    private function categories(array $discovery): array
    {
        $counts = collect($discovery)
            ->groupBy(fn (PublicDiscoveryData $item): string => strtoupper((string) $item->category))
            ->map(fn ($items): int => $items->pluck('businessId')->unique()->count());

        return collect(BusinessCategories::all())
            ->reject(fn (array $category): bool => $category['value'] === 'OTHER')
            ->map(fn (array $category): array => [
                'name' => $category['label'],
                'slug' => $category['slug'],
                'icon' => $category['icon'],
                'count' => $counts->get($category['value'], 0),
            ])
            ->all();
    }

    /** @param  list<PublicDiscoveryData>  $discovery */
    private function businesses(array $discovery): array
    {
        return collect($discovery)
            ->groupBy('businessId')
            ->map(function ($branches): array {
                /** @var PublicDiscoveryData $first */
                $first = $branches->first();

                return [
                    'id' => $first->businessId,
                    'name' => $first->businessName,
                    'description' => $first->businessDescription,
                    'category' => $first->category,
                    'branches' => $branches->map(fn (PublicDiscoveryData $branch): array => [
                        'id' => $branch->branchId,
                        'name' => $branch->branchName,
                        'address' => $branch->address,
                        'services' => $branch->services,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function account(): View
    {
        $tickets = $this->ownershipStore->all();
        $servedCount = $tickets === [] ? 0 : null;

        if ($tickets !== []) {
            try {
                $classified = $this->customerQueueService->classifiedOwnedTickets();

                if ($classified['unclassified'] === [] && $classified['unavailable'] === []) {
                    $servedCount = count(array_filter(
                        $classified['history'],
                        static fn ($ticket): bool => $ticket->position->status === 'COMPLETED',
                    ));
                }
            } catch (QueueFlowApiException|GuestQueueOwnershipException) {
                $servedCount = null;
            }
        }

        return view('account.show', [
            'account' => $this->demoPresentation->account(
                count($tickets),
                $this->favouriteStore->count(),
                $servedCount,
            ),
        ]);
    }

    public function favourites(): View
    {
        $places = [];

        foreach ($this->favouriteStore->all() as $businessId) {
            $business = null;
            $availability = 'missing';

            try {
                $resolved = $this->apiClient->business($businessId);
                $business = $resolved->id === $businessId ? $resolved : null;
            } catch (QueueFlowApiException $exception) {
                // Keep unavailable saved IDs removable without inventing place data.
                $availability = $exception->status === 404 ? 'missing' : 'unavailable';
            }

            $places[] = [
                'id' => $businessId,
                'business' => $business,
                'availability' => $business !== null ? 'available' : ($availability ?? 'missing'),
            ];
        }

        return view('account.favourites', ['favourites' => $places]);
    }

    public function clearDeviceData(): RedirectResponse
    {
        try {
            $this->ownershipStore->clear();
            $this->favouriteStore->clear();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'deviceData' => 'QueueFlow could not clear the saved data. Please try again.',
            ]);
        }

        return redirect()
            ->route('account.show')
            ->with('status', 'Saved QueueFlow data was cleared from this browser.');
    }

    public function more(): View
    {
        return view('more.show', [
            'more' => $this->demoPresentation->more(),
        ]);
    }

    public function information(string $page): View
    {
        return view('more.information', [
            'information' => $this->demoPresentation->informationPage($page),
        ]);
    }

    public function scanner(): View
    {
        return view('scanner.show');
    }
}
