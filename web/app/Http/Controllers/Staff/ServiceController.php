<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\ServiceData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreServiceRequest;
use App\Http\Requests\Staff\UpdateServiceRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use App\Services\StaffCatalogService;
use App\Services\StaffDashboardPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
        private readonly StaffDashboardPresentationService $dashboardService,
        private readonly StaffCatalogService $catalogService,
    ) {}

    public function index(Request $request): View
    {
        $authContext = $request->attributes->get('queueflow.auth');
        $businesses = $this->catalogService->accessibleBusinessesWithServices(
            $authContext['memberships'],
        );
        $services = collect($businesses)
            ->flatMap(static fn (array $businessContext): array => $businessContext['servicesByBranchId'])
            ->flatten(1);

        return view('staff.services.index', [
            'authContext' => $authContext,
            'businesses' => $businesses,
            'serviceCount' => $services->count(),
            'activeServiceCount' => $services
                ->filter(static fn (ServiceData $service): bool => $service->active)
                ->count(),
            'inactiveServiceCount' => $services
                ->reject(static fn (ServiceData $service): bool => $service->active)
                ->count(),
        ]);
    }

    public function create(Request $request, int $businessId, int $branchId): View
    {
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        return view('staff.services.create', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
        ]);
    }

    public function store(
        StoreServiceRequest $request,
        int $businessId,
        int $branchId,
    ): RedirectResponse {
        $data = $request->validated();
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        try {
            $service = $this->authService->authenticatedRequest(
                fn (#[\SensitiveParameter] string $token): ServiceData => $this->apiClient->createService(
                    $businessId,
                    $branchId,
                    $token,
                    $data['name'],
                    $data['description'] ?? null,
                    (int) $data['durationMinutes'],
                ),
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 400) {
                return back()
                    ->withErrors($this->validationErrors($exception))
                    ->withInput();
            }

            throw $exception;
        }

        return redirect()
            ->route('staff.services.show', [$businessId, $branchId, $service->id])
            ->with('status', 'Service created successfully.');
    }

    public function edit(
        Request $request,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): View {
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureHierarchy($branch, $service, $businessId, $branchId);

        return view('staff.services.edit', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'service' => $service,
        ]);
    }

    public function update(
        UpdateServiceRequest $request,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): RedirectResponse {
        $data = $request->validated();
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureHierarchy($branch, $service, $businessId, $branchId);

        try {
            $service = $this->authService->authenticatedRequest(
                fn (#[\SensitiveParameter] string $token): ServiceData => $this->apiClient->updateService(
                    $businessId,
                    $branchId,
                    $serviceId,
                    $token,
                    $data['name'],
                    $this->normalizeNullableDescription($data['description'] ?? null),
                    (int) $data['durationMinutes'],
                    $request->boolean('active'),
                ),
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 400) {
                return back()
                    ->withErrors($this->updateValidationErrors($exception))
                    ->withInput();
            }

            throw $exception;
        }

        return redirect()
            ->route('staff.services.show', [$businessId, $branchId, $service->id])
            ->with('status', 'Service updated successfully.');
    }

    public function show(
        Request $request,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): View {
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureHierarchy($branch, $service, $businessId, $branchId);

        $dashboardContext = $this->dashboardService->forBranch($businessId, $branchId);
        $queueContext = $this->dashboardService->queueForService($dashboardContext['dashboard'], $serviceId);

        return view('staff.services.show', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'service' => $service,
            'dashboard' => $dashboardContext['dashboard'],
            'dashboardUnavailable' => $dashboardContext['unavailable'],
            'serviceQueue' => $queueContext['queue'],
            'serviceQueueAmbiguous' => $queueContext['ambiguous'],
        ]);
    }

    private function ensureBranchBelongsToBusiness(BranchData $branch, int $businessId): void
    {
        abort_if(
            $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );
    }

    private function ensureHierarchy(
        BranchData $branch,
        ServiceData $service,
        int $businessId,
        int $branchId,
    ): void {
        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        abort_if(
            $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );
    }

    /**
     * @return array<string, string>
     */
    private function validationErrors(QueueFlowApiException $exception): array
    {
        $errors = [];

        foreach (['name', 'description', 'durationMinutes'] as $field) {
            $message = $exception->validationErrors[$field] ?? null;

            if (is_string($message) && $message !== '') {
                $errors[$field] = $message;
            }
        }

        return $errors !== []
            ? $errors
            : ['service' => 'We could not create the service. Please review the details and try again.'];
    }

    /**
     * @return array<string, string>
     */
    private function updateValidationErrors(QueueFlowApiException $exception): array
    {
        $errors = [];

        foreach (['name', 'description', 'durationMinutes', 'active'] as $field) {
            $message = $exception->validationErrors[$field] ?? null;

            if (is_string($message) && $message !== '') {
                $errors[$field] = $message;
            }
        }

        return $errors !== []
            ? $errors
            : ['service' => 'We could not update the service. Please review the details and try again.'];
    }

    private function normalizeNullableDescription(?string $description): ?string
    {
        return $description === null || trim($description) === ''
            ? null
            : $description;
    }
}
