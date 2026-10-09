@extends('layouts.app')

@section('title', 'Ticket '.$position->ticketNumber.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page customer-page-narrow">
        <x-customer-navigation />
        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center gap-3">
                <a class="customer-back-link" href="{{ route('tickets.show') }}" aria-label="Back to Tickets">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg>
                </a>
                <h1 class="text-xl font-bold">{{ in_array($position->status, ['COMPLETED', 'CANCELLED', 'SKIPPED'], true) ? 'Ticket details' : 'Live ticket' }}</h1>
            </div>
        </header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            @if (session('status'))<p class="mb-4 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-customer-green shadow-sm" role="status">{{ session('status') }}</p>@endif

            @if (in_array($position->status, ['COMPLETED', 'CANCELLED', 'SKIPPED'], true))
                <article class="overflow-visible rounded-[1.5rem] bg-white p-5 shadow-[0_18px_42px_-25px_rgba(21,17,63,0.62)] ring-1 ring-customer-line/50 sm:p-6" aria-labelledby="ticket-heading">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-customer-muted">Ticket number</p>
                            <h2 id="ticket-heading" class="mt-2 font-sora text-[clamp(2.5rem,14vw,4rem)] font-extrabold leading-none tracking-[-0.08em] text-customer-indigo">{{ $position->ticketNumber }}</h2>
                        </div>
                        <p class="customer-status-badge shrink-0 bg-customer-indigo/10 text-customer-indigo">{{ $status['label'] }}</p>
                    </div>

                    <div class="customer-ticket-divider relative -mx-5 mt-6 px-5 pt-6 sm:-mx-6 sm:px-6">
                        <div class="space-y-5">
                            <div>
                                <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-customer-muted">Final status</p>
                                <p class="mt-1 text-2xl font-bold tracking-[-0.035em] text-customer-navy">{{ $status['heading'] }}</p>
                                <p class="mt-2 text-sm leading-5 text-customer-muted">{{ $status['message'] }}</p>
                            </div>

                            <dl class="space-y-4 rounded-2xl bg-customer-canvas px-4 py-4">
                                @if ($context['business'])<div><dt class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-customer-muted">Business</dt><dd class="mt-1 font-bold">{{ $context['business']->name }}</dd></div>@endif
                                @if ($context['branch'])<div><dt class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-customer-muted">Branch</dt><dd class="mt-1 font-semibold">{{ $context['branch']->name }}</dd></div>@endif
                                @if ($context['service'])<div><dt class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-customer-muted">Service</dt><dd class="mt-1 font-semibold">{{ $context['service']->name }}</dd></div>@endif
                            </dl>
                        </div>
                    </div>
                </article>

                <a class="customer-utility-button mt-4" href="{{ route('tickets.show', ['tab' => 'history']) }}">Back to history</a>
            @else
            <article @class([
                'overflow-visible rounded-[1.5rem] bg-white p-5 shadow-[0_18px_42px_-25px_rgba(21,17,63,0.62)] ring-1 sm:p-6',
                'ring-customer-yellow/70' => $status['label'] === 'CALLED',
                'ring-customer-green/30' => in_array($status['label'], ['WAITING', 'SERVING'], true),
                'ring-customer-line/50' => ! in_array($status['label'], ['CALLED', 'WAITING', 'SERVING'], true),
            ]) aria-labelledby="ticket-heading">
                <div class="flex items-start justify-between gap-3">
                    @if ($context['business'] || $context['service'])
                        <div class="min-w-0">
                            @if ($context['business'])<p class="truncate font-bold">{{ $context['business']->name }}</p>@endif
                            @if ($context['service'])<p class="truncate text-sm text-customer-muted">{{ $context['service']->name }}</p>@endif
                        </div>
                    @endif
                    <p @class([
                        'customer-status-badge shrink-0',
                        'bg-customer-yellow text-customer-navy' => $status['label'] === 'CALLED',
                        'bg-customer-green/10 text-customer-green' => in_array($status['label'], ['WAITING', 'SERVING'], true),
                        'bg-customer-indigo/10 text-customer-indigo' => ! in_array($status['label'], ['CALLED', 'WAITING', 'SERVING'], true),
                    ])>{{ $status['label'] }}</p>
                </div>

                <div class="mt-6 grid grid-cols-[minmax(0,1fr)_6.25rem] items-start gap-3 sm:grid-cols-[minmax(0,1fr)_8rem] sm:gap-5">
                    <div class="min-w-0">
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-customer-muted">Queue number</p>
                        <h2 id="ticket-heading" class="mt-1 whitespace-nowrap font-sora text-[clamp(2.25rem,11vw,3.5rem)] font-extrabold leading-none tracking-[-0.08em] text-customer-indigo">{{ $position->ticketNumber }}</h2>

                        @if ($status['showWaitingPosition'])
                            <div class="mt-4 space-y-1 text-sm leading-5">
                                <p><strong class="tabular-nums">{{ $position->peopleAhead }}</strong> <span class="text-customer-muted">{{ $position->peopleAhead === 1 ? 'person' : 'people' }} ahead</span></p>
                                <p><strong class="tabular-nums">{{ $position->estimatedWaitMinutes }} min</strong> <span class="text-customer-muted">estimated wait</span></p>
                            </div>
                        @endif
                    </div>
                    <div class="grid aspect-square place-items-center rounded-2xl border border-customer-line bg-customer-canvas px-2 text-center">
                        @if ($qrDataUri)
                            <img class="size-full rounded-lg" src="{{ $qrDataUri }}" alt="QR credential for {{ $position->ticketNumber }}" />
                        @else
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-customer-muted">QR unavailable</p>
                                <p class="mt-1 text-xs font-semibold text-customer-navy">Refresh ticket</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="customer-ticket-divider relative -mx-5 mt-6 px-5 pt-6 sm:-mx-6 sm:px-6">
                    <div class="text-center">
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-customer-muted">Current status</p>
                        <p class="mt-1 text-2xl font-bold tracking-[-0.035em] text-customer-navy">{{ $status['heading'] }}</p>
                        <p class="mt-2 text-sm leading-5 text-customer-muted">{{ $status['message'] }}</p>
                        @if ($context['service'] || $context['branch'])
                            <div class="mt-6 rounded-2xl bg-customer-canvas px-4 py-4 text-left">
                                @if ($context['service'])<p class="font-bold">{{ $context['service']->name }}</p>@endif
                                @if ($context['branch'])
                                    <p class="mt-1 text-sm font-semibold text-customer-navy">{{ $context['branch']->name }}</p>
                                    <p class="mt-1 text-xs leading-5 text-customer-muted">{{ $context['branch']->address }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            <a class="customer-utility-button mt-4" href="{{ route('queue-entries.show', [$ownership->queueId, $ownership->entryId]) }}">Refresh status</a>

            @if ($status['canCancel'])
                <div class="mt-5 border-t border-customer-line/70 pt-5">
                    <p class="text-xs text-customer-muted">Need to leave before you're called?</p>
                    <button class="customer-destructive-button mt-3" type="button" data-open-leave-dialog>Leave queue</button>
                </div>
                <span class="sr-only">Cancel ticket</span>
                <dialog class="m-auto w-[min(calc(100%-2rem),24rem)] rounded-[1.5rem] bg-white p-0 text-customer-navy shadow-2xl backdrop:bg-customer-navy/70" data-leave-dialog>
                    <div class="p-5"><h2 class="text-xl font-bold">Leave this queue?</h2><p class="mt-2 text-sm leading-5 text-customer-muted">Your current ticket will be cancelled. You will need to join again for a new ticket.</p><form class="mt-5" method="POST" action="{{ route('queue-entries.cancel', [$ownership->queueId, $ownership->entryId]) }}">@csrf<button class="customer-destructive-button" type="submit">Yes, leave queue</button></form><button class="customer-secondary-button mt-3" type="button" data-close-leave-dialog>Keep my ticket</button></div>
                </dialog>
            @endif
            @endif
        </main>
    </div>
@endsection

@if ($status['canCancel'])
    @push('scripts')
        <script type="module">
            const dialog = document.querySelector('[data-leave-dialog]');
            document.querySelector('[data-open-leave-dialog]')?.addEventListener('click', () => dialog?.showModal());
            document.querySelector('[data-close-leave-dialog]')?.addEventListener('click', () => dialog?.close());
        </script>
    @endpush
@endif

@if (in_array($status['label'], ['WAITING', 'CALLED', 'SERVING'], true))
    @push('scripts')
        <script>
            (() => {
                const publicCode = @json($position->publicCode);
                const baseUrl = document.querySelector('meta[name="queueflow-realtime-base-url"]')?.content?.trim();
                const RealtimeConnection = window.QueueFlowRealtime?.QueueFlowSseConnection;

                if (!baseUrl || !publicCode || !RealtimeConnection) {
                    return;
                }

                const eventsUrl = new URL(
                    `api/v1/public/queues/${encodeURIComponent(publicCode)}/events`,
                    `${baseUrl.replace(/\/+$/, '')}/`,
                ).toString();
                let refreshTimer = null;
                const connection = new RealtimeConnection(eventsUrl, {
                    events: {
                        'queue-update': () => {
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
@endif
