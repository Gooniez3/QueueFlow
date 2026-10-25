<svg xmlns="http://www.w3.org/2000/svg" width="2480" height="3508" viewBox="0 0 1240 1754" role="img" aria-labelledby="title description">
    <title id="title">Queue entry poster for {{ $service->name }}</title>
    <desc id="description">Scan the QR code or enter {{ $service->queueEntryCode }} to view the queue at {{ $branch->name }}.</desc>
    <rect width="1240" height="1754" fill="#f7f6fb"/>
    <rect x="52" y="52" width="1136" height="1650" rx="40" fill="#ffffff" stroke="#dedbea" stroke-width="3"/>
    <circle cx="122" cy="126" r="34" fill="#4f46b8"/>
    <path d="M122 105v23l15 10" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="176" y="123" fill="#15123b" font-family="Arial, sans-serif" font-size="34" font-weight="700">QueueFlow</text>
    <text x="176" y="151" fill="#716d87" font-family="Arial, sans-serif" font-size="13" font-weight="700" letter-spacing="3">SMARTER WAITING STARTS HERE</text>
    <text x="620" y="258" fill="#4f46b8" font-family="Arial, sans-serif" font-size="19" font-weight="700" text-anchor="middle" letter-spacing="3">{{ mb_strtoupper($business->name) }}</text>
    <text x="620" y="340" fill="#15123b" font-family="Arial, sans-serif" font-size="72" font-weight="700" text-anchor="middle">Scan to join the queue</text>
    <text x="620" y="393" fill="#716d87" font-family="Arial, sans-serif" font-size="24" text-anchor="middle">Review the live queue before choosing to get a ticket.</text>
    <rect x="280" y="445" width="680" height="680" rx="32" fill="#fff" stroke="#15123b" stroke-width="5"/>
    <image href="{{ $qrDataUri }}" x="310" y="475" width="620" height="620"/>
    <rect x="228" y="1170" width="784" height="166" rx="28" fill="#efeffc" stroke="#4f46b8" stroke-width="3"/>
    <text x="620" y="1217" fill="#716d87" font-family="Arial, sans-serif" font-size="16" font-weight="700" text-anchor="middle" letter-spacing="3">CAN'T SCAN? ENTER THIS CODE</text>
    <text x="620" y="1287" fill="#15123b" font-family="monospace" font-size="49" font-weight="700" text-anchor="middle" letter-spacing="5">{{ $service->queueEntryCode }}</text>
    <text x="620" y="1378" fill="#4f46b8" font-family="Arial, sans-serif" font-size="17" font-weight="700" text-anchor="middle" letter-spacing="2">{{ mb_strtoupper($branch->name) }}</text>
    <text x="620" y="1430" fill="#15123b" font-family="Arial, sans-serif" font-size="34" font-weight="700" text-anchor="middle">{{ $service->name }}</text>
    <line x1="116" y1="1560" x2="1124" y2="1560" stroke="#dedbea" stroke-width="2"/>
    <text x="116" y="1615" fill="#716d87" font-family="Arial, sans-serif" font-size="18">Powered by QueueFlow</text>
    <text x="1124" y="1615" fill="#716d87" font-family="Arial, sans-serif" font-size="18" text-anchor="end">Scan · Review · Join</text>
</svg>
