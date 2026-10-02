<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreBranchRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function create(Request $request, int $businessId): View
    {
        return view('staff.branches.create', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
        ]);
    }

    public function store(StoreBranchRequest $request, int $businessId): RedirectResponse
    {
        $data = $request->validated();

        try {
            $branch = $this->authService->authenticatedRequest(
                fn (#[\SensitiveParameter] string $token): BranchData => $this->apiClient->createBranch(
                    $businessId,
                    $token,
                    $data['name'],
                    $data['address'],
                    isset($data['latitude']) ? (float) $data['latitude'] : null,
                    isset($data['longitude']) ? (float) $data['longitude'] : null,
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
            ->route('staff.branches.show', [$businessId, $branch->id])
            ->with('status', 'Branch created successfully.');
    }

    public function show(Request $request, int $businessId, int $branchId): View
    {
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        return view('staff.branches.show', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'services' => $this->apiClient->services($businessId, $branchId),
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

        foreach (['name', 'address', 'latitude', 'longitude'] as $field) {
            $message = $exception->validationErrors[$field] ?? null;

            if (is_string($message) && $message !== '') {
                $errors[$field] = $message;
            }
        }

        return $errors !== []
            ? $errors
            : ['branch' => 'We could not create the branch. Please review the details and try again.'];
    }
}
