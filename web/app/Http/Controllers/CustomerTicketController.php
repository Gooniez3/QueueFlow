<?php

namespace App\Http\Controllers;

use App\Contracts\QueuePresentationSource;
use Illuminate\View\View;

class CustomerTicketController extends Controller
{
    public function __construct(
        private readonly QueuePresentationSource $queuePresentationSource,
    ) {}

    public function __invoke(): View
    {
        return view('tickets.show', [
            'ticket' => $this->queuePresentationSource->customerTicket(),
        ]);
    }
}
