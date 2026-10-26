<?php

namespace Tests\Feature;

use App\Data\GuestQueueOwnershipData;
use App\Services\GuestFavouriteStore;
use App\Services\GuestQueueOwnershipStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_empty_account_links_to_empty_favourites_and_existing_ticket_history(): void
    {
        $this->get(route('account.show'))->assertOk()
            ->assertSee('Saved tickets')
            ->assertSee('href="'.route('account.favourites').'"', false)
            ->assertSee('href="'.route('tickets.show', ['tab' => 'history']).'"', false)
            ->assertSee('data-clear-device-data', false)
            ->assertSee('0');

        $this->get(route('account.favourites'))->assertOk()
            ->assertSee('No favourite places yet.')
            ->assertSee(route('places.index'), false);
        Http::assertNothingSent();
    }

    public function test_favourite_ids_are_unique_persistent_and_removable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([]),
        ]);

        $this->post(route('businesses.favourites.store', 10))->assertRedirect();
        $this->post(route('businesses.favourites.store', 10))->assertRedirect();
        $this->assertSame([10], app(GuestFavouriteStore::class)->all());
        $this->get(route('account.show'))->assertOk()->assertSee('>1</dd>', false);
        $this->get(route('account.favourites'))->assertOk()
            ->assertSee('Northstar Health')
            ->assertSee('href="'.route('businesses.show', 10).'"', false);

        $this->delete(route('businesses.favourites.remove', 10))->assertRedirect();
        $this->assertSame([], app(GuestFavouriteStore::class)->all());
        Http::assertSentCount(2);
    }

    public function test_favourite_list_distinguishes_api_outage_from_a_removed_place(): void
    {
        app(GuestFavouriteStore::class)->add(10);
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/businesses/10' => Http::response([], 503)]);

        $this->get(route('account.favourites'))->assertOk()
            ->assertSee('Saved place could not be loaded')
            ->assertSee('Please try again later.')
            ->assertDontSee('This place may have been removed.');
    }

    public function test_favourite_api_outage_is_not_reported_as_a_removed_place(): void
    {
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/businesses/10' => Http::response([], 503)]);

        $this->post(route('businesses.favourites.store', 10))
            ->assertRedirect(route('account.favourites'))
            ->assertSessionHasErrors('favourite');
        $this->assertSame([], app(GuestFavouriteStore::class)->all());
    }

    public function test_favourite_can_be_removed_when_place_is_unavailable(): void
    {
        app(GuestFavouriteStore::class)->add(99);
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/businesses/99' => Http::response([], 404)]);

        $this->get(route('account.favourites'))->assertOk()
            ->assertSee('Saved place unavailable')
            ->assertDontSee('href="'.route('businesses.show', 99).'"', false);
        $this->delete(route('businesses.favourites.remove', 99))->assertRedirect();
        $this->assertSame([], app(GuestFavouriteStore::class)->all());
    }

    public function test_business_detail_has_keyboard_accessible_favourite_control(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([]),
        ]);
        $this->get(route('businesses.show', 10))->assertOk()
            ->assertSee('Add to favourites')
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_statistics_use_distinct_saved_ticket_references_and_only_confirmed_completed_status(): void
    {
        $store = app(GuestQueueOwnershipStore::class);
        $store->store($this->ticket(1, 101));
        $store->store($this->ticket(2, 102));
        session()->put('queueflow.customer.favourites', [10, 10]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/*/entries/*/position' => Http::sequence()
                ->push($this->position(1, 101, 'COMPLETED'))
                ->push($this->position(2, 102, 'CANCELLED')),
        ]);

        $this->get(route('account.show'))->assertOk()
            ->assertSee('>2</dd>', false)
            ->assertSee('>1</dd>', false)
            ->assertSee('>1</dd>', false);
        Http::assertSentCount(2);
    }

    public function test_queue_history_uses_the_existing_saved_guest_ticket_reference(): void
    {
        app(GuestQueueOwnershipStore::class)->store($this->ticket(1, 101));
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/queues/*/entries/*/position' => Http::response($this->position(1, 101, 'CANCELLED'))]);

        $this->get(route('tickets.show', ['tab' => 'history']))->assertOk()
            ->assertSee('Ticket history')
            ->assertSee('A101')
            ->assertSee('CANCELLED');
        Http::assertSentCount(1);
    }

    public function test_history_keeps_valid_tickets_when_another_saved_ticket_was_removed(): void
    {
        app(GuestQueueOwnershipStore::class)->store($this->ticket(1, 101));
        app(GuestQueueOwnershipStore::class)->store($this->ticket(2, 102));
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/1/entries/101/position' => Http::response($this->position(1, 101, 'CANCELLED')),
            'http://localhost:8080/api/v1/queues/2/entries/102/position' => Http::response([], 404),
        ]);

        $this->get(route('tickets.show', ['tab' => 'history']))->assertOk()
            ->assertSee('Ticket history')
            ->assertSee('A101')
            ->assertSee('Some saved tickets are unavailable.')
            ->assertSee('They may have expired or been removed.')
            ->assertDontSee('No ticket history yet.');
        Http::assertSentCount(2);
    }

    public function test_unknown_ticket_status_makes_served_count_unavailable(): void
    {
        app(GuestQueueOwnershipStore::class)->store($this->ticket(1, 101));
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/queues/*/entries/*/position' => Http::response($this->position(1, 101, 'UNKNOWN'))]);

        $this->get(route('account.show'))->assertOk()
            ->assertSee('>Unavailable</dd>', false)
            ->assertSee('confirmed completions only');
    }

    public function test_api_failure_makes_served_count_unavailable(): void
    {
        app(GuestQueueOwnershipStore::class)->store($this->ticket(1, 101));
        Http::preventStrayRequests();
        Http::fake(['http://localhost:8080/api/v1/queues/*/entries/*/position' => Http::response([], 503)]);

        $this->get(route('account.show'))->assertOk()
            ->assertSee('>Unavailable</dd>', false);
    }

    public function test_clear_requires_post_and_removes_only_owned_guest_keys(): void
    {
        app(GuestQueueOwnershipStore::class)->store($this->ticket(1, 101));
        app(GuestFavouriteStore::class)->add(10);
        session()->put('queueflow.customer.join_attempts', ['keep']);
        session()->put('queueflow.auth', ['staff' => 'keep']);
        session()->put('unrelated.application.key', 'keep');

        $this->get(route('account.show'))->assertOk()
            ->assertSee('server-side tickets')
            ->assertSee('Cancel');
        $this->post(route('account.device-data.clear'))->assertRedirect(route('account.show'));

        $this->assertSame([], app(GuestQueueOwnershipStore::class)->all());
        $this->assertSame([], app(GuestFavouriteStore::class)->all());
        $this->assertSame(['keep'], session('queueflow.customer.join_attempts'));
        $this->assertSame(['staff' => 'keep'], session('queueflow.auth'));
        $this->assertSame('keep', session('unrelated.application.key'));
        Http::assertNothingSent();
    }

    private function ticket(int $queue, int $entry): GuestQueueOwnershipData
    {
        return new GuestQueueOwnershipData(10, 20, 30, $queue, $entry, 'A'.$entry, 'guest-token-'.$entry);
    }

    private function position(int $queue, int $entry, string $status): array
    {
        return ['entryId' => $entry, 'queueId' => $queue, 'publicCode' => 'PUB-'.$queue, 'serviceId' => 30,
            'ticketSequence' => $entry, 'ticketNumber' => 'A'.$entry, 'status' => $status, 'peopleAhead' => 0, 'estimatedWaitMinutes' => 0];
    }

    private function business(): array
    {
        return ['id' => 10, 'name' => 'Northstar Health', 'description' => 'Local clinic.', 'createdAt' => '2026-09-30T10:15:30+08:00'];
    }
}
