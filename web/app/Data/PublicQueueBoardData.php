<?php

namespace App\Data;

final readonly class PublicQueueBoardData
{
    /**
     * @param  list<string>  $upcomingTicketNumbers
     */
    public function __construct(
        public int $queueId,
        public string $name,
        public string $status,
        public ?string $nowServing,
        public ?string $calling,
        public int $waitingCount,
        public array $upcomingTicketNumbers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            queueId: (int) $data['queueId'],
            name: (string) $data['name'],
            status: (string) $data['status'],
            nowServing: isset($data['nowServing'])
                ? (string) $data['nowServing']
                : null,
            calling: isset($data['calling'])
                ? (string) $data['calling']
                : null,
            waitingCount: (int) $data['waitingCount'],
            upcomingTicketNumbers: array_map(
                static fn (mixed $ticketNumber): string => (string) $ticketNumber,
                $data['upcomingTicketNumbers'],
            ),
        );
    }
}
