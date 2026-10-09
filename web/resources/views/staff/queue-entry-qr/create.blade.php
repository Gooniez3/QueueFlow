@extends('layouts.staff')

@section('title', 'Scan ticket')
@section('staff-area', 'Operations')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Live queues', 'url' => route('staff.live-queues.index', [$businessId, $branchId])], ['label' => 'Scan ticket']]" />
@endsection

@section('staff-content')
    <div class="staff-form-wrap">
        <x-staff.breadcrumbs
            :back-url="route('staff.live-queues.index', [$businessId, $branchId])"
            :back-label="'Back to live queues'"
            :items="[['label' => 'Operations', 'url' => route('staff.live-queues.index', [$businessId, $branchId])], ['label' => 'Scan ticket']]"
        />

        <header class="mt-6 flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="scan" /></span>
            <div>
                <p class="staff-eyebrow">QUEUE OPERATIONS</p>
                <h1 class="staff-page-title">Scan ticket</h1>
                <p class="staff-page-copy">Verify a customer&rsquo;s QR code for this branch. Verification is read-only.</p>
            </div>
        </header>

        @if (session('error'))
            <p class="staff-alert-error mt-5" role="alert">{{ session('error') }}</p>
        @endif

        @if ($verification)
            <section class="staff-card mt-5 p-5" aria-labelledby="verified-ticket-heading">
                <p class="staff-eyebrow">VERIFIED TICKET</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                    <h2 id="verified-ticket-heading" class="font-staff-display text-4xl font-extrabold text-staff-indigo">{{ $verification->ticketNumber }}</h2>
                    <span class="staff-live-status staff-live-status-open">{{ $verification->status }}</span>
                </div>
                <dl class="mt-5 grid gap-4 border-t border-staff-line pt-5 sm:grid-cols-2">
                    <div><dt class="staff-information-label">SERVICE</dt><dd class="mt-1 font-semibold">{{ $verification->serviceName }}</dd></div>
                    <div><dt class="staff-information-label">BRANCH</dt><dd class="mt-1 font-semibold">{{ $verification->branchName }}</dd></div>
                </dl>
            </section>
        @endif

        <section class="staff-form-card mt-5" aria-labelledby="camera-heading">
            <h2 id="camera-heading" class="staff-card-title">Use camera</h2>
            <p class="mt-2 text-sm leading-6 text-staff-muted">Allow camera access to scan the customer&rsquo;s QR code on supported browsers.</p>
            <div class="relative mt-4 hidden aspect-video overflow-hidden rounded-xl bg-staff-ink" data-qr-camera-wrap>
                <video class="size-full object-cover" playsinline muted data-qr-camera></video>
            </div>
            <button class="staff-secondary-button mt-4" type="button" data-qr-start>Enable camera</button>
            <p class="mt-3 text-xs text-staff-muted" data-qr-camera-status role="status"></p>
        </section>

        <form class="staff-form-card mt-4" method="POST" action="{{ route('staff.queue-entry-qr.verify', [$businessId, $branchId]) }}">
            @csrf
            <h2 class="staff-card-title">Enter credential</h2>
            <p class="mt-2 text-sm leading-6 text-staff-muted">Use this fallback when camera scanning is unavailable.</p>
            <label class="staff-field-label mt-5 block" for="credential">QR credential</label>
            <input class="staff-field-control mt-2" id="credential" name="credential" type="text" required maxlength="255" autocomplete="off" value="{{ old('credential') }}">
            @error('credential')<p class="staff-field-error mt-2">{{ $message }}</p>@enderror
            <button class="staff-primary-button mt-4" type="submit">Verify ticket</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const start = document.querySelector('[data-qr-start]');
            const video = document.querySelector('[data-qr-camera]');
            const wrap = document.querySelector('[data-qr-camera-wrap]');
            const status = document.querySelector('[data-qr-camera-status]');
            const credential = document.querySelector('#credential');
            let stream = null;
            let submitted = false;

            const stop = () => {
                stream?.getTracks().forEach((track) => track.stop());
                stream = null;
            };

            start?.addEventListener('click', async () => {
                if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                    status.textContent = 'Camera scanning is not supported here. Use the manual credential field below.';
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
                    video.srcObject = stream;
                    await video.play();
                    wrap.classList.remove('hidden');
                    status.textContent = 'Point the camera at the customer QR code.';
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    const scan = async () => {
                        if (submitted || !stream) return;
                        const codes = await detector.detect(video);
                        const value = codes[0]?.rawValue?.trim();
                        if (value) {
                            submitted = true;
                            credential.value = value;
                            stop();
                            credential.form.requestSubmit();
                            return;
                        }
                        window.requestAnimationFrame(scan);
                    };
                    scan();
                } catch {
                    status.textContent = 'Camera access was unavailable. Use the manual credential field below.';
                    stop();
                }
            });

            window.addEventListener('pagehide', stop, { once: true });
        })();
    </script>
@endpush
