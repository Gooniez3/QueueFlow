<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerExperiencePresentationTest extends TestCase
{
    public function test_places_category_screen_uses_the_demo_presentation_boundary(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                $this->business(10, 'Northstar Health'),
                $this->business(20, 'Harbour Services'),
            ]),
        ]);

        $response = $this->get(route('places.index'));

        $response->assertOk()
            ->assertViewIs('places.index')
            ->assertSee('What do you need today?')
            ->assertSee('Hospital / Clinic')
            ->assertSee('Bank / Service')
            ->assertSee('Public Service')
            ->assertSee('Restaurant')
            ->assertSee('Event')
            ->assertSee('Retail / Tech')
            ->assertSee('Search coming soon')
            ->assertSee('Browse all places')
            ->assertSee('Northstar Health')
            ->assertSee('Harbour Services')
            ->assertSee('href="'.route('businesses.show', 10).'"', false)
            ->assertSee('href="'.route('businesses.show', 20).'"', false)
            ->assertSee('data-active-destination="places"', false);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(1);
    }

    public function test_place_category_list_renders_display_only_filters_and_waiting_values(): void
    {
        $response = $this->get(route('places.show', 'hospital-clinic'));

        $response->assertOk()
            ->assertViewIs('places.show')
            ->assertSee('Hospital / Clinic')
            ->assertSee('Near me')
            ->assertSee('Open now')
            ->assertSee('Shortest wait')
            ->assertSee('QueueFlow Clinic')
            ->assertSee('waiting')
            ->assertSee('temporary presentation data');
    }

    public function test_unknown_demo_place_category_is_not_found(): void
    {
        $this->get(route('places.show', 'unknown-category'))->assertNotFound();
    }

    public function test_guest_account_has_no_customer_authentication_controls(): void
    {
        $response = $this->get(route('account.show'));

        $response->assertOk()
            ->assertViewIs('account.show')
            ->assertSee('Guest')
            ->assertSee('No account needed')
            ->assertSee('saved on this device only')
            ->assertSee('Clear data on this device')
            ->assertSee('data-active-destination="account"', false)
            ->assertDontSee('Sign in')
            ->assertDontSee('Sign out')
            ->assertDontSee('Password')
            ->assertDontSee('Biometric');
    }

    public function test_more_screen_contains_static_queueflow_information(): void
    {
        $response = $this->get(route('more.show'));

        $response->assertOk()
            ->assertViewIs('more.show')
            ->assertSee('About QueueFlow')
            ->assertSee('How it works')
            ->assertSee('FAQ')
            ->assertSee('Terms and Conditions')
            ->assertSee('Privacy Policy')
            ->assertSee('Need help?')
            ->assertSee('data-active-destination="more"', false);
    }

    public function test_all_five_customer_navigation_destinations_are_real_links(): void
    {
        $response = $this->get(route('account.show'));

        $response->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('places.index').'"', false)
            ->assertSee('href="'.route('tickets.show').'"', false)
            ->assertSee('href="'.route('account.show').'"', false)
            ->assertSee('href="'.route('more.show').'"', false)
            ->assertDontSee('href="#"', false);
    }

    public function test_scanner_uses_camera_progressively_without_a_fake_join_request(): void
    {
        $response = $this->get(route('scanner.show'));

        $response->assertOk()
            ->assertViewIs('scanner.show')
            ->assertSee('Scan to join a queue')
            ->assertSee('Enter queue code')
            ->assertSee('Join with code')
            ->assertSee('navigator.mediaDevices?.getUserMedia', false)
            ->assertSee('customer-scan-line', false)
            ->assertDontSee('<form', false)
            ->assertDontSee('guestToken')
            ->assertDontSee('Idempotency-Key')
            ->assertDontSee('Authorization')
            ->assertDontSee('Bearer');
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Customer-facing services.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
