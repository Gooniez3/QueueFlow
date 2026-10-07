@extends('layouts.app')

@section('title', 'Ticket '.$ownership->ticketNumber.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page customer-page-narrow">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10 flex items-center justify-between gap-3"><a class="customer-back-link" href="{{ route('tickets.show') }}" aria-label="Back to My Tickets"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg></a><div class="text-center"><p class="text-xs text-white/65">My ticket</p><h1 class="text-xl font-bold">Live ticket</h1></div><span class="rounded-full bg-white/15 px-3 py-2 text-xs font-bold">History</span></div></header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            @if (session('status'))<p class="mb-4 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-customer-green shadow-sm" role="status">{{ session('status') }}</p>@endif

            <article class="rounded-[1.6rem] bg-white p-5 shadow-[0_16px_38px_-25px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40">
                <div class="flex items-start justify-between gap-3"><div><p class="font-bold">QueueFlow ticket</p><p class="text-xs text-customer-muted">Saved in this browser</p></div><p @class(['customer-status-badge', 'bg-customer-yellow text-customer-navy' => $status['label'] === 'CALLED', 'bg-customer-green/10 text-customer-green' => in_array($status['label'], ['WAITING', 'SERVING'], true), 'bg-customer-indigo/10 text-customer-indigo' => ! in_array($status['label'], ['CALLED', 'WAITING', 'SERVING'], true)])>{{ $status['label'] }}</p></div>

                <div class="mt-5 grid grid-cols-[1fr_7rem] items-center gap-4">
                    <div><p class="text-[0.65rem] font-bold tracking-[0.13em] text-customer-muted">Ticket number</p><p class="mt-1 break-words text-5xl leading-none font-bold tracking-[-0.055em] text-customer-indigo">{{ $ownership->ticketNumber }}</p><p class="mt-4 text-[0.65rem] font-bold tracking-[0.12em] text-customer-muted">Current status</p><h2 class="mt-1 text-xl font-bold">{{ $status['heading'] }}</h2></div>
                    <div><x-demo-qr :pattern="$presentation['qrPattern']" /><p class="mt-2 text-center text-[0.6rem] text-customer-muted">Presentation QR</p></div>
                </div>
                <p class="mt-3 text-sm leading-5 text-customer-muted">{{ $status['message'] }}</p>

                @if ($status['showWaitingPosition'])
                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-dashed border-customer-line pt-5"><div><dt class="text-xs text-customer-muted">People ahead</dt><dd class="mt-1 text-2xl font-bold">{{ $position->peopleAhead }}</dd></div><div><dt class="text-xs text-customer-muted">Estimated wait</dt><dd class="mt-1 text-2xl font-bold">{{ $position->estimatedWaitMinutes }} {{ $position->estimatedWaitMinutes === 1 ? 'minute' : 'minutes' }}</dd></div></dl>
                @endif

                <dl class="mt-5 grid grid-cols-2 gap-y-4 border-t border-dashed border-customer-line pt-5 text-xs"><div><dt class="text-customer-muted">Ticket ID</dt><dd class="mt-1 font-bold">QF-{{ $ownership->entryId }}</dd></div><div><dt class="text-customer-muted">Date</dt><dd class="mt-1 font-bold">{{ $presentation['date'] }}</dd></div><div><dt class="text-customer-muted">Joined</dt><dd class="mt-1 font-bold">{{ $presentation['joinedAt'] }}</dd></div><div><dt class="text-customer-muted">Party size</dt><dd class="mt-1 font-bold">{{ $presentation['partySize'] }}</dd></div></dl>
                <div class="mt-5 flex items-center gap-3 rounded-2xl bg-customer-canvas p-4"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-customer-indigo/10 text-customer-indigo"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-5" /></svg></span><div><p class="text-sm font-bold">Owned ticket</p><p class="text-xs text-customer-muted">Available in this browser session</p></div></div>
            </article>

            <a class="customer-secondary-button mt-5" href="{{ route('queue-entries.show', [$ownership->queueId, $ownership->entryId]) }}">Refresh status</a>

            @if ($status['canCancel'])
                <button class="customer-destructive-button mt-4" type="button" data-open-leave-dialog>Leave queue</button>
                <span class="sr-only">Cancel ticket</span>
                <dialog class="m-auto w-[min(calc(100%-2rem),24rem)] rounded-[1.5rem] bg-white p-0 text-customer-navy shadow-2xl backdrop:bg-customer-navy/70" data-leave-dialog>
                    <div class="p-5"><h2 class="text-xl font-bold">Leave this queue?</h2><p class="mt-2 text-sm leading-5 text-customer-muted">Your current ticket will be cancelled. You will need to join again for a new ticket.</p><form class="mt-5" method="POST" action="{{ route('queue-entries.cancel', [$ownership->queueId, $ownership->entryId]) }}">@csrf<button class="customer-destructive-button" type="submit">Yes, leave queue</button></form><button class="customer-secondary-button mt-3" type="button" data-close-leave-dialog>Keep my ticket</button></div>
                </dialog>
            @endif

            <a class="customer-text-link mt-5" href="{{ route('tickets.show') }}">&larr;&nbsp; Back to My Tickets</a>
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
