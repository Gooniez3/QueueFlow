<article class="queue-entry-qr-poster" aria-label="Printable queue entry QR poster">
    <div class="queue-entry-qr-brand">
        <span class="staff-brand-mark"><x-staff.icon name="clock" /></span>
        <span><span class="queue-entry-qr-brand-name">QueueFlow</span><span class="queue-entry-qr-brand-caption">SMARTER WAITING STARTS HERE</span></span>
    </div>

    <div class="queue-entry-qr-heading">
        <p>{{ mb_strtoupper($business->name) }}</p>
        <h1>Scan to join the queue</h1>
        <span>Scan with your phone camera, review the live queue, then choose whether to get a ticket.</span>
    </div>

    <div class="queue-entry-qr-image-wrap">
        <img src="{{ $qrDataUri }}" alt="Queue entry QR code for {{ $service->name }} at {{ $branch->name }}">
    </div>

    <div class="queue-entry-code" aria-label="Manual queue entry code">
        <span>CAN&rsquo;T SCAN? ENTER THIS CODE</span>
        <strong>{{ $service->queueEntryCode }}</strong>
        <small>Open QueueFlow Scanner and enter the code above</small>
    </div>

    <dl class="queue-entry-qr-details">
        <div><dt>LOCATION</dt><dd>{{ $branch->name }}</dd></div>
        <div><dt>SERVICE</dt><dd>{{ $service->name }}</dd></div>
    </dl>

    <footer class="queue-entry-qr-footer">
        <span>Powered by <strong>QueueFlow</strong></span>
        <span>Scan &middot; Review &middot; Join</span>
    </footer>
</article>
