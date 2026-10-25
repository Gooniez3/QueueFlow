<?php

namespace Tests\Feature\Staff;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueueEntryQrDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.queueflow.public_web_url', 'https://queueflow.example');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('staff.queue-entry-qr-display.show', [10, 101, 91]))
            ->assertRedirect(route('staff.login'));
    }

    public function test_staff_without_business_or_branch_access_is_forbidden(): void
    {
        foreach ([[$this->membership(businessId: 11)], [$this->membership(102)]] as $memberships) {
            $this->fakeWorkspace(memberships: $memberships);

            $this->authenticated($memberships)
                ->get(route('staff.queue-entry-qr-display.show', [10, 101, 91]))
                ->assertForbidden();
        }
    }

    public function test_wrong_business_branch_and_queue_nesting_return_not_found(): void
    {
        foreach ([
            ['business' => $this->business(11)],
            ['branch' => $this->branch(id: 102)],
            ['dashboard' => $this->dashboard([$this->dashboardQueue(92)])],
        ] as $overrides) {
            $this->fakeWorkspace(...$overrides);

            $this->authenticated()
                ->get(route('staff.queue-entry-qr-display.show', [10, 101, 91]))
                ->assertNotFound();
        }
    }

    public function test_authorized_staff_can_view_and_download_qr_assets(): void
    {
        $this->fakeWorkspace();

        $routes = [
            'staff.queue-entry-qr-display.show' => ['text/html; charset=utf-8', true],
            'staff.queue-entry-qr-display.poster' => ['text/html; charset=utf-8', true],
            'staff.queue-entry-qr-display.download' => ['image/svg+xml', false],
            'staff.queue-entry-qr-display.poster.download' => ['image/svg+xml', true],
        ];

        foreach ($routes as $routeName => [$contentType, $containsServiceName]) {
            $response = $this->authenticated()
                ->get(route($routeName, [10, 101, 91]));

            $response->assertOk()
                ->assertHeader('Content-Type', $contentType);

            if ($containsServiceName) {
                $response->assertSee('General Consultation');
            }
        }
    }

    public function test_missing_and_invalid_service_codes_are_unavailable(): void
    {
        foreach (['', 'QF-ABCD-EFGH-JKLO'] as $entryCode) {
            $this->fakeWorkspace(service: $this->service($entryCode));

            $this->authenticated()
                ->get(route('staff.queue-entry-qr-display.show', [10, 101, 91]))
                ->assertServiceUnavailable();
        }
    }

    /**
     * @param  list<array<string, mixed>>|null  $memberships
     * @param  array<string, mixed>|null  $business
     * @param  array<string, mixed>|null  $branch
     * @param  array<string, mixed>|null  $dashboard
     * @param  array<string, mixed>|null  $service
     */
    private function fakeWorkspace(
        ?array $memberships = null,
        ?array $business = null,
        ?array $branch = null,
        ?array $dashboard = null,
        ?array $service = null,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => $memberships ?? [$this->membership()],
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($business ?? $this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($branch ?? $this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response(
                $dashboard ?? $this->dashboard([$this->dashboardQueue(91)]),
            ),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response(
                $service ?? $this->service('QF-ABCD-EFGH-JKLM'),
            ),
        ]);
    }

    /** @param list<array<string, mixed>>|null $memberships */
    private function authenticated(?array $memberships = null): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => $memberships ?? [$this->membership()],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function user(): array
    {
        return [
            'id' => 42,
            'email' => 'alex@example.com',
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'phone' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function membership(?int $branchId = null, int $businessId = 10): array
    {
        return ['businessId' => $businessId, 'branchId' => $branchId, 'role' => 'MANAGER'];
    }

    /** @return array<string, mixed> */
    private function business(int $id = 10): array
    {
        return [
            'id' => $id,
            'name' => 'Northstar Health',
            'description' => 'Community health services.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function branch(int $id = 101): array
    {
        return [
            'id' => $id,
            'businessId' => 10,
            'name' => 'Riverside Clinic',
            'address' => '1 River Road',
            'latitude' => 1.3,
            'longitude' => 103.8,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @param list<array<string, mixed>> $queues */
    private function dashboard(array $queues): array
    {
        return [
            'businessId' => 10,
            'branchId' => 101,
            'businessDate' => '2026-10-23',
            'queues' => $queues,
        ];
    }

    /** @return array<string, mixed> */
    private function dashboardQueue(int $queueId): array
    {
        return [
            'queueId' => $queueId,
            'publicCode' => "queue-{$queueId}-public-code",
            'name' => 'Consultation Queue',
            'status' => 'OPEN',
            'ticketPrefix' => 'GC',
            'service' => [
                'id' => 501,
                'name' => 'General Consultation',
                'durationMinutes' => 20,
            ],
            'counts' => ['waiting' => 0, 'called' => 0, 'serving' => 0],
            'serving' => null,
            'called' => null,
            'waiting' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function service(string $entryCode): array
    {
        return [
            'id' => 501,
            'branchId' => 101,
            'name' => 'General Consultation',
            'description' => 'General consultation service.',
            'durationMinutes' => 20,
            'active' => true,
            'createdAt' => '2026-09-30T10:15:30+08:00',
            'partySizeMode' => 'DISABLED',
            'maxPartySize' => 20,
            'queueEntryCode' => $entryCode,
        ];
    }
}
