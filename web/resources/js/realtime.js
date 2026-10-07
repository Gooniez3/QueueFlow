/**
 * A small native EventSource wrapper for invalidation signals.
 *
 * SSE payloads are notifications only. Consumers must refresh authoritative
 * state through the existing Laravel/Spring HTTP flow after receiving one.
 */
export class QueueFlowSseConnection {
    /**
     * @param {string} url
     * @param {{events?: Record<string, Function>, onUpdate?: Function, onConnected?: Function, onError?: Function, eventSource?: typeof EventSource}} options
     */
    constructor(url, options = {}) {
        this.url = url;
        this.options = options;
        this.eventSource = null;
        this.listeners = new Map();

        Object.entries(options.events ?? {}).forEach(([eventName, callback]) => {
            this.subscribe(eventName, callback);
        });
    }

    connect() {
        if (this.eventSource && this.eventSource.readyState !== 2) {
            return this;
        }

        const EventSourceImplementation = this.options.eventSource ?? globalThis.EventSource;

        if (typeof EventSourceImplementation !== 'function') {
            throw new Error('This browser does not support Server-Sent Events.');
        }

        this.eventSource = new EventSourceImplementation(this.url);
        this.eventSource.onopen = (event) => this.options.onConnected?.(event);
        this.eventSource.onerror = (event) => this.options.onError?.(event);

        this.listeners.forEach((callbacks, eventName) => {
            callbacks.forEach((callback) => {
                this.eventSource.addEventListener(eventName, callback);
            });
        });

        return this;
    }

    subscribe(eventName, callback) {
        if (!this.listeners.has(eventName)) {
            this.listeners.set(eventName, new Set());
        }

        const callbacks = this.listeners.get(eventName);

        if ([...callbacks].some((candidate) => candidate.originalCallback === callback)) {
            return () => this.unsubscribe(eventName, callback);
        }

        const listener = (event) => {
            const payload = this.parsePayload(event);
            callback(payload, event);
            this.options.onUpdate?.(payload, event);
        };
        listener.originalCallback = callback;
        callbacks.add(listener);

        if (this.eventSource) {
            this.eventSource.addEventListener(eventName, listener);
        }

        return () => this.unsubscribe(eventName, callback);
    }

    unsubscribe(eventName, callback) {
        const callbacks = this.listeners.get(eventName);

        if (!callbacks) {
            return;
        }

        const listener = [...callbacks].find((candidate) => candidate.originalCallback === callback);

        if (listener) {
            this.eventSource?.removeEventListener(eventName, listener);
            callbacks.delete(listener);
        }

        if (callbacks.size === 0) {
            this.listeners.delete(eventName);
        }
    }

    close() {
        this.eventSource?.close();
        this.eventSource = null;
    }

    parsePayload(event) {
        try {
            return JSON.parse(event.data);
        } catch {
            return event.data;
        }
    }
}

/**
 * Read the server-rendered public-safe base URL without exposing Laravel
 * session state or any API credentials to JavaScript.
 */
export function queueFlowPublicRealtimeBaseUrl() {
    return document.querySelector('meta[name="queueflow-realtime-base-url"]')?.content ?? '';
}
