<?php

namespace App\Http\Controllers;

use App\Presentation\CustomerDemoPresentation;
use App\Services\QueueFlowApiClient;
use Illuminate\View\View;

class CustomerExperienceController extends Controller
{
    public function __construct(
        private readonly CustomerDemoPresentation $demoPresentation,
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    public function places(): View
    {
        return view('places.index', [
            'categories' => $this->demoPresentation->placeCategories(),
            'businesses' => $this->apiClient->businesses(),
        ]);
    }

    public function category(string $category): View
    {
        abort_unless(
            collect($this->demoPresentation->placeCategories())->contains('slug', $category),
            404,
        );

        return view('places.show', [
            'category' => $this->demoPresentation->categoryPlaces($category),
        ]);
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
        return view('scanner.show');
    }
}
