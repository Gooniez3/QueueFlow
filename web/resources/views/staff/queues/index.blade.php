@extends('layouts.staff')

@section('title', 'Live queues')
@section('staff-area', 'Operations')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Live queues', 'url' => route('staff.live-queues.gateway')], ['label' => $branch->name]]" />
@endsection

@push('scripts')
    <script>
        (() => {
            const baseUrl = @json(route('staff.live-queues.events', [$business->id, $branch->id]));
            const RealtimeConnection = window.QueueFlowRealtime?.QueueFlowSseConnection;

            if (!baseUrl || !RealtimeConnection) {
                return;
            }

            let refreshTimer = null;
            const connection = new RealtimeConnection(baseUrl, {
                events: {
                    'branch-update': () => {
                        if (refreshTimer !== null) {
                            return;
                        }

                        refreshTimer = window.setTimeout(() => {
                            refreshTimer = null;
                            window.location.reload();
                        }, 500);
                    },
                },
            });

            connection.connect();
            window.addEventListener('pagehide', () => {
                if (refreshTimer !== null) {
                    window.clearTimeout(refreshTimer);
                }

                connection.close();
            }, { once: true });
        })();
    </script>
@endpush

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="business" /><span class="max-w-36 truncate">{{ $business->name }}</span></div>
    <div class="staff-context-chip" aria-label="Current branch: {{ $branch->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="branch" /><span class="max-w-36 truncate">{{ $branch->name }}</span></div>
@endsection

