<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\VerifyQueueEntryQrRequest;
use App\Services\QueueFlowQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueEntryQrController extends Controller
{
    public function __construct(private readonly QueueFlowQrService $qrService) {}

    public function create(Request $request, int $businessId, int $branchId): View
    {
        return view('staff.queue-entry-qr.create', [
            'businessId' => $businessId,
            'branchId' => $branchId,
            'verification' => null,
        ]);
    }

    public function verify(
        VerifyQueueEntryQrRequest $request,
        int $businessId,
        int $branchId,
    ): View|RedirectResponse {
        try {
            $verification = $this->qrService->verify($request->credential());

            abort_if(
                $verification->businessId !== $businessId || $verification->branchId !== $branchId,
                404,
                'The ticket was not found for this branch.',
            );

            return view('staff.queue-entry-qr.create', compact('businessId', 'branchId', 'verification'));
        } catch (QueueFlowApiException $exception) {
            if (in_array($exception->status, [400, 403, 404, 409], true)) {
                return back()->with('error', 'That ticket QR is invalid, expired, or unavailable.');
            }

            throw $exception;
        }
    }
}
