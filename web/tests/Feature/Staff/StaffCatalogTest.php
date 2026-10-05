<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffCatalogTest extends TestCase
{
    #[DataProvider('catalogRoutes')]
    public function test_catalog_indexes_require_staff_authentication(string $routeName): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('staff.login'));
    }

    public function test_business_wide_membership_lists_all_real_branches_and_navigation(): void
    {
        $memberships = [$this->membership(10)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic', '1 River Road', 'Asia/Singapore'),
                $this->branch(102, 10, 'Harbour Clinic', '8 Harbour Road', 'Asia/Kuala_Lumpur'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response(
                $this->business(10, 'Northstar Health'),
            ),
        ]);

        $response = $this->authenticated($memberships)
            ->get(route('staff.branches.index'));

        $response->assertOk()
            ->assertSee('Northstar Health')
            ->assertSee('Riverside Clinic')
            ->assertSee('1 River Road')
            ->assertSee('Asia/Singapore')
            ->assertSee('Harbour Clinic')
            ->assertSee('8 Harbour Road')
            ->assertSee('Asia/Kuala_Lumpur')
            ->assertSee('href="'.route('staff.branches.show', [10, 101]).'"', false)
            ->assertSee('href="'.route('staff.branches.show', [10, 102]).'"', false)
            ->assertSee('href="'.route('staff.branches.index').'"', false)
            ->assertSee('href="'.route('staff.services.index').'"', false)
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-active',
                '<span>Branches</span>',
            ], false)
            ->assertSee('Pre-Queue / Appointments')
            ->assertSee('Analytics')
            ->assertSee('Settings')
            ->assertDontSee('inert-spring-token');

        $this->assertSame(6, substr_count($response->getContent(), 'staff-nav-item staff-nav-item-disabled'));
        Http::assertSent(fn (Request $request): bool => $request->url() !== 'http://localhost:8080/api/v1/auth/me'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(3);
    }

    public function test_branch_scoped_membership_hides_inaccessible_sibling_branch(): void
    {
        $memberships = [$this->membership(10, 102)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
                $this->branch(102, 10, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response(
                $this->business(10, 'Northstar Health'),
            ),
        ]);

        $this->authenticated($memberships)
            ->get(route('staff.branches.index'))
            ->assertOk()
            ->assertSee('Harbour Clinic')
            ->assertSee('href="'.route('staff.branches.show', [10, 102]).'"', false)
            ->assertDontSee('Riverside Clinic')
            ->assertDontSee('href="'.route('staff.branches.show', [10, 101]).'"', false);
    }

    public function test_overlapping_memberships_produce_a_deduplicated_union_and_business_wide_access_wins(): void
    {
        $memberships = [
            $this->membership(10, 101, 'STAFF'),
            $this->membership(10, 101, 'MANAGER'),
            $this->membership(10, null, 'OWNER'),
            $this->membership(20, 201, 'STAFF'),
            $this->membership(20, 201, 'MANAGER'),
        ];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
                $this->branch(102, 10, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/20/branches' => Http::response([
                $this->branch(201, 20, 'Orchard Branch'),
                $this->branch(202, 20, 'Hidden Branch'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/20' => Http::response($this->business(20, 'City Services')),
        ]);

        $response = $this->authenticated($memberships)
            ->get(route('staff.branches.index'));

        $response->assertOk()
            ->assertSee('Riverside Clinic')
            ->assertSee('Harbour Clinic')
            ->assertSee('Orchard Branch')
            ->assertDontSee('Hidden Branch');

        $this->assertSame(1, substr_count($response->getContent(), 'Riverside Clinic'));
        $this->assertSame(1, substr_count($response->getContent(), 'Orchard Branch'));
        Http::assertSentCount(5);
    }

    public function test_branches_index_has_a_truthful_empty_state(): void
    {
        $this->fakeCatalog([]);

        $this->authenticated([])
            ->get(route('staff.branches.index'))
            ->assertOk()
            ->assertSee('No accessible branches')
            ->assertDontSee('Riverside Clinic');

        Http::assertSentCount(1);
    }

    public function test_invalid_authentication_is_cleared_before_loading_catalog(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'message' => 'Internal expired credential detail',
            ], 401),
        ]);

        $this->authenticated([$this->membership(10)])
            ->get(route('staff.branches.index'))
            ->assertRedirect(route('staff.login'))
            ->assertSessionHas('error', 'Your session has expired. Please sign in again.')
            ->assertSessionMissing('queueflow.auth')
            ->assertDontSee('Internal expired credential detail');
    }

    public function test_catalog_connection_failure_is_safe_and_preserves_authentication(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $memberships = [$this->membership(10)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::failedConnection(
                'Internal connection detail',
            ),
        ]);

        $this->authenticated($memberships)
            ->get(route('staff.branches.index'))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal connection detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null,
        );
    }

    public function test_services_index_renders_real_business_branch_and_service_data_without_dashboards(): void
    {
        $memberships = [$this->membership(10)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
                $this->branch(102, 10, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([
                $this->service(501, 101, 'General Consultation', 20),
            ]),
            'http://localhost:8080/api/v1/businesses/10/branches/102/services' => Http::response([
                $this->service(502, 102, 'Legacy Screening', 45, false),
            ]),
        ]);

        $response = $this->authenticated($memberships)
            ->get(route('staff.services.index'));

        $response->assertOk()
            ->assertSee('Northstar Health')
            ->assertSee('Riverside Clinic')
            ->assertSee('General Consultation')
            ->assertSee('20 minutes')
            ->assertSee('Harbour Clinic')
            ->assertSee('Legacy Screening')
            ->assertSee('45 minutes')
            ->assertSee('Inactive')
            ->assertSee('href="'.route('staff.services.show', [10, 101, 501]).'"', false)
            ->assertSee('href="'.route('staff.services.show', [10, 102, 502]).'"', false)
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-active',
                '<span>Services</span>',
            ], false)
            ->assertDontSee('inert-spring-token');

        Http::assertNotSent(
            fn (Request $request): bool => str_contains($request->url(), '/staff/dashboard'),
        );
        Http::assertSentCount(5);
    }

    public function test_services_index_filters_branch_scope_and_deduplicates_overlapping_memberships(): void
    {
        $memberships = [
            $this->membership(10, 101, 'STAFF'),
            $this->membership(10, 101, 'MANAGER'),
        ];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
                $this->branch(102, 10, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([
                $this->service(501, 101, 'General Consultation', 20),
            ]),
        ]);

        $response = $this->authenticated($memberships)
            ->get(route('staff.services.index'));

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertDontSee('Harbour Clinic')
            ->assertDontSee('Inaccessible Service');

        $this->assertSame(1, substr_count($response->getContent(), 'General Consultation'));
        Http::assertNotSent(
            fn (Request $request): bool => str_contains($request->url(), '/branches/102/services'),
        );
        Http::assertSentCount(4);
    }

    public function test_services_index_has_a_truthful_empty_state(): void
    {
        $memberships = [$this->membership(10, 101)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([]),
        ]);

        $this->authenticated($memberships)
            ->get(route('staff.services.index'))
            ->assertOk()
            ->assertSee('No accessible services')
            ->assertDontSee('General Consultation');
    }

    public function test_business_index_respects_branch_scoped_membership(): void
    {
        $memberships = [$this->membership(10, 101)];
        $this->fakeCatalog($memberships, [
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 10, 'Riverside Clinic'),
                $this->branch(102, 10, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([
                $this->service(501, 101, 'General Consultation', 20),
            ]),
        ]);

        $this->authenticated($memberships)
            ->get(route('staff.businesses.index'))
            ->assertOk()
            ->assertSee('Riverside Clinic')
            ->assertSee('>1</dd>', false)
            ->assertDontSee('Harbour Clinic');

        Http::assertNotSent(
            fn (Request $request): bool => str_contains($request->url(), '/branches/102/services'),
        );
    }

    /** @return array<string, array{string}> */
    public static function catalogRoutes(): array
    {
        return [
            'branches' => ['staff.branches.index'],
            'services' => ['staff.services.index'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     * @param  array<string, mixed>  $responses
     */
    private function fakeCatalog(array $memberships, array $responses = []): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => $memberships,
            ]),
            ...$responses,
        ]);
    }

    /** @param list<array<string, mixed>> $memberships */
    private function authenticated(array $memberships): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => $memberships,
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
    private function membership(
        int $businessId,
        ?int $branchId = null,
        string $role = 'STAFF',
    ): array {
        return [
            'businessId' => $businessId,
            'branchId' => $branchId,
            'role' => $role,
        ];
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Real business description.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function branch(
        int $id,
        int $businessId,
        string $name,
        string $address = '1 River Road',
        string $timezone = 'Asia/Singapore',
    ): array {
        return [
            'id' => $id,
            'businessId' => $businessId,
            'name' => $name,
            'address' => $address,
            'latitude' => 1.3,
            'longitude' => 103.8,
            'timezone' => $timezone,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function service(
        int $id,
        int $branchId,
        string $name,
        int $durationMinutes,
        bool $active = true,
    ): array {
        return [
            'id' => $id,
            'branchId' => $branchId,
            'name' => $name,
            'description' => 'Real service description.',
            'durationMinutes' => $durationMinutes,
            'active' => $active,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
