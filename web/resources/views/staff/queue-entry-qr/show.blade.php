@extends('layouts.staff')

@section('title', 'Queue entry QR')
@section('staff-area', 'Operations')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Live queues', 'url' => route('staff.live-queues.index', [$business->id, $branch->id])], ['label' => $service->name.' QR']]" />
@endsection

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="business" /><span class="max-w-36 truncate">{{ $business->name }}</span></div>
    <div class="staff-context-chip" aria-label="Current branch: {{ $branch->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="branch" /><span class="max-w-36 truncate">{{ $branch->name }}</span></div>
@endsection

@section('staff-content')
    <div class="queue-entry-qr-management">
        <x-staff.breadcrumbs
            :back-url="route('staff.live-queues.index', ['businessId' => $business->id, 'branchId' => $branch->id, 'queue' => $queue->queueId])"
            :back-label="'Back to live queues'"
            :items="[['label' => 'Operations', 'url' => route('staff.live-queues.index', [$business->id, $branch->id])], ['label' => 'Queue entry QR']]"
        />

        <header class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="staff-eyebrow">CUSTOMER ENTRY</p>
                <h1 class="staff-page-title">Queue entry QR</h1>
                <p class="staff-page-copy">Share a code that opens the real {{ $service->name }} preview. Scanning never joins the queue automatically.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="QR poster actions">
                <a class="staff-secondary-button" href="{{ route('staff.queue-entry-qr-display.poster', [$business->id, $branch->id, $queue->queueId]) }}" target="_blank" rel="noopener"><x-staff.icon class="size-4" name="qr" />View Poster</a>
                <a class="staff-secondary-button" href="{{ route('staff.queue-entry-qr-display.poster', [$business->id, $branch->id, $queue->queueId, 'print' => 1]) }}" target="_blank" rel="noopener"><x-staff.icon class="size-4" name="print" />Print QR</a>
                <a class="staff-secondary-button" href="{{ route('staff.queue-entry-qr-display.download', [$business->id, $branch->id, $queue->queueId]) }}"><x-staff.icon class="size-4" name="download" />Download QR</a>
                <a class="staff-primary-button" href="{{ route('staff.queue-entry-qr-display.poster.download', [$business->id, $branch->id, $queue->queueId]) }}"><x-staff.icon class="size-4" name="download" />Download Poster</a>
            </div>
        </header>

        @if (! $service->active || $queue->status !== 'OPEN')
            <div class="staff-alert-error mt-5" role="status">
                @if (! $service->active)
                    This service is disabled. The QR remains valid, but customers will see that the service is unavailable.
                @elseif ($queue->status === 'PAUSED')
                    This queue is paused. The QR remains valid, but customers cannot join until it is resumed.
                @else
                    This queue is closed. The QR remains valid for future daily sessions, but customers cannot join now.
                @endif
            </div>
        @endif

        <section class="queue-entry-qr-summary" aria-label="Queue entry QR details">
            <div class="queue-entry-qr-preview">
                <img src="{{ $qrDataUri }}" alt="QR code preview for {{ $service->name }}">
                <span>Compact preview</span>
            </div>
            <dl class="queue-entry-qr-management-details">
                <div><dt>Business</dt><dd>{{ $business->name }}</dd></div>
                <div><dt>Branch</dt><dd>{{ $branch->name }}</dd></div>
                <div><dt>Service</dt><dd>{{ $service->name }}</dd></div>
                <div><dt>Queue</dt><dd>{{ $queue->name }} <span @class(['staff-live-status', 'staff-live-status-open' => $queue->status === 'OPEN', 'staff-live-status-paused' => $queue->status === 'PAUSED', 'staff-live-status-closed' => $queue->status === 'CLOSED'])>{{ $queue->status }}</span></dd></div>
                <div><dt>Manual code</dt><dd class="font-mono tracking-[0.08em]">{{ $service->queueEntryCode }}</dd></div>
                <div><dt>Destination</dt><dd><a href="{{ $customerUrl }}" target="_blank" rel="noopener noreferrer">Customer service preview</a></dd></div>
            </dl>
        </section>
    </div>
@endsection
