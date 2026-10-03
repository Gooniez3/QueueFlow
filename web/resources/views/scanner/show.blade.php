@extends('layouts.app')

@section('title', 'Scan QR Code - QueueFlow')
@section('body-class', 'bg-[#0f0d2e] text-white')

@section('content')
    <div class="mx-auto flex min-h-dvh w-full max-w-[28rem] flex-col overflow-hidden bg-[#0f0d2e] sm:my-6 sm:min-h-[calc(100dvh-3rem)] sm:rounded-[2rem]">
        <header class="flex items-center justify-between gap-3 px-5 pt-6">
            <a class="grid size-11 place-items-center rounded-full bg-white/15" href="{{ route('home') }}" aria-label="Close scanner"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg></a>
            <h1 class="text-lg font-bold">Scan QR code</h1>
            <button class="grid size-11 place-items-center rounded-full bg-white/15 text-white/45" type="button" data-flashlight aria-label="Toggle flashlight" aria-disabled="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m13 2-7 11h6l-1 9 7-12h-6l1-8Z" /></svg></button>
        </header>

        <main class="flex grow flex-col items-center px-5 pt-6 text-center">
            <h2 class="text-3xl font-bold tracking-[-0.04em]">Scan to join a queue</h2>
            <p class="mt-2 max-w-xs text-sm leading-5 text-white/65">Point your camera at the QR code at the counter.</p>

            <div class="relative mt-8 aspect-square w-full max-w-64 overflow-hidden rounded-[2rem] bg-[#201b58]" data-scanner-frame>
                <video class="absolute inset-0 size-full object-cover opacity-80" playsinline muted data-scanner-video></video>
                <div class="absolute inset-0 bg-customer-navy/30" data-scanner-placeholder></div>
                <span class="absolute top-0 left-0 size-12 rounded-tl-[2rem] border-t-4 border-l-4 border-customer-yellow"></span><span class="absolute top-0 right-0 size-12 rounded-tr-[2rem] border-t-4 border-r-4 border-customer-yellow"></span><span class="absolute bottom-0 left-0 size-12 rounded-bl-[2rem] border-b-4 border-l-4 border-customer-yellow"></span><span class="absolute right-0 bottom-0 size-12 rounded-br-[2rem] border-r-4 border-b-4 border-customer-yellow"></span>
                <span class="customer-scan-line absolute top-1/2 right-4 left-4 h-0.5 bg-customer-yellow shadow-[0_0_12px_rgba(255,200,61,0.8)]"></span>
                <svg class="absolute inset-1/2 size-24 -translate-1/2 text-white/20" viewBox="0 0 96 96" fill="none" stroke="currentColor" stroke-width="7" aria-hidden="true"><path d="M8 34V8h26M62 8h26v26M8 62v26h26M62 88h26V62" /><path d="M28 28h14v14H28zM54 28h14v14H54zM28 54h14v14H28zM54 54h14v14H54z" /></svg>
            </div>

            <button class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-white/15 px-5 text-xs font-bold" type="button" data-enable-camera><span class="size-2 rounded-full bg-customer-yellow"></span><span data-scanner-status>Enable camera</span></button>
        </main>

        <section class="mt-7 rounded-t-[2rem] bg-white px-5 pt-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] text-left text-customer-navy" aria-labelledby="manual-heading">
            <h2 id="manual-heading" class="text-lg font-bold">Can&rsquo;t scan?</h2><p class="mt-1 text-sm text-customer-muted">Type the code shown next to the QR.</p>
            <label class="mt-4 flex min-h-13 items-center gap-3 rounded-full bg-customer-canvas px-5 text-customer-muted"><span aria-hidden="true">&#9000;</span><span class="sr-only">Queue code</span><input class="min-w-0 grow bg-transparent text-sm outline-none placeholder:text-customer-muted" name="queueCode" placeholder="Enter queue code" autocomplete="off" data-manual-code></label>
            <button class="customer-secondary-button mt-3 cursor-not-allowed opacity-70" type="button" disabled>Join with code</button>
            <p class="mt-2 text-center text-[0.68rem] text-customer-muted">Code resolution is a presentation preview until a Spring contract is available.</p>
        </section>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const enableButton = document.querySelector('[data-enable-camera]');
        const flashlightButton = document.querySelector('[data-flashlight]');
        const video = document.querySelector('[data-scanner-video]');
        const placeholder = document.querySelector('[data-scanner-placeholder]');
        const status = document.querySelector('[data-scanner-status]');
        let stream;
        let torchEnabled = false;

        const stopCamera = () => stream?.getTracks().forEach((track) => track.stop());

        enableButton?.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                status.textContent = 'Camera unavailable — use the code below';
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                video.srcObject = stream;
                await video.play();
                placeholder.hidden = true;
                status.textContent = 'Looking for a QR code';

                const track = stream.getVideoTracks()[0];
                const capabilities = track.getCapabilities?.() ?? {};
                if (capabilities.torch) {
                    flashlightButton.setAttribute('aria-disabled', 'false');
                    flashlightButton.classList.remove('text-white/45');
                    flashlightButton.addEventListener('click', async () => {
                        torchEnabled = !torchEnabled;
                        await track.applyConstraints({ advanced: [{ torch: torchEnabled }] });
                        flashlightButton.setAttribute('aria-pressed', String(torchEnabled));
                    });
                }
            } catch {
                status.textContent = 'Camera unavailable — use the code below';
            }
        });

        window.addEventListener('pagehide', stopCamera, { once: true });
    </script>
@endpush
