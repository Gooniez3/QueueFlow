import {
    QueueFlowSseConnection,
    queueFlowPublicRealtimeBaseUrl,
} from './realtime.js';
import './staff-qr-scanner.js';

// Keep the small realtime API available to later Blade checkpoints without
// exposing any server-side authentication state to the browser.
window.QueueFlowRealtime = Object.freeze({
    QueueFlowSseConnection,
    queueFlowPublicRealtimeBaseUrl,
});

export { QueueFlowSseConnection, queueFlowPublicRealtimeBaseUrl };
