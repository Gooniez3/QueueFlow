import assert from 'node:assert/strict';
import test from 'node:test';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { element, mediaStream, settle } from './scanner-test-helpers.js';

let importId = 0;

test('starts, stops, and reinitializes the camera', async () => {
    const cameras = [mediaStream(), mediaStream()];
    const harness = await createHarness({ getUserMedia: async () => cameras.shift().value });
    await harness.start.listeners.click();
    assert.equal(harness.stop.hidden, false);
    harness.stop.listeners.click();
    assert.equal(harness.started[0].track.stopped, true);
    assert.equal(harness.video.srcObject, null);
    await harness.start.listeners.click();
    assert.equal(harness.video.srcObject, harness.started[1].value);
    assert.equal(harness.started[1].track.stopped, false);
});

test('ignores rapid clicks and stops a stale asynchronous camera', async () => {
    const requests = [];
    const harness = await createHarness({ getUserMedia: () => new Promise((resolve) => requests.push(resolve)) });
    const first = harness.start.listeners.click();
    await settle();
    await harness.start.listeners.click();
    assert.equal(requests.length, 1);
    harness.document.hidden = true;
    harness.documentListeners.visibilitychange();
    harness.document.hidden = false;
    const second = harness.start.listeners.click();
    await settle();
    const current = mediaStream(); requests[1](current.value); await settle();
    const stale = mediaStream(); requests[0](stale.value);
    await Promise.all([first, second]);
    assert.equal(stale.track.stopped, true);
    assert.equal(current.track.stopped, false);
    assert.equal(harness.video.srcObject, current.value);
});

test('reports permission denial and permits a retry', async () => {
    const camera = mediaStream();
    let request = 0;
    const harness = await createHarness({ getUserMedia: async () => {
        if (request++ === 0) throw Object.assign(new Error('denied'), { name: 'NotAllowedError' });
        return camera.value;
    } });
    await harness.start.listeners.click();
    assert.equal(harness.status.textContent, 'Camera permission was denied. Use manual entry below.');
    assert.equal(harness.start.disabled, false);
    await harness.start.listeners.click();
    assert.equal(harness.video.srcObject, camera.value);
});

test('cleans up on page navigation and when the document is hidden', async () => {
    for (const event of ['pagehide', 'visibilitychange']) {
        const camera = mediaStream();
        const harness = await createHarness({ getUserMedia: async () => camera.value });
        await harness.start.listeners.click();
        if (event === 'visibilitychange') harness.document.hidden = true;
        (event === 'pagehide' ? harness.windowListeners : harness.documentListeners)[event]();
        assert.equal(camera.track.stopped, true);
        assert.equal(harness.video.srcObject, null);
        assert.equal(harness.start.disabled, false);
    }
});

test('rejects an empty scan and submits one valid credential', async () => {
    const camera = mediaStream();
    const harness = await createHarness({ detections: [[{ rawValue: '   ' }], [{ rawValue: ' token-123 ' }]], getUserMedia: async () => camera.value });
    await harness.start.listeners.click();
    assert.equal(harness.status.textContent, 'No ticket credential was found. Try again or use manual entry.');
    await harness.runFrame();
    await harness.runFrame();
    assert.equal(harness.credential.value, 'token-123');
    assert.equal(harness.submissions.value, 1);
    assert.equal(camera.track.stopped, true);
});

test('uses the existing ZXing fallback and ignores duplicate results', async () => {
    const original = BrowserMultiFormatReader.prototype.decodeFromVideoElement;
    let callback;
    let controlsStopped = false;
    BrowserMultiFormatReader.prototype.decodeFromVideoElement = async (_video, handler) => {
        callback = handler;
        return { stop: () => { controlsStopped = true; } };
    };
    try {
        const camera = mediaStream();
        const harness = await createHarness({ getUserMedia: async () => camera.value, native: false });
        await harness.start.listeners.click();
        callback({ getText: () => 'staff-token' });
        callback({ getText: () => 'staff-token' });
        assert.equal(harness.submissions.value, 1);
        assert.equal(controlsStopped, true);
        assert.equal(camera.track.stopped, true);
    } finally {
        BrowserMultiFormatReader.prototype.decodeFromVideoElement = original;
    }
});

async function createHarness({ detections = [], getUserMedia, native = true }) {
    const start = element(); const stop = element(); const video = element();
    const wrap = element(); const status = element(); const credential = element();
    const frames = []; const started = []; const windowListeners = {}; const documentListeners = {};
    const submissions = { value: 0 };
    video.play = async () => {};
    credential.form = { requestSubmit: () => { submissions.value += 1; } };
    const scanner = { querySelector: (selector) => ({
        '[data-qr-start]': start, '[data-qr-stop]': stop, '[data-qr-camera]': video,
        '[data-qr-camera-wrap]': wrap, '[data-qr-camera-status]': status,
    })[selector] };
    const fakeWindow = {
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
        querySelector: (selector) => selector === '[data-qr-scanner]' ? scanner : credential,
    };
    globalThis.window = fakeWindow; globalThis.document = fakeDocument;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { mediaDevices: {
        getUserMedia: async (...args) => { const value = await getUserMedia(...args); started.push({ value, track: value.getTracks()[0] }); return value; },
    } } });
    await import(`../../resources/js/staff-qr-scanner.js?test=${++importId}`);
    return {
        credential, document: fakeDocument, documentListeners, frames, start, started, status,
        stop, submissions, video, windowListeners,
        async runFrame() { const frame = frames.shift(); if (frame) await frame(); await settle(); },
    };
}
