<?php

namespace Tests\Feature;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\PublicQueueBoardData;
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
            ->assertSee('WAITING NOW')
            ->assertSee('3')
            ->assertSee('20 min')
            ->assertSee('Join queue')
            ->assertDontSee('A-???')
            ->assertSee('Spring issues your real ticket number after you join.')
            ->assertSee('href="'.route('branches.show', [10, 21]).'"', false)
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

    public function test_active_branch_services_render_with_authoritative_waiting_counts(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn($this->service());
        $apiClient->shouldReceive('services')->once()->with(10, 21)->andReturn([
            $this->service(),
            new ServiceData(
                id: 32,
                branchId: 21,
                name: 'Health Screening',
                description: 'Preventive screening.',
                durationMinutes: 30,
                active: true,
                createdAt: CarbonImmutable::parse('2026-09-30T10:15:30+08:00'),
            ),
        ]);
        $apiClient->shouldReceive('publicQueueBoard')->with(91)->andReturn($this->board(91, 3));
        $apiClient->shouldReceive('publicQueueBoard')->with(92)->andReturn($this->board(92, 7));

        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')->once()->with(10, 21, 31)->andReturn($this->queue('OPEN'));
        $customerQueueService->shouldReceive('applicableQueue')->once()->with(10, 21, 32)->andReturn($this->queue('OPEN', 92, 32));

        $response = $this->get(route('services.show', [10, 21, 31]));

        $response->assertOk()
            ->assertSee('Health Screening')
            ->assertSee('7 waiting')
            ->assertSee('href="'.route('services.show', [10, 21, 32]).'"', false)
            ->assertDontSee('Vaccination')
            ->assertDontSee('4 waiting');
    }

    public function test_inactive_and_cross_branch_services_are_not_rendered_as_alternatives(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn($this->service());
        $apiClient->shouldReceive('services')->once()->with(10, 21)->andReturn([
            $this->service(),
            new ServiceData(33, 21, 'Inactive Service', null, 15, false, CarbonImmutable::parse('2026-09-30T10:15:30+08:00')),
            new ServiceData(34, 99, 'Other Branch Service', null, 15, true, CarbonImmutable::parse('2026-09-30T10:15:30+08:00')),
        ]);
        $apiClient->shouldReceive('publicQueueBoard')->with(91)->andReturn($this->board(91, 0));

        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')->once()->with(10, 21, 31)->andReturn($this->queue('OPEN'));

        $this->get(route('services.show', [10, 21, 31]))
            ->assertOk()
            ->assertDontSee('Inactive Service')
            ->assertDontSee('Other Branch Service');
    }

    public function test_missing_board_metrics_are_rendered_as_unavailable(): void
    {
        $apiClient = $this->mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('business')->once()->with(10)->andReturn($this->business());
        $apiClient->shouldReceive('branch')->once()->with(10, 21)->andReturn($this->branch());
        $apiClient->shouldReceive('service')->once()->with(10, 21, 31)->andReturn($this->service());
        $apiClient->shouldReceive('services')->once()->with(10, 21)->andReturn([$this->service()]);
        $apiClient->shouldReceive('publicQueueBoard')->with(91)->andThrow(new QueueFlowApiException('Unavailable', 503));

        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('applicableQueue')->once()->with(10, 21, 31)->andReturn($this->queue('OPEN'));

        $this->get(route('services.show', [10, 21, 31]))
            ->assertOk()
            ->assertSee('WAITING NOW')
            ->assertSee('—');
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
            ->assertSee('Choose another service')
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
            ->assertSee('Choose another service')
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
            ->assertSee('Choose another service')
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
        $apiClient->shouldReceive('services')->with(10, 21)->andReturn([$this->service()])->zeroOrMoreTimes();
        $apiClient->shouldReceive('publicQueueBoard')->with(91)->andReturn($this->board(91, 3))->zeroOrMoreTimes();
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

    private function queue(string $status, int $id = 91, ?int $serviceId = 31): TodayQueueData
    {
        return new TodayQueueData(
            id: $id,
            branchId: 21,
            serviceId: $serviceId,
            name: 'General Care',
            businessDate: '2026-10-03',
            ticketPrefix: 'A',
            status: $status,
        );
    }

    private function board(int $queueId, int $waitingCount): PublicQueueBoardData
    {
        return new PublicQueueBoardData($queueId, 'General Care', 'OPEN', null, null, $waitingCount, []);
    }
}
