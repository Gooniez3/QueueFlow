<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerExperiencePresentationTest extends TestCase
{
    public function test_places_uses_real_discovery_categories_and_businesses(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/discovery' => Http::response([
                $this->discovery(10, 'Northstar Health', 'HEALTH', 101, 'Riverside Clinic', [501 => 'General Consultation']),
                $this->discovery(20, 'Harbour Services', 'FINANCE', 201, 'Central Branch', [601 => 'Account Services']),
            ]),
        ]);

        $response = $this->get(route('places.index'));

        $response->assertOk()
            ->assertViewIs('places.index')
            ->assertViewHas('categories', fn (array $categories): bool => count($categories) === 6
                && $categories[0]['name'] === 'Hospital / Clinic'
                && $categories[0]['count'] === 1
                && $categories[2]['count'] === 0)
            ->assertSee('What do you need today?')
            ->assertSee('Hospital / Clinic')
            ->assertSee('Bank / Service')
            ->assertSee('Public Service')
            ->assertSee('Restaurant')
            ->assertSee('Event')
            ->assertSee('Retail / Tech')
            ->assertSee('0 places')
            ->assertSee('Browse places')
            ->assertSee('Northstar Health')
            ->assertSee('Harbour Services')
            ->assertSee('href="'.route('businesses.show', 10).'"', false)
            ->assertSee('href="'.route('businesses.show', 20).'"', false)
            ->assertSee('data-active-destination="places"', false);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/public/discovery'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(1);
    }

    public function test_place_category_list_renders_real_business_branch_and_service_hierarchy(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/discovery*' => Http::response([
                $this->discovery(10, 'Northstar Health', 'HEALTH', 101, 'Riverside Clinic', [501 => 'General Consultation']),
            ]),
        ]);

        $response = $this->get(route('places.show', 'health'));

        $response->assertOk()
            ->assertViewIs('places.show')
            ->assertSee('Hospital / Clinic')
            ->assertSee('Northstar Health')
            ->assertSee('Riverside Clinic')
            ->assertSee('General Consultation')
            ->assertDontSee('temporary presentation data')
            ->assertDontSee('Search coming soon');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'category=HEALTH'));
    }

    public function test_places_search_forwards_query_and_renders_no_results_state(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/discovery*' => Http::response([]),
        ]);

        $response = $this->get(route('places.index', ['search' => '  closed clinic  ']));

        $response->assertOk()
            ->assertSee('No places match your search.')
            ->assertSee('Clear search')
            ->assertSee('value="closed clinic"', false);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:8080/api/v1/public/discovery?search=closed%20clinic');
    }

    public function test_empty_place_category_has_a_truthful_empty_state(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/public/discovery*' => Http::response([]),
        ]);

        $this->get(route('places.show', 'unknown-category'))
            ->assertNotFound();
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
    private function discovery(int $businessId, string $businessName, string $category, int $branchId, string $branchName, array $services): array
    {
        return [
            'businessId' => $businessId,
            'businessName' => $businessName,
            'businessDescription' => 'Customer-facing services.',
            'category' => $category,
            'branchId' => $branchId,
            'branchName' => $branchName,
            'address' => '1 QueueFlow Street',
            'latitude' => null,
            'longitude' => null,
            'distanceKm' => null,
            'services' => collect($services)->map(fn (string $name, int $id): array => [
                'serviceId' => $id,
                'name' => $name,
                'description' => 'Available service',
                'durationMinutes' => 20,
            ])->values()->all(),
        ];
    }
}
