<?php

namespace Tests\Feature\Staff;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffShellTest extends TestCase
{
    public function test_authenticated_staff_can_render_overview_with_navigation_identity_and_logout(): void
    {
        $memberships = [$this->membership(10, 'MANAGER')];
        $this->fakeCurrentUser($memberships);

        $response = $this->withAuthentication($memberships)
            ->get(route('staff.home'));

        $response->assertOk()
            ->assertSee('QueueFlow')
            ->assertSee('STAFF PORTAL')
            ->assertSee('Overview')
            ->assertSee('Businesses')
            ->assertSee('Alex Rivera')
            ->assertSee('alex@example.com')
            ->assertSee('MANAGER')
            ->assertSee('href="'.route('staff.home').'"', false)
            ->assertSee('href="'.route('staff.businesses.index').'"', false)
            ->assertSee('action="'.route('staff.logout').'"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Soon')
            ->assertDontSee('href="#"', false)
            ->assertDontSee('inert-spring-token');
    }

    public function test_staff_shell_does_not_fabricate_one_role_when_memberships_have_different_roles(): void
    {
        $memberships = [
            $this->membership(10, 'OWNER'),
            $this->membership(20, 'STAFF'),
        ];
        $this->fakeCurrentUser($memberships);

        $response = $this->withAuthentication($memberships)
            ->get(route('staff.home'));

        $response->assertOk()
            ->assertSee('Alex Rivera')
            ->assertDontSee('>OWNER<', false)
            ->assertDontSee('>STAFF<', false);
    }

    public function test_authenticated_staff_can_render_businesses_in_shell_with_actual_membership_roles(): void
    {
        $memberships = [
            $this->membership(10, 'OWNER'),
            $this->membership(20, 'STAFF'),
        ];
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse($memberships)),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'CapyTech')),
            'http://localhost:8080/api/v1/businesses/20' => Http::response($this->business(20, 'Harbour Dental')),
        ]);

        $response = $this->withAuthentication($memberships)
            ->get(route('staff.businesses.index'));

        $response->assertOk()
            ->assertSee('Businesses you can access with your staff account.')
            ->assertSee('CapyTech')
            ->assertSee('Harbour Dental')
            ->assertSee('OWNER')
            ->assertSee('STAFF')
            ->assertSee('Create business')
            ->assertSee('Alex Rivera')
            ->assertSee('action="'.route('staff.logout').'"', false)
            ->assertDontSee('inert-spring-token');
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     */
    private function fakeCurrentUser(array $memberships): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse($memberships)),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     */
    private function withAuthentication(array $memberships): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => $memberships,
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     * @return array<string, mixed>
     */
    private function meResponse(array $memberships): array
    {
        return ['user' => $this->user(), 'memberships' => $memberships];
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
    private function membership(int $businessId, string $role): array
    {
        return ['businessId' => $businessId, 'branchId' => null, 'role' => $role];
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => null,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
