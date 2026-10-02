<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\ServiceData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreServiceRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

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

    public function show(
        Request $request,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): View {
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        abort_if(
            $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );

        return view('staff.services.show', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'service' => $service,
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
}
