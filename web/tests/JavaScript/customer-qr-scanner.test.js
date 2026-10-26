import assert from 'node:assert/strict';
import test from 'node:test';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { element, mediaStream, settle } from './scanner-test-helpers.js';
import { queueFlowDestination } from '../../resources/js/customer-qr-scanner.js';

const origin = 'https://queueflow.test';
const publicOrigin = 'https://queueflow.example:8443';
const validUrl = `${origin}/businesses/1/branches/2/services/3`;
let importId = 0;

test('accepts only service previews on trusted origins', () => {
    assert.equal(queueFlowDestination(validUrl, origin, publicOrigin), validUrl);
    const publicUrl = `${publicOrigin}/businesses/1/branches/2/services/3/`;
    assert.equal(queueFlowDestination(publicUrl, origin, publicOrigin), publicUrl);
});

test('rejects untrusted, malformed, and unexpected destinations', () => {
    for (const value of [
        'https://evil.example/businesses/1/branches/2/services/3', 'not a URL',
        '/businesses/1/branches/2/services/3', 'javascript:alert(1)',
        'https://user:password@queueflow.test/businesses/1/branches/2/services/3',
        'https://queueflow.test@evil.example/businesses/1/branches/2/services/3',
        `${validUrl}?join=true`, `${validUrl}?`, `${validUrl}#join`, `${validUrl}#`,
        `${validUrl}/join`, `${origin}/businesses/1/branches/2/queues/3`,
    ]) assert.equal(queueFlowDestination(value, origin, publicOrigin), null);
});

test('reports permission denial and camera initialization failure', async () => {
    for (const [name, message] of [
        ['NotAllowedError', 'Camera permission was denied. Allow access or use the code below.'],
        ['NotReadableError', 'The camera is busy or unavailable. Close other camera apps and try again.'],
    ]) {
        const error = Object.assign(new Error(name), { name });
        const harness = await createHarness({ getUserMedia: async () => { throw error; } });
        await harness.enable.listeners.click();
        assert.equal(harness.status.textContent, message);
        assert.equal(harness.enable.disabled, false);
    }
});

test('ignores repeated starts and stops stale asynchronous camera startup', async () => {
    const requests = [];
    const harness = await createHarness({ getUserMedia: () => new Promise((resolve) => requests.push(resolve)) });
    const first = harness.enable.listeners.click();
    await settle();
    await harness.enable.listeners.click();
    assert.equal(requests.length, 1);
    harness.document.hidden = true;
    harness.documentListeners.visibilitychange();
    harness.document.hidden = false;
    const second = harness.enable.listeners.click();
    await settle();
    assert.equal(requests.length, 2);
    const current = mediaStream();
    requests[1](current.value);
    await settle();
    const stale = mediaStream();
    requests[0](stale.value);
    await Promise.all([first, second]);
    assert.equal(stale.track.stopped, true);
    assert.equal(current.track.stopped, false);
    assert.equal(harness.video.srcObject, current.value);
});

test('cleans up on pagehide and when the document becomes hidden', async () => {
    for (const event of ['pagehide', 'visibilitychange']) {
        const camera = mediaStream();
        const harness = await createHarness({ getUserMedia: async () => camera.value });
        await harness.enable.listeners.click();
        if (event === 'visibilitychange') harness.document.hidden = true;
        (event === 'pagehide' ? harness.windowListeners : harness.documentListeners)[event]();
        assert.equal(camera.track.stopped, true);
        assert.equal(harness.video.srcObject, null);
        assert.equal(harness.enable.disabled, false);
    }
});

test('navigates once and disposes the scanner after a native scan', async () => {
    const camera = mediaStream();
    const harness = await createHarness({ detections: [[{ rawValue: validUrl }]], getUserMedia: async () => camera.value });
    await harness.enable.listeners.click();
    await harness.runFrame();
    await harness.runFrame();
    assert.deepEqual(harness.destinations, [validUrl]);
    assert.equal(camera.track.stopped, true);
    assert.equal(harness.video.srcObject, null);
});

