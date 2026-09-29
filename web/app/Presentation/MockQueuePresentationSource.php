<?php

namespace App\Presentation;

use App\Contracts\QueuePresentationSource;

class MockQueuePresentationSource implements QueuePresentationSource
{
    public function customerHome(): array
    {
        $board = $this->queueBoard();
        $ticket = $this->customerTicket();

        return [
            'businessName' => $ticket['businessName'],
            'branchName' => $ticket['branchName'],
            'queueName' => $ticket['queueName'],
            'lastUpdated' => $ticket['lastUpdated'],
            'activeTicket' => [
                'ticketNumber' => $ticket['ticketNumber'],
                'serviceName' => $ticket['serviceName'],
                'status' => $ticket['status'],
                'peopleAhead' => $ticket['peopleAhead'],
                'estimatedWait' => $ticket['estimatedWait'],
                'counter' => $ticket['counter'],
            ],
            'serving' => $board['serving'],
            'calling' => $board['calling'],
            'waiting' => $board['waiting'],
        ];
    }

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

    public function customerTicket(): array
    {
        return [
            'businessName' => 'Northstar Health',
            'branchName' => 'Riverside Clinic',
            'queueName' => 'General Care',
            'ticketNumber' => 'A023',
            'serviceName' => 'General Consultation',
            'status' => 'CALLED',
            'statusMessage' => 'Please make your way to the service area.',
            'peopleAhead' => 0,
            'estimatedWait' => 'Ready now',
            'counter' => 'Counter 2',
            'lastUpdated' => 'Just now',
            'qrPresentation' => [
                'isMock' => true,
                'heading' => 'Scan at the counter',
                'instructions' => 'Show this code to staff to start your service.',
                'ticketLabel' => 'Ticket A023',
                'pattern' => [
                    '11111110001111111',
                    '10000010101000001',
                    '10111010101011101',
                    '10111010001011101',
                    '10111010111011101',
                    '10000010001000001',
                    '11111110101111111',
                    '00000000101000000',
                    '10101111111010101',
                    '01010010100101010',
                    '11101110111011101',
                    '00000000100000000',
                    '11111110101110111',
                    '10000010100010101',
                    '10111010111110111',
                    '10000010010000001',
                    '11111110101111111',
                ],
            ],
        ];
    }
}
