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
     * @return array{queuesJoined: int, favourites: int, served: ?int, version: string}
     */
    public function account(int $queuesJoined, int $favourites, ?int $served): array
    {
        return [
            'queuesJoined' => $queuesJoined,
            'favourites' => $favourites,
            'served' => $served,
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
     * @return array{
     *     title: string,
     *     introduction?: string,
     *     status?: string,
     *     reviewNote?: string,
     *     sections: list<array{heading: string, body?: string, paragraphs?: list<string>, items?: list<string>, afterParagraphs?: list<string>}>
     * }
     */
    public function informationPage(string $page): array
    {
        return match ($page) {
            'about' => [
                'title' => 'About QueueFlow',
                'introduction' => 'QueueFlow helps people discover participating services, check queue availability, join when available, and follow their ticket from one place.',
                'sections' => [
                    ['heading' => 'For customers', 'body' => 'Customers can browse participating places and services, join an available queue, and follow their ticket from the same device.'],
                    ['heading' => 'For service teams', 'body' => 'Participating businesses and staff can organize queues, manage customer flow, and keep customers informed.'],
                ],
            ],
            'how-it-works' => [
                'title' => 'How it works',
                'introduction' => 'Use QueueFlow to find a service and understand its queue before deciding what to do next.',
                'sections' => [
                    ['heading' => 'Find a place', 'body' => 'Browse Places and choose a participating business, branch, and service.'],
                    ['heading' => 'Check the service', 'body' => 'Review the service details and whether its queue is available.'],
                    ['heading' => 'Join when available', 'body' => 'Follow the on-screen steps to join an open queue.'],
                    ['heading' => 'Keep your ticket handy', 'body' => 'Return to Tickets on this device to view your queue entry.'],
                ],
            ],
            'faq' => [
                'title' => 'Frequently asked questions',
                'introduction' => 'A few quick answers about using QueueFlow.',
                'sections' => [
                    ['heading' => 'Does scanning a service QR code join the queue?', 'body' => 'No. It opens the service preview. You join only if you continue through and submit the separate join flow.'],
                    ['heading' => 'Why is joining unavailable?', 'body' => 'The service or queue may be inactive or closed. The service page shows when joining is unavailable.'],
                    ['heading' => 'Do I need an account?', 'body' => 'This customer preview supports guest use. Ticket information is available from this device.'],
                    ['heading' => 'Where can I find my ticket?', 'body' => 'Open Tickets from the bottom navigation on the device where you joined.'],
                ],
            ],
            'help' => [
                'title' => 'Help and Support',
                'introduction' => 'Use the FAQ and troubleshooting guidance below to find answers about using QueueFlow.',
                'sections' => [
                    ['heading' => 'Check the FAQ', 'body' => 'The FAQ covers joining a queue, service availability, and finding a ticket.'],
                    ['heading' => 'Queue unavailable', 'body' => 'The service or queue may be inactive or closed. The service page shows when joining is unavailable.'],
                    ['heading' => 'Finding your ticket', 'body' => 'Open Tickets from the bottom navigation on the device where you joined.'],
                    ['heading' => 'Understanding service QR codes', 'body' => 'Scanning a service QR code opens a preview. It does not automatically join the queue or create a ticket.'],
                    ['heading' => 'Contact information', 'body' => 'Official support contact details are not available in this preview.'],
                ],
            ],
            'terms' => [
                'title' => 'Terms and Conditions',
                'status' => 'Portfolio demo · Policy draft',
                'reviewNote' => 'This document is being finalized before public release.',
                'sections' => [
                    [
                        'heading' => '1. About QueueFlow',
                        'paragraphs' => [
                            'QueueFlow is a software portfolio and demonstration project designed to showcase digital queue management, including business discovery, service queues, queue tickets, and related features.',
                            'QueueFlow is currently provided for demonstration, evaluation, and testing purposes. Unless expressly identified otherwise, queues and businesses displayed in the demo should not be treated as real-world service appointments.',
                            'QueueFlow is not currently a guaranteed real-world queue service. Demonstration queues and data may be reset during development and testing.',
                            'Testers should not submit real sensitive, confidential, or unnecessary personal information.',
                        ],
                    ],
                    [
                        'heading' => '2. Acceptance of Terms',
                        'paragraphs' => [
                            'By accessing or using QueueFlow, you agree to follow these Terms and Conditions.',
                            'If you do not agree, please discontinue using the application.',
                        ],
                    ],
                    [
                        'heading' => '3. Permitted Use',
                        'paragraphs' => [
                            'You may use QueueFlow to explore available features, browse demonstration businesses and services, and interact with queue functionality where access is permitted.',
                            'You must not:',
                        ],
                        'items' => [
                            'Attempt to access another user\'s private information or tickets, or business functions, without authorization.',
                            'Interfere with the platform or its availability.',
                            'Submit unlawful, malicious, or misleading content.',
                            'Attempt to bypass authentication or access controls.',
                            'Use automated tools to overload the service.',
                        ],
                    ],
                    [
                        'heading' => '4. Queue Participation and Tickets',
                        'paragraphs' => [
                            'QueueFlow may allow users to join demonstration queues and obtain digital tickets, but it is not currently a guaranteed real-world queue service.',
                            'Queue positions, waiting estimates, service availability, and ticket statuses are provided for demonstration purposes and may change.',
                            'A demonstration ticket does not guarantee service from a real business.',
                        ],
                    ],
                    [
                        'heading' => '5. Guest Sessions and Saved Information',
                        'paragraphs' => [
                            'Some features use browser cookies and guest sessions to maintain ticket references, favourites, and other temporary application state.',
                            'Guest information may become unavailable when sessions expire, browser data is cleared, or demonstration data is reset.',
                            'Users should not rely on QueueFlow as permanent storage for important information.',
                        ],
                    ],
                    [
                        'heading' => '6. Availability and Changes',
                        'paragraphs' => [
                            'QueueFlow may be updated, interrupted, reset, or temporarily unavailable during development and testing.',
                            'Features and demonstration records may be changed or removed without prior notice.',
                        ],
                    ],
                    [
                        'heading' => '7. Intellectual Property',
                        'paragraphs' => [
                            'The QueueFlow application, including its original software and interface designs, is developed as a portfolio project.',
                            'Nothing in these Terms grants users ownership of QueueFlow\'s software or branding. Third-party materials remain subject to their respective rights.',
                        ],
                    ],
                    [
                        'heading' => '8. Disclaimer',
                        'paragraphs' => [
                            'QueueFlow is provided on an "as available" basis for demonstration purposes.',
                            'We do not guarantee uninterrupted availability, accurate waiting estimates, or suitability for operational queue management.',
                            'Nothing in these Terms excludes rights or liabilities that cannot lawfully be excluded.',
                        ],
                    ],
                    [
                        'heading' => '9. Changes to These Terms',
                        'paragraphs' => [
                            'These Terms may be updated as QueueFlow develops. Material changes will be reflected in the published version and its effective date.',
                        ],
                    ],
                    [
                        'heading' => '10. Contact',
                        'paragraphs' => [
                            'Project operator and contact details will be added and verified before public publication.',
                        ],
                    ],
                ],
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'status' => 'Portfolio demo · Policy draft',
                'reviewNote' => 'This document is being finalized before public release.',
                'sections' => [
                    [
                        'heading' => '1. Introduction',
                        'paragraphs' => [
                            'QueueFlow is a portfolio and demonstration application for digital queue management.',
                            'This Privacy Policy describes the types of information QueueFlow may process, how the application uses that information, and the controls available to visitors.',
                            'The details below must be confirmed against the deployed application before publication.',
                        ],
                    ],
                    [
                        'heading' => '2. Information Processed',
                        'paragraphs' => [
                            'Depending on the features used, QueueFlow processes the following information:',
                        ],
                        'items' => [
                            'Staff and business account details: email address, first and last name, optional phone number, password hash, account identifier, roles, and business memberships.',
                            'Queue information: ticket identifiers and numbers, queue participation and status, party size when provided, and relevant timestamps.',
                            'Guest ticket access is associated with a token credential; the backend stores a hash of the guest token with the queue record.',
                            'Reservation and notification records related to queue features.',
                            'Favourite business identifiers, saved guest ticket references, pending queue-join identifiers, and authenticated staff session credentials in Laravel server-side sessions.',
                            'Business, branch, and service information submitted by business owners and staff, including branch address and location details.',
                            'Technical information needed to operate and secure the application. Database-backed Laravel sessions can record IP addresses and user-agent details; infrastructure logging depends on deployment configuration.',
                        ],
                    ],
                    [
                        'heading' => '3. How Information Is Used',
                        'paragraphs' => [
                            'QueueFlow processes information to:',
                        ],
                        'items' => [
                            'Provide queue discovery and participation features.',
                            'Display saved tickets and favourites.',
                            'Maintain guest sessions.',
                            'Support authorized business and staff operations.',
                            'Maintain application reliability and security.',
                            'Investigate technical errors and misuse.',
                        ],
                    ],
                    [
                        'heading' => '4. Cookies and Guest Sessions',
                        'paragraphs' => [
                            'QueueFlow uses a Laravel session cookie to associate browser requests with server-side session information, which can include saved favourites, guest ticket references and access credentials, pending queue-join identifiers, and authenticated staff session credentials.',
                            'QueueFlow also uses a CSRF/XSRF cookie to support protection of requests that change application data.',
                            'The session cookie and CSRF/XSRF cookie settings, including lifetime and security attributes, depend on deployment configuration and must be verified before public deployment.',
                            'Saved favourites and ticket references should not be considered permanent browser storage.',
                        ],
                    ],
                    [
                        'heading' => '5. Queue and Ticket Information',
                        'paragraphs' => [
                            'QueueFlow\'s Laravel frontend communicates with a Spring Boot API.',
                            'The Spring Boot API stores queue entries, ticket identifiers and statuses, timestamps, and related reservation and notification records in PostgreSQL.',
                            'Authorized business and staff users can access operational queue information according to their assigned access.',
                            'Guest ticket access uses credentials associated with the guest session and queue record.',
                            'Business, branch, and service information, including submitted branch location details, may be publicly visible through discovery features.',
                            'The reviewed QR scanner implementation processes camera images locally in the browser; the scanner does not upload camera images to the API.',
                            'Browser-based live ticket updates connect to the QueueFlow API.',
                        ],
                    ],
                    [
                        'heading' => '6. Information Sharing',
                        'paragraphs' => [
                            'Information is processed by the QueueFlow Laravel frontend, Spring Boot API, and PostgreSQL database to provide the application features described in this policy.',
                            'Authorized business and staff users may access operational queue information needed for their roles. Business, branch, and service information may be visible to visitors.',
                            'The reviewed application code does not configure an analytics service or an external email provider. Hosting providers, infrastructure services, and any other third parties involved in a public deployment have not yet been confirmed and must be reviewed before publication.',
                        ],
                    ],
                    [
                        'heading' => '7. Retention and Deletion',
                        'paragraphs' => [
                            'Some session and credential data has configured expiry behavior, but expiration does not necessarily mean that the underlying record is physically deleted.',
                            'The backend currently has no general automated retention or purge process for queue and related records. Records may remain until an operator removes or resets them.',
                            'Retention periods and deletion rules must be established and verified before public deployment. No retention period is stated here because one has not been confirmed.',
                        ],
                    ],
                    [
                        'heading' => '8. Security',
                        'paragraphs' => [
                            'The application uses authentication and authorization checks, CSRF protection for browser requests, password hashing, ticket ownership checks, and protections for authentication and guest ticket tokens.',
                            'These application safeguards do not guarantee absolute security. Hosting, infrastructure, and operational security settings remain subject to deployment review.',
                        ],
                    ],
                    [
                        'heading' => '9. Your Privacy Choices',
                        'paragraphs' => [
                            'The Account “Clear data” control removes saved guest ticket references and favourite business identifiers from the Laravel session.',
                            'It does not delete Spring Boot queue records, technical logs, or notifications; sign out authenticated staff; or remove pending queue-join attempts.',
                            'A process for handling requests to access, correct, or delete personal information has not yet been confirmed and must be established before public deployment.',
                        ],
                    ],
                    [
                        'heading' => '10. International Processing',
                        'paragraphs' => [
                            'The hosting provider, infrastructure providers, server and database regions, and any cross-border processing arrangements have not yet been confirmed. These details must be reviewed before publication; no location or transfer arrangement is represented here.',
                        ],
                    ],
                    [
                        'heading' => '11. Changes to This Policy',
                        'paragraphs' => [
                            'This Privacy Policy may be updated when QueueFlow\'s features or data-handling practices change.',
                            'The published version will display an effective date.',
                        ],
                    ],
                    [
                        'heading' => '12. Privacy Contact',
                        'paragraphs' => [
                            'The responsible project operator and an appropriate privacy contact method will be identified before public publication.',
                        ],
                    ],
                ],
            ],
            default => throw new \InvalidArgumentException('Unknown customer information page.'),
        };
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