test('rejects invalid scans and continues scanning', async () => {
    const camera = mediaStream();
    const harness = await createHarness({ detections: [[{ rawValue: 'https://evil.example/' }]], getUserMedia: async () => camera.value });
    await harness.enable.listeners.click();
    await harness.runFrame();
    assert.equal(harness.status.textContent, 'That is not a valid QueueFlow queue code');
    assert.deepEqual(harness.destinations, []);
    assert.equal(camera.track.stopped, false);
    assert.equal(harness.frames.length, 1);
});

test('falls back to ZXing when native BarcodeDetector is unavailable', async () => {
    const original = BrowserMultiFormatReader.prototype.decodeFromVideoElement;
    let controlsStopped = false;
    BrowserMultiFormatReader.prototype.decodeFromVideoElement = async (_video, callback) => {
        callback({ getText: () => validUrl });
        return { stop: () => { controlsStopped = true; } };
    };
    try {
        const camera = mediaStream();
        const harness = await createHarness({ getUserMedia: async () => camera.value, native: false });
        await harness.enable.listeners.click();
        assert.equal(controlsStopped, true);
        assert.equal(camera.track.stopped, true);
        assert.deepEqual(harness.destinations, [validUrl]);
    } finally {
        BrowserMultiFormatReader.prototype.decodeFromVideoElement = original;
    }
});

test('handles unsupported torch and torch failures', async () => {
    const unsupported = mediaStream();
    let harness = await createHarness({ getUserMedia: async () => unsupported.value });
    await harness.enable.listeners.click();
    await harness.flashlight.listeners.click();
    assert.equal(harness.flashlight.getAttribute('aria-disabled'), 'true');
    const torchValues = [];
    const failing = mediaStream({ torch: true, applyConstraints: async ({ advanced }) => {
        torchValues.push(advanced[0].torch);
        if (torchValues.length === 1) throw new Error('torch');
    } });
    harness = await createHarness({ getUserMedia: async () => failing.value });
    await harness.enable.listeners.click();
    await harness.flashlight.listeners.click();
    assert.equal(harness.status.textContent, 'Flashlight control is unavailable on this camera');
    assert.equal(failing.track.stopped, false);
    await harness.flashlight.listeners.click();
    assert.deepEqual(torchValues, [true, true]);
    assert.equal(harness.flashlight.getAttribute('aria-pressed'), 'true');
});

async function createHarness({ detections = [], getUserMedia, native = true }) {
    const enable = element(); const flashlight = element(); const video = element();
    const placeholder = element(); const status = element(); const frames = [];
    const destinations = []; const windowListeners = {}; const documentListeners = {};
    video.play = async () => {}; flashlight.setAttribute('aria-disabled', 'true');
    const scanner = { dataset: { queueflowOrigin: publicOrigin }, querySelector: (selector) => ({
        '[data-enable-camera]': enable, '[data-flashlight]': flashlight,
        '[data-scanner-video]': video, '[data-scanner-placeholder]': placeholder,
        '[data-scanner-status]': status,
    })[selector] };
    const fakeWindow = {
        isSecureContext: true,
        location: { origin, assign: (url) => destinations.push(url) },
        addEventListener: (type, handler) => { windowListeners[type] = handler; },
        cancelAnimationFrame() {},
        requestAnimationFrame: (callback) => { frames.push(callback); return frames.length; },
    };
    if (native) fakeWindow.BarcodeDetector = class {
        static async getSupportedFormats() { return ['qr_code']; }
        async detect() { return detections.shift() ?? []; }
    };
    const fakeDocument = {
        hidden: false,
        addEventListener: (type, handler) => { documentListeners[type] = handler; },
        querySelector: (selector) => selector === '[data-scanner]' ? scanner : null,
    };
    globalThis.window = fakeWindow; globalThis.document = fakeDocument;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { mediaDevices: { getUserMedia } } });
    await import(`../../resources/js/customer-qr-scanner.js?test=${++importId}`);
    return {
        destinations, document: fakeDocument, documentListeners, enable, flashlight, frames,
        status, video, windowListeners,
        async runFrame() { const frame = frames.shift(); if (frame) await frame(); await settle(); },
    };
}
