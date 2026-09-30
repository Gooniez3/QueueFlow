<?php

namespace App\Http\Controllers;

use App\Contracts\QueuePresentationSource;
use Illuminate\View\View;

class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly QueuePresentationSource $queuePresentationSource,
    ) {}

    public function __invoke(): View
    {
        return view('home', [
            'home' => $this->queuePresentationSource->customerHome(),
        ]);
    }
}
