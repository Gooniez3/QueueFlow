<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerHomeTest extends TestCase
{
    public function test_customer_home_renders_active_ticket_and_live_queue_summary(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('home')
            ->assertSeeInOrder([
                'YOUR QUEUE',
                'Northstar Health',
                'Riverside Clinic',
                'General Care',
                'A023',
                'CALLED',
                'General Consultation',
                'Please proceed to',
                'Counter 2',
                'People ahead',
                '0',
                'Estimated wait',
                'Ready now',
                'Live queue',
                'View full queue board',
                'NOW SERVING',
                'A021',
                'A022',
                'CALLING',
                'A023',
                'UP NEXT',
                'A024',
                'A025',
                'A026',
                'View my ticket',
            ])
            ->assertSee('data-customer-navigation', false)
            ->assertSee('data-customer-bottom-navigation', false)
            ->assertSee('data-active-destination="home"', false)
            ->assertSee('Home')
            ->assertSee('Explore')
            ->assertSee('My Ticket')
            ->assertSee('More')
            ->assertSee('data-future-destination="explore"', false)
            ->assertSee('data-future-destination="more"', false)
            ->assertSee('aria-label="Explore, coming later"', false)
            ->assertSee('aria-label="More, coming later"', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('tickets.show').'"', false)
            ->assertSee('href="'.route('queue-board.show').'"', false)
            ->assertDontSee('href="#"', false)
            ->assertDontSee('Ticket QR')
            ->assertDontSee('Scan at the counter');
    }

    public function test_customer_home_has_the_root_route(): void
    {
        $this->assertSame(url('/'), route('home'));
    }
}
