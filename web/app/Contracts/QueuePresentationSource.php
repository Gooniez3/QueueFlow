<?php

namespace App\Contracts;

interface QueuePresentationSource
{
    /**
     * @return array{
     *     businessName: string,
     *     branchName: string,
     *     queueName: string,
     *     lastUpdated: string,
     *     activeTicket: array{
     *         ticketNumber: string,
     *         serviceName: string,
     *         status: string,
     *         peopleAhead: int,
     *         estimatedWait: string,
     *         counter: string|null
     *     },
     *     serving: list<array{ticketNumber: string, serviceName: string, counter: string|null}>,
     *     calling: list<array{ticketNumber: string, serviceName: string, counter: string|null}>,
     *     waiting: list<array{ticketNumber: string, serviceName: string, counter: string|null}>
     * }
     */
    public function customerHome(): array;

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

    /**
     * @return array{
     *     businessName: string,
     *     branchName: string,
     *     queueName: string,
     *     ticketNumber: string,
     *     serviceName: string,
     *     status: string,
     *     statusMessage: string,
     *     peopleAhead: int,
     *     estimatedWait: string,
     *     counter: string|null,
     *     lastUpdated: string,
     *     qrPresentation: array{
     *         isMock: bool,
     *         heading: string,
     *         instructions: string,
     *         ticketLabel: string,
     *         pattern: list<string>
     *     }
     * }
     */
    public function customerTicket(): array;
}
