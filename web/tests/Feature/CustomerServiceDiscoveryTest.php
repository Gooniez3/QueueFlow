<?php

namespace Tests\Feature;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\ServiceData;
use App\Data\TodayQueueData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerServiceDiscoveryTest extends TestCase
{
    public function test_open_active_service_renders_secure_join_form(): void
    {
        $this->mockDiscoveryResources();
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')
            ->once()
            ->with(10, 21, 31)
            ->andReturn($this->queue('OPEN'));

        $response = $this->get(route('services.show', [10, 21, 31]));

        $response->assertOk()
            ->assertViewIs('services.show')
            ->assertSee('General Consultation')
            ->assertSee('Accepting customers')
            ->assertSee('QUEUE OPEN')
            ->assertSee('Join queue')
            ->assertSee('data-customer-join-form', false)
            ->assertSee('action="'.route('queue-entries.store', 91).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="businessId" value="10"', false)
            ->assertSee('name="branchId" value="21"', false)
            ->assertSee('name="serviceId" value="31"', false)
            ->assertDontSee('guestToken')
            ->assertDontSee('idempotencyKey')
            ->assertDontSee('Idempotency-Key')
            ->assertDontSee('?guestToken=', false)
            ->assertDontSee('?idempotencyKey=', false)
            ->assertDontSee('Authorization')
            ->assertDontSee('Bearer');
    }

    #[DataProvider('unavailableQueueStates')]
    public function test_unavailable_queue_state_is_presented_without_join_action(
        string $status,
        string $heading,
    ): void {
        $this->mockDiscoveryResources();
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')
            ->once()
            ->with(10, 21, 31)
            ->andReturn($this->queue($status));

        $response = $this->get(route('services.show', [10, 21, 31]));

        $response->assertOk()
            ->assertSee($heading)
            ->assertDontSee('data-join-placeholder', false)
            ->assertDontSee('<form', false);
    }

    public function test_inactive_service_has_no_actionable_join_form(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn(
            new ServiceData(
                id: 31,
                branchId: 21,
                name: 'General Consultation',
                description: 'Primary care consultation.',
                durationMinutes: 20,
                active: false,
                createdAt: CarbonImmutable::parse('2026-09-30T10:15:30+08:00'),
            ),
        );
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')
            ->once()
            ->with(10, 21, 31)
            ->andReturn($this->queue('OPEN'));

        $response = $this->get(route('services.show', [10, 21, 31]));

        $response->assertOk()
            ->assertSee('Service unavailable')
            ->assertDontSee('data-customer-join-form', false)
            ->assertDontSee('<form', false);
    }

    public function test_valid_service_without_queue_renders_normal_unavailable_state(): void
    {
        $this->mockDiscoveryResources();
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')
            ->once()
            ->with(10, 21, 31)
            ->andThrow(new QueueFlowApiException('Queue not found for today', 404));

        $response = $this->get(route('services.show', [10, 21, 31]));

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('No queue available today')
            ->assertDontSee('data-customer-join-form', false)
            ->assertDontSee('<form', false)
            ->assertDontSee('The requested QueueFlow resource was not found.');
    }

    public function test_invalid_service_404_is_not_presented_as_no_queue(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')
            ->once()
            ->with(10, 21, 99)
            ->andThrow(new QueueFlowApiException('Internal service lookup detail', 404));
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldNotReceive('applicableQueue');

        $response = $this->get(route('services.show', [10, 21, 99]));

        $response->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('No queue available today')
            ->assertDontSee('Internal service lookup detail');
    }

    #[DataProvider('nestedResourceMismatches')]
    public function test_nested_resource_mismatch_returns_404(
        int $returnedBusinessId,
        int $returnedBranchId,
    ): void {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn(
            $this->branch(businessId: $returnedBusinessId),
        );
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn(
            $this->service(branchId: $returnedBranchId),
        );
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldNotReceive('applicableQueue');

        $this->get(route('services.show', [10, 21, 31]))
            ->assertNotFound();
    }

    /** @return array<string, array{string, string}> */
    public static function unavailableQueueStates(): array
    {
        return [
            'paused' => ['PAUSED', 'Temporarily unavailable'],
            'closed' => ['CLOSED', 'Closed for today'],
        ];
    }

    /** @return array<string, array{int, int}> */
    public static function nestedResourceMismatches(): array
    {
        return [
            'branch belongs to another business' => [99, 21],
            'service belongs to another branch' => [10, 22],
        ];
    }

    private function mockDiscoveryResources(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn($this->service());
    }

    private function business(): BusinessData
    {
        return new BusinessData(
            id: 10,
            name: 'Northstar Health',
            description: 'Neighbourhood healthcare.',
            createdAt: CarbonImmutable::parse('2026-09-30T10:15:30+08:00'),
        );
    }

    private function branch(int $businessId = 10): BranchData
    {
        return new BranchData(
            id: 21,
            businessId: $businessId,
            name: 'Riverside Clinic',
            address: '10 River Road, Singapore',
            latitude: null,
            longitude: null,
            timezone: 'Asia/Singapore',
            createdAt: CarbonImmutable::parse('2026-09-30T10:15:30+08:00'),
        );
    }

    private function service(int $branchId = 21): ServiceData
    {
        return new ServiceData(
            id: 31,
            branchId: $branchId,
            name: 'General Consultation',
            description: 'Primary care consultation.',
            durationMinutes: 20,
            active: true,
            createdAt: CarbonImmutable::parse('2026-09-30T10:15:30+08:00'),
        );
    }

    private function queue(string $status): TodayQueueData
    {
        return new TodayQueueData(
            id: 91,
            branchId: 21,
            serviceId: 31,
            name: 'General Care',
            businessDate: '2026-10-03',
            ticketPrefix: 'A',
            status: $status,
        );
    }
}
