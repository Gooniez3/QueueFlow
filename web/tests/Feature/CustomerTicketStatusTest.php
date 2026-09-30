<?php

namespace Tests\Feature;

use App\Contracts\QueuePresentationSource;
use App\Presentation\MockQueuePresentationSource;
use Tests\TestCase;

class CustomerTicketStatusTest extends TestCase
{
    public function test_public_ticket_page_renders_customer_status_details(): void
    {
        $response = $this->get(route('tickets.show'));

        $response
            ->assertOk()
            ->assertViewIs('tickets.show')
            ->assertSee('A023')
            ->assertSee('General Consultation')
            ->assertSee('CALLED')
            ->assertSee('People ahead')
            ->assertSee('Ready now')
            ->assertSee('Counter 2');
    }

    public function test_ticket_page_renders_naturally_without_a_counter(): void
    {
        $this->app->bind(QueuePresentationSource::class, function (): QueuePresentationSource {
            return new class extends MockQueuePresentationSource
            {
                public function customerTicket(): array
                {
                    return [
                        ...parent::customerTicket(),
                        'counter' => null,
                    ];
                }
            };
        });

        $response = $this->get(route('tickets.show'));

        $response
            ->assertOk()
            ->assertSee('A023')
            ->assertDontSee('Please proceed to')
            ->assertDontSee('No Counter');
    }

    public function test_mock_ticket_page_renders_qr_presentation(): void
    {
        $response = $this->get(route('tickets.show'));

        $response->assertSeeInOrder([
            'Mock preview · not scannable',
            'Scan at the counter',
            'Show this code to staff to start your service.',
            'Ticket A023',
        ]);
    }

    public function test_customer_navigation_renders_with_ticket_active(): void
    {
        $response = $this->get(route('tickets.show'));

        $response
            ->assertSee('data-customer-navigation', false)
            ->assertSee('data-customer-bottom-navigation', false)
            ->assertSee('data-active-destination="ticket"', false)
            ->assertSee('Explore')
            ->assertSee('More')
            ->assertSee('data-future-destination="explore"', false)
            ->assertSee('data-future-destination="more"', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('tickets.show').'"', false)
            ->assertDontSee('href="#"', false);
    }

    public function test_ticket_route_does_not_require_a_display_number(): void
    {
        $this->assertSame(url('/ticket'), route('tickets.show'));
    }
}
