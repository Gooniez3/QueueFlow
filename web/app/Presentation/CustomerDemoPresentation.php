<?php

namespace App\Presentation;

final readonly class CustomerDemoPresentation
{
    /**
     * @return list<array{name: string, nowServing: string, waiting: int}>
     */
    public function liveQueues(): array
    {
        return [
            ['name' => 'QueueFlow Clinic', 'nowServing' => 'A-018', 'waiting' => 12],
            ['name' => 'QueueFlow Bank', 'nowServing' => 'B-041', 'waiting' => 4],
            ['name' => 'CapyTech', 'nowServing' => 'C-007', 'waiting' => 0],
        ];
    }

    /**
     * @return array{nowServing: string, calling: string, upNext: string, alertsEnabled: bool}
     */
    public function activeTicketSummary(): array
    {
        return [
            'nowServing' => 'A-018',
            'calling' => 'A-019',
            'upNext' => 'A-020',
            'alertsEnabled' => true,
        ];
    }

    /**
     * @return list<array{slug: string, name: string, count: int, color: string, icon: string}>
     */
    public function placeCategories(): array
    {
        return [
            ['slug' => 'hospital-clinic', 'name' => 'Hospital / Clinic', 'count' => 18, 'color' => 'indigo', 'icon' => 'plus'],
            ['slug' => 'bank-service', 'name' => 'Bank / Service', 'count' => 12, 'color' => 'teal', 'icon' => 'bank'],
            ['slug' => 'public-service', 'name' => 'Public Service', 'count' => 9, 'color' => 'blue', 'icon' => 'people'],
            ['slug' => 'restaurant', 'name' => 'Restaurant', 'count' => 24, 'color' => 'red', 'icon' => 'food'],
            ['slug' => 'event', 'name' => 'Event', 'count' => 6, 'color' => 'purple', 'icon' => 'calendar'],
            ['slug' => 'retail-tech', 'name' => 'Retail / Tech', 'count' => 15, 'color' => 'yellow', 'icon' => 'bag'],
        ];
    }

    /**
     * @return array{title: string, places: list<array{initials: string, name: string, area: string, distance: string, waiting: int, open: bool, tone: string}>}
     */
    public function categoryPlaces(string $slug): array
    {
        $title = collect($this->placeCategories())
            ->firstWhere('slug', $slug)['name'] ?? 'Places';

        return [
            'title' => $title,
            'places' => [
                ['initials' => 'QF', 'name' => 'QueueFlow Clinic', 'area' => 'Central', 'distance' => '0.3 km', 'waiting' => 12, 'open' => true, 'tone' => 'indigo'],
                ['initials' => 'NF', 'name' => 'Northgate Family Clinic', 'area' => 'Northgate', 'distance' => '1.1 km', 'waiting' => 5, 'open' => true, 'tone' => 'teal'],
                ['initials' => 'HM', 'name' => 'Harbour Medical Centre', 'area' => 'Harbour', 'distance' => '1.8 km', 'waiting' => 9, 'open' => true, 'tone' => 'blue'],
                ['initials' => 'WP', 'name' => 'Willow Pharmacy', 'area' => 'Eastside', 'distance' => '2.0 km', 'waiting' => 2, 'open' => true, 'tone' => 'red'],
                ['initials' => 'ED', 'name' => 'Eastside Dental', 'area' => 'Eastside', 'distance' => '2.4 km', 'waiting' => 0, 'open' => true, 'tone' => 'purple'],
            ],
        ];
    }

    /**
     * @return array{qrPattern: list<string>, joinedAt: string, date: string, partySize: string}
     */
    public function ticketDetails(): array
    {
        return [
            'qrPattern' => [
                '1111101011111',
                '1000100010001',
                '1011101110111',
                '1011101010111',
                '1011101110111',
                '1000100010001',
                '1111101011111',
                '0000011100000',
                '1011110110101',
                '0010011011000',
                '1111101110111',
                '1000100100101',
                '1111101111101',
            ],
            'joinedAt' => '2:41 PM',
            'date' => 'Today',
            'partySize' => '1 person',
        ];
    }

    /**
     * @return array{queuesJoined: int, favourites: int, served: int, language: string, version: string}
     */
    public function account(): array
    {
        return [
            'queuesJoined' => 12,
            'favourites' => 3,
            'served' => 9,
            'language' => 'English',
            'version' => '1.0.0 preview',
        ];
    }

    /**
     * @return array{version: string}
     */
    public function more(): array
    {
        return ['version' => '1.0.0 preview'];
    }

    /**
     * @return array{waiting: int, averageWaitMinutes: int, servedToday: int, recentlyServed: list<array{ticketNumber: string, minutesAgo: int}>}
     */
    public function queueBoard(): array
    {
        return [
            'waiting' => 12,
            'averageWaitMinutes' => 15,
            'servedToday' => 86,
            'recentlyServed' => [
                ['ticketNumber' => 'A-017', 'minutesAgo' => 3],
                ['ticketNumber' => 'A-016', 'minutesAgo' => 8],
            ],
        ];
    }

    /**
     * @return array{number: string, peopleAhead: int, estimatedWaitMinutes: int, alternatives: list<array{name: string, waiting: int, estimatedWaitMinutes: int}>}
     */
    public function joinPreview(): array
    {
        return [
            'number' => 'A-???',
            'peopleAhead' => 5,
            'estimatedWaitMinutes' => 15,
            'alternatives' => [
                ['name' => 'Health screening', 'waiting' => 4, 'estimatedWaitMinutes' => 6],
                ['name' => 'Vaccination', 'waiting' => 0, 'estimatedWaitMinutes' => 0],
            ],
        ];
    }
}
