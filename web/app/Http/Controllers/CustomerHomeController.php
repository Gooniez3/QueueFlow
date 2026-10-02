<?php

namespace App\Http\Controllers;

use App\Services\QueueFlowApiClient;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
    ) {}

    public function __invoke(): View
    {
        return view('home', [
            'businesses' => $this->apiClient->businesses(),
        ]);
    }
}
