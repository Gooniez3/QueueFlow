<?php

namespace Tests\Feature;

use Tests\TestCase;

class QueueBoardTest extends TestCase
{
    public function test_public_queue_board_has_its_own_display_route(): void
    {
        $this->assertSame(url('/queue-board'), route('queue-board.show'));
    }

    public function test_public_queue_board_renders_entries_in_status_order(): void
    {
        $response = $this->get(route('queue-board.show'));

        $response
            ->assertOk()
            ->assertViewIs('queue-board.show')
            ->assertSeeInOrder([
                'NOW SERVING',
                'A021',
                'A022',
                'CALLING',
                'A023',
                'UP NEXT',
                'A024',
                'A025',
                'A026',
            ]);
    }

    public function test_queue_board_renders_optional_counters_without_placeholders(): void
    {
        $response = $this->get(route('queue-board.show'));

        $response
            ->assertOk()
            ->assertSee('Counter 1')
            ->assertSee('Counter 2')
            ->assertSee('A022')
            ->assertDontSee('No Counter');
    }

    public function test_public_queue_board_does_not_render_customer_navigation(): void
    {
        $response = $this->get(route('queue-board.show'));

        $response
            ->assertDontSee('data-customer-navigation', false)
            ->assertDontSee('data-customer-bottom-navigation', false);
    }
}
