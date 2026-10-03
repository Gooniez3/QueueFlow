<?php

namespace App\Contracts;

interface QueuePresentationSource
{
    /**
     * @return array{
     *     businessName: string,
     *     branchName: string,
     *     queueName: string,
     *     queueStatus: string,
     *     lastUpdated: string,
     *     serving: list<array{ticketNumber: string, serviceName: string, counter: string|null}>,
     *     calling: list<array{ticketNumber: string, serviceName: string, counter: string|null}>,
     *     waiting: list<array{ticketNumber: string, serviceName: string, counter: string|null}>
     * }
     */
    public function queueBoard(): array;
}