@section('staff-content')
    @php
        $formattedBusinessDate = \Carbon\CarbonImmutable::parse($dashboard->businessDate)->format('l j F Y');
        $refreshParameters = ['businessId' => $business->id, 'branchId' => $branch->id];

        if ($selectedQueue) {
            $refreshParameters['queue'] = $selectedQueue->queueId;
        }
    @endphp

    <header class="staff-live-header">
        <div>
            <p class="staff-eyebrow">OPERATIONS</p>
            <h1 class="staff-page-title">Live queues</h1>
            <p class="staff-page-copy">{{ $branch->name }} &middot; <time datetime="{{ $dashboard->businessDate }}">{{ $formattedBusinessDate }}</time></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 text-xs text-staff-muted"><x-staff.icon class="size-4" name="refresh" />Page refreshed at {{ now()->format('H:i:s') }}</span>
            <a class="staff-primary-button" href="{{ route('staff.live-queues.create', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="plus" />Open queue</a>
            <a class="staff-secondary-button" href="{{ route('staff.live-queues.index', $refreshParameters) }}"><x-staff.icon class="size-4.5" name="refresh" />Refresh</a>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#e3e5f2] bg-white px-4 py-3 text-sm font-semibold">
            {{ session('status') }}
        </div>
    @endif

    @if ($dashboard->queues === [])
        <section class="staff-live-empty" aria-labelledby="no-queues-heading">
            <span class="grid size-12 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="queues" /></span>
            <div>
                <h2 id="no-queues-heading" class="staff-section-title">No queues today</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-staff-muted">No queue has been opened for {{ $branch->name }} on this business date.</p>
                <a class="staff-primary-button mt-4" href="{{ route('staff.live-queues.create', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="plus" />Open queue</a>
            </div>
        </section>
    @else
        <nav class="staff-queue-selectors" aria-label="Queues for {{ $branch->name }}">
            @foreach ($dashboard->queues as $queue)
                <a
                    @class(['staff-queue-selector', 'staff-queue-selector-active' => $selectedQueue?->queueId === $queue->queueId])
                    href="{{ route('staff.live-queues.index', ['businessId' => $business->id, 'branchId' => $branch->id, 'queue' => $queue->queueId]) }}"
                    @if ($selectedQueue?->queueId === $queue->queueId) aria-current="true" @endif
                >
                    <span class="flex items-start justify-between gap-3">
                        <span class="min-w-0 break-words text-sm font-bold">{{ $queue->name }}</span>
                        <span @class(['staff-live-status', 'staff-live-status-open' => $queue->status === 'OPEN', 'staff-live-status-paused' => $queue->status === 'PAUSED', 'staff-live-status-closed' => $queue->status === 'CLOSED'])><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $queue->status }}</span>
                    </span>
                    <span class="mt-2 flex items-baseline gap-2">
                        <span class="font-staff-display text-[1.375rem] font-extrabold">{{ $queue->counts->waiting }}</span>
                        <span class="text-xs text-staff-muted">waiting &middot; prefix {{ $queue->ticketPrefix }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        <div class="staff-live-layout">
            <div class="grid min-w-0 gap-4">
                <section class="staff-card p-5 sm:p-5.5" aria-labelledby="selected-queue-heading">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 id="selected-queue-heading" class="break-words font-staff-display text-xl font-extrabold sm:text-[1.375rem]">{{ $selectedQueue->name }}</h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span @class(['staff-live-status', 'staff-live-status-open' => $selectedQueue->status === 'OPEN', 'staff-live-status-paused' => $selectedQueue->status === 'PAUSED', 'staff-live-status-closed' => $selectedQueue->status === 'CLOSED'])><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $selectedQueue->status }}</span>
                                <span class="staff-live-meta"><x-staff.icon class="size-3.5" name="service" />{{ $selectedQueue->service?->name ?? 'Shared branch queue' }}</span>
                                <span class="staff-live-meta">Prefix {{ $selectedQueue->ticketPrefix }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2" aria-label="Queue controls">
                            <a class="staff-secondary-button" href="{{ route('staff.queue-entry-qr.create', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="scan" />Scan ticket</a>
                            <a class="staff-secondary-button" href="{{ route('queues.board.show', $selectedQueue->publicCode) }}" target="_blank" rel="noopener noreferrer"><x-staff.icon class="size-4" name="queues" />View board</a>

                            @if ($selectedQueue->status === 'OPEN')
                                <form method="POST" action="{{ route('staff.live-queues.pause', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId]) }}">
                                    @csrf
                                    <button class="staff-live-secondary-action" type="submit"><x-staff.icon class="size-4" name="pause" />Pause</button>
                                </form>
                            @elseif ($selectedQueue->status === 'PAUSED')
                                <form method="POST" action="{{ route('staff.live-queues.resume', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId]) }}">
                                    @csrf
                                    <button class="staff-live-secondary-action" type="submit"><x-staff.icon class="size-4" name="play" />Resume</button>
                                </form>
                            @elseif ($selectedQueue->status === 'CLOSED')
                                <form method="POST" action="{{ route('staff.live-queues.reopen', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId]) }}">
                                    @csrf
                                    <button class="staff-live-secondary-action" type="submit"><x-staff.icon class="size-4" name="play" />Reopen</button>
                                </form>
                            @endif

                            @if ($selectedQueue->status !== 'CLOSED')
                                <form method="POST" action="{{ route('staff.live-queues.close', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId]) }}">
                                    @csrf
                                    <button class="staff-live-secondary-action staff-live-secondary-danger" type="submit"><x-staff.icon class="size-4" name="close" />Close queue</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="staff-live-metric"><dt class="staff-information-label">WAITING</dt><dd class="staff-live-metric-value text-staff-indigo">{{ $selectedQueue->counts->waiting }}</dd></div>
                        <div class="staff-live-metric"><dt class="staff-information-label">CALLED</dt><dd class="staff-live-metric-value text-staff-warning">{{ $selectedQueue->counts->called }}</dd></div>
                        <div class="staff-live-metric"><dt class="staff-information-label">SERVING</dt><dd class="staff-live-metric-value text-staff-success">{{ $selectedQueue->counts->serving }}</dd></div>
                    </dl>
                </section>

                <section class="staff-next-in-line" aria-labelledby="next-in-line-heading">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-warning-soft text-staff-warning" aria-hidden="true"><x-staff.icon class="size-6" name="megaphone" /></span>
                        <div class="min-w-0">
                            <h2 id="next-in-line-heading" class="staff-information-label">NEXT IN LINE</h2>
                            @if ($selectedQueue->waiting !== [])
                                <p class="mt-1 font-staff-display text-sm font-bold sm:text-base">Position 1 of {{ $selectedQueue->counts->waiting }} waiting</p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-staff-muted">No tickets are currently waiting.</p>
                            @endif
                        </div>
                    </div>

                    @if ($selectedQueue->waiting !== [] && $selectedQueue->status === 'OPEN' && $selectedQueue->called === null)
                        <form method="POST" action="{{ route('staff.live-queues.call-next', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId]) }}">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <button class="staff-live-call-next" type="submit"><x-staff.icon class="size-5" name="megaphone" />Call next &middot; {{ $selectedQueue->waiting[0]->ticketNumber }}</button>
                        </form>
                    @endif
                </section>

                <div class="grid gap-4 md:grid-cols-2">
                    <section class="staff-serving-card" aria-labelledby="now-serving-heading">
                        <div class="flex items-center justify-between gap-3">
                            <h2 id="now-serving-heading" class="staff-information-label text-staff-sidebar-muted">NOW SERVING</h2>
                            @if ($selectedQueue->serving)
                                <span class="staff-live-status bg-staff-indigo text-white"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>SERVING</span>
                            @endif
                        </div>

                        @if ($selectedQueue->serving)
                            <p class="staff-live-ticket text-staff-amber">{{ $selectedQueue->serving->ticketNumber }}</p>

                            @if ($selectedQueue->status !== 'CLOSED')
                                <form method="POST" action="{{ route('staff.live-queues.complete', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId, 'entryId' => $selectedQueue->serving->entryId]) }}">
                                    @csrf
                                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                    <button class="staff-live-secondary-action border-0 bg-staff-amber text-staff-ink" type="submit"><x-staff.icon class="size-4" name="check" />Complete</button>
                                </form>
                            @endif

                            <p class="mt-3 text-xs leading-5 text-staff-sidebar-muted">Complete when the customer has been served.</p>
                        @else
                            <div class="staff-live-ticket-empty"><p class="font-semibold text-white">No ticket is currently being served.</p><p class="mt-2 text-xs leading-5 text-staff-sidebar-muted">Called tickets will appear here after service begins.</p></div>
                        @endif
                    </section>

                    <section class="staff-called-card" aria-labelledby="called-heading">
                        <div class="flex items-center justify-between gap-3">
                            <h2 id="called-heading" class="staff-information-label">CALLED</h2>
                            @if ($selectedQueue->called)
                                <span class="staff-live-status staff-live-status-paused"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>CALLED</span>
                            @endif
                        </div>

                        @if ($selectedQueue->called)
                            <p class="staff-live-ticket">{{ $selectedQueue->called->ticketNumber }}</p>

                            @if ($selectedQueue->status !== 'CLOSED')
                                <div class="flex flex-wrap gap-2" aria-label="Called ticket controls">
                                    <form method="POST" action="{{ route('staff.live-queues.start', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId, 'entryId' => $selectedQueue->called->entryId]) }}">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                        <button class="staff-live-secondary-action border-0 bg-staff-indigo text-white" type="submit"><x-staff.icon class="size-4" name="play" />Start serving</button>
                                    </form>

                                    <form method="POST" action="{{ route('staff.live-queues.recall', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId, 'entryId' => $selectedQueue->called->entryId]) }}">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                        <button class="staff-live-secondary-action" type="submit"><x-staff.icon class="size-4" name="megaphone" />Recall</button>
                                    </form>

                                    <form method="POST" action="{{ route('staff.live-queues.skip', ['businessId' => $business->id, 'branchId' => $branch->id, 'queueId' => $selectedQueue->queueId, 'entryId' => $selectedQueue->called->entryId]) }}">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                        <button class="staff-live-secondary-action staff-live-secondary-danger" type="submit"><x-staff.icon class="size-4" name="skip" />Skip</button>
                                    </form>
                                </div>
                            @endif

                            <p class="mt-3 text-xs leading-5 text-staff-muted">The customer has been called and service has not started.</p>
                        @else
                            <div class="staff-live-ticket-empty"><p class="font-semibold">No ticket is currently called.</p><p class="mt-2 text-xs leading-5 text-staff-muted">The next called ticket will appear here.</p></div>
                        @endif
                    </section>
                </div>
            </div>

            <aside class="staff-waiting-list" aria-labelledby="waiting-list-heading">
                <header class="flex items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <h2 id="waiting-list-heading" class="staff-card-title">Waiting list</h2>
                        <p class="mt-1 text-xs text-staff-muted">Ordered by queue position</p>
                    </div>
                    <span class="font-staff-display text-2xl font-extrabold text-staff-indigo">{{ $selectedQueue->counts->waiting }}</span>
                </header>

                @if ($selectedQueue->waiting === [])
                    <div class="border-t border-[#eeeff8] px-5 py-8 text-center">
                        <p class="font-semibold">No tickets waiting</p>
                        <p class="mt-2 text-xs leading-5 text-staff-muted">New waiting tickets will appear here after a refresh.</p>
                    </div>
                @else
                    <ol>
                        @foreach ($selectedQueue->waiting as $entry)
                            <li @class(['staff-waiting-row', 'staff-waiting-row-next' => $loop->first])>
                                <span @class(['staff-waiting-position', 'staff-waiting-position-next' => $loop->first])>{{ $loop->iteration }}</span>
                                <span class="min-w-0 flex-1 break-words font-staff-display text-lg font-extrabold">{{ $entry->ticketNumber }}</span>
                                @if ($loop->first)
                                    <span class="staff-live-next-badge">Next up</span>
                                @endif
                                <span class="staff-live-status bg-staff-indigo-soft text-[#3a30c8]"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $entry->status }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </aside>
        </div>
    @endif
@endsection
