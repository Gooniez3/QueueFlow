<?php

namespace App\Presentation;

use App\Contracts\QueuePresentationSource;

class MockQueuePresentationSource implements QueuePresentationSource
{
    public function queueBoard(): array
    {
        return [
            'businessName' => 'Northstar Health',
            'branchName' => 'Riverside Clinic',
            'queueName' => 'General Care',
            'queueStatus' => 'OPEN',
            'lastUpdated' => 'Just now',
            'serving' => [
                ['ticketNumber' => 'A021', 'serviceName' => 'General Consultation', 'counter' => 'Counter 1'],
                ['ticketNumber' => 'A022', 'serviceName' => 'General Consultation', 'counter' => null],
            ],
            'calling' => [
                ['ticketNumber' => 'A023', 'serviceName' => 'General Consultation', 'counter' => 'Counter 2'],
            ],
            'waiting' => [
                ['ticketNumber' => 'A024', 'serviceName' => 'General Consultation', 'counter' => null],
                ['ticketNumber' => 'A025', 'serviceName' => 'General Consultation', 'counter' => null],
                ['ticketNumber' => 'A026', 'serviceName' => 'General Consultation', 'counter' => null],
            ],
        ];
    }
}
