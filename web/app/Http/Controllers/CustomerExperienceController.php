<?php

namespace App\Http\Controllers;

use App\Data\PublicDiscoveryData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Requests\ResolveQueueEntryCodeRequest;
use App\Presentation\CustomerDemoPresentation;
use App\Services\QueueFlowApiClient;
use App\Support\BusinessCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerExperienceController extends Controller
{
    public function __construct(
        private readonly CustomerDemoPresentation $demoPresentation,
        private readonly QueueFlowApiClient $apiClient,
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
        return view('account.show', [
            'account' => $this->demoPresentation->account(),
        ]);
    }

    public function more(): View
    {
        return view('more.show', [
            'more' => $this->demoPresentation->more(),
        ]);
    }

    public function scanner(): View
    {
        $publicUrl = (string) config('services.queueflow.public_web_url');

        return view('scanner.show', [
            'queueFlowPublicOrigin' => parse_url($publicUrl, PHP_URL_SCHEME).'://'.parse_url($publicUrl, PHP_URL_HOST)
                .(parse_url($publicUrl, PHP_URL_PORT) ? ':'.parse_url($publicUrl, PHP_URL_PORT) : ''),
        ]);
    }

    public function resolveQueueEntryCode(ResolveQueueEntryCodeRequest $request): RedirectResponse
    {
        try {
            $resolvedCode = $this->apiClient->resolveQueueEntryCode($request->entryCode());
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 404) {
                return back()
                    ->withErrors(['entryCode' => 'That queue code is invalid. Check the code and try again.'])
                    ->withInput();
            }

            if ($exception->status === 409) {
                return back()
                    ->withErrors(['entryCode' => 'That service is currently unavailable.'])
                    ->withInput();
            }

            report($exception);

            return back()
                ->withErrors(['entryCode' => 'QueueFlow could not check that code right now. Please try again.'])
                ->withInput();
        }

        return redirect()->route('services.show', [
            'businessId' => $resolvedCode->businessId,
            'branchId' => $resolvedCode->branchId,
            'serviceId' => $resolvedCode->serviceId,
        ]);
    }
}
