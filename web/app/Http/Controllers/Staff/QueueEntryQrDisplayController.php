<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\StaffDashboardQueueData;
use App\Http\Controllers\Controller;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowQrRenderer;
use App\Services\QueueFlowQueueEntryUrlService;
use App\Services\QueueFlowQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use LogicException;

class QueueEntryQrDisplayController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowQueueService $queueService,
        private readonly QueueFlowQueueEntryUrlService $queueEntryUrlService,
        private readonly QueueFlowQrRenderer $qrRenderer,
    ) {}

    public function show(
        Request $request,
        int $businessId,
        int $branchId,
        int $queueId,
    ): View|RedirectResponse {
        $data = $this->displayData($request, $businessId, $branchId, $queueId);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('staff.queue-entry-qr.show', $data);
    }

    public function poster(Request $request, int $businessId, int $branchId, int $queueId): View|RedirectResponse
    {
        $data = $this->displayData($request, $businessId, $branchId, $queueId);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('staff.queue-entry-qr.poster', $data + [
            'printOnLoad' => $request->boolean('print'),
        ]);
    }

    public function downloadQr(Request $request, int $businessId, int $branchId, int $queueId): Response|RedirectResponse
    {
        $data = $this->displayData($request, $businessId, $branchId, $queueId);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return response($this->qrRenderer->renderQueueEntrySvg($data['customerUrl']), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$data['qrDownloadFilename'].'"',
        ]);
    }

    public function downloadPoster(Request $request, int $businessId, int $branchId, int $queueId): Response|RedirectResponse
    {
        $data = $this->displayData($request, $businessId, $branchId, $queueId);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return response(view('staff.queue-entry-qr.poster-download', $data)->render(), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$data['posterDownloadFilename'].'"',
        ]);
    }

    /** @return array<string, mixed>|RedirectResponse */
    private function displayData(Request $request, int $businessId, int $branchId, int $queueId): array|RedirectResponse
    {
        $business = $this->apiClient->business($businessId);
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchHierarchy($branch, $business->id, $businessId, $branchId);

        $dashboard = $this->queueService->staffDashboard($businessId, $branchId);

        abort_if(
            $dashboard->businessId !== $businessId || $dashboard->branchId !== $branchId,
            404,
            'The requested queue dashboard was not found for this branch.',
        );

        $queue = collect($dashboard->queues)->first(
            static fn (StaffDashboardQueueData $candidate): bool => $candidate->queueId === $queueId,
        );

        abort_unless($queue instanceof StaffDashboardQueueData, 404, 'The requested queue was not found for this branch.');
        abort_if($queue->service === null, 404, 'Queue entry QR codes are available for service queues.');

        $service = $this->apiClient->service($businessId, $branchId, $queue->service->id);

        abort_if(
            $service->branchId !== $branchId || $service->id !== $queue->service->id,
            404,
            'The requested service was not found for this queue.',
        );
        abort_if(
            $service->queueEntryCode === '',
            503,
            'The queue entry code is not available for this service.',
        );
        abort_unless(
            preg_match('/\AQF-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}\z/', $service->queueEntryCode) === 1,
            503,
            'The queue entry code is invalid for this service.',
        );

        try {
            $customerUrl = $this->queueEntryUrlService->forService($businessId, $branchId, $service->id);
        } catch (LogicException $exception) {
            report($exception);

            return redirect()
                ->route('staff.live-queues.index', [
                    'businessId' => $businessId,
                    'branchId' => $branchId,
                    'queue' => $queueId,
                ])
                ->with('error', $exception->getMessage());
        }

        $filenameBase = sprintf(
            'queueflow-%s-%s-queue-entry',
            Str::slug($branch->name) ?: 'branch-'.$branch->id,
            Str::slug($service->name) ?: 'service-'.$service->id,
        );

        return [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $business,
            'branch' => $branch,
            'service' => $service,
            'queue' => $queue,
            'customerUrl' => $customerUrl,
            'qrDataUri' => $this->qrRenderer->renderQueueEntryUrl($customerUrl),
            'qrDownloadFilename' => $filenameBase.'-qr.svg',
            'posterDownloadFilename' => $filenameBase.'-poster.svg',
        ];
    }

    private function ensureBranchHierarchy(
        BranchData $branch,
        int $resolvedBusinessId,
        int $businessId,
        int $branchId,
    ): void {
        abort_if(
            $resolvedBusinessId !== $businessId
                || $branch->id !== $branchId
                || $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );
    }
}
