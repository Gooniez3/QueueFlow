<?php

namespace App\Http\Controllers;

use App\Contracts\QueuePresentationSource;
use App\Presentation\CustomerDemoPresentation;
use App\Services\GuestQueueOwnershipStore;
use Illuminate\View\View;

class QueueBoardController extends Controller
{
    public function __construct(
        private readonly QueuePresentationSource $queuePresentationSource,
        private readonly GuestQueueOwnershipStore $ownershipStore,
        private readonly CustomerDemoPresentation $demoPresentation,
    ) {}

    public function __invoke(): View
    {
        return view('queue-board.show', [
            'board' => $this->queuePresentationSource->queueBoard(),
            'ownedTicket' => $this->ownershipStore->all()[0] ?? null,
            'presentation' => $this->demoPresentation->queueBoard(),
        ]);
    }
}
