<?php

namespace App\Http\Controllers\Staff;

use App\Data\BusinessData;
use App\Data\StaffMembershipData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreBusinessRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function index(Request $request): View
    {
        $authContext = $request->attributes->get('queueflow.auth');
        $businessIds = array_values(array_unique(array_map(
            static fn (StaffMembershipData $membership): int => $membership->businessId,
            $authContext['memberships'],
        )));

        $businesses = array_map(
            fn (int $businessId): BusinessData => $this->apiClient->business($businessId),
            $businessIds,
        );

        return view('staff.businesses.index', [
            'authContext' => $authContext,
            'businesses' => $businesses,
        ]);
    }

    public function create(Request $request): View
    {
        return view('staff.businesses.create', [
            'authContext' => $request->attributes->get('queueflow.auth'),
        ]);
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $business = $this->authService->authenticatedRequest(
                fn (#[\SensitiveParameter] string $token): BusinessData => $this->apiClient->createBusiness(
                    $token,
                    $data['name'],
                    $data['description'] ?? null,
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

        $this->authService->currentUser();

        return redirect()
            ->route('staff.businesses.show', $business->id)
            ->with('status', 'Business created successfully.');
    }

    public function show(Request $request, int $businessId): View
    {
        return view('staff.businesses.show', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branches' => $this->apiClient->branches($businessId),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function validationErrors(QueueFlowApiException $exception): array
    {
        $name = $exception->validationErrors['name'] ?? null;

        return is_string($name) && $name !== ''
            ? ['name' => $name]
            : ['business' => 'We could not create the business. Please review the details and try again.'];
    }
}
