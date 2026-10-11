import { BrowserMultiFormatReader } from '@zxing/browser';

const scannerRoot = document.querySelector('[data-qr-scanner]');

if (scannerRoot) {
    const start = scannerRoot.querySelector('[data-qr-start]');
    const stopButton = scannerRoot.querySelector('[data-qr-stop]');
    const video = scannerRoot.querySelector('[data-qr-camera]');
    const wrap = scannerRoot.querySelector('[data-qr-camera-wrap]');
    const status = scannerRoot.querySelector('[data-qr-camera-status]');
    const credential = document.querySelector('#credential');
    let stream = null;
    let controls = null;
    let frameId = null;
    let runId = 0;
    let phase = 'idle';
    let submitted = false;

    const setStatus = (message) => {
        if (status) status.textContent = message;
    };

    const setControls = () => {
        const active = phase === 'starting' || phase === 'scanning';
        if (start) start.disabled = active || phase === 'submitted';
        if (stopButton) {
            stopButton.hidden = !active;
            stopButton.disabled = !active;
        }
    };

    const cancelFrame = () => {
        if (frameId !== null) {
            window.cancelAnimationFrame(frameId);
            frameId = null;
        }
    };

    const stopResources = () => {
        cancelFrame();
        controls?.stop();
        controls = null;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        if (video) video.srcObject = null;
    };

    const stopScanning = (message = 'Camera stopped. Use manual entry below.') => {
        runId += 1;
        phase = 'idle';
        stopResources();
        setControls();
        if (message) setStatus(message);
    };

    const isCurrentRun = (currentRun) => currentRun === runId && phase !== 'idle' && phase !== 'submitted';

    const submitCredential = (value) => {
        const normalized = value?.trim();

        if (!normalized) {
            setStatus('No ticket credential was found. Try again or use manual entry.');
            return false;
        }

        if (submitted) return true;

        submitted = true;
        phase = 'submitted';
        credential.value = normalized;
        stopResources();
        setControls();
        credential.form.requestSubmit();
        return true;
    };

    const nativeDetector = async () => {
        if (!('BarcodeDetector' in window)) return null;

        try {
            if (typeof window.BarcodeDetector.getSupportedFormats === 'function') {
                const formats = await window.BarcodeDetector.getSupportedFormats();
                if (!formats.includes('qr_code')) return null;
            }

            return new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch {
            return null;
        }
    };

    const scanWithNativeDetector = async (detector, currentRun) => {
        let consecutiveErrors = 0;

        const scan = async () => {
            if (!isCurrentRun(currentRun)) return;

            try {
                const codes = await detector.detect(video);
                consecutiveErrors = 0;
                if (codes[0]?.rawValue && submitCredential(codes[0].rawValue)) return;
            } catch {
                consecutiveErrors += 1;
                if (consecutiveErrors >= 5 && isCurrentRun(currentRun)) {
                    await scanWithFallback(currentRun);
                    return;
                }
            }

            if (isCurrentRun(currentRun)) frameId = window.requestAnimationFrame(scan);
        };

        await scan();
    };

    const scanWithFallback = async (currentRun) => {
        if (!isCurrentRun(currentRun)) return;

        const reader = new BrowserMultiFormatReader();
        controls = await reader.decodeFromVideoElement(video, (result) => {
            if (isCurrentRun(currentRun) && result) submitCredential(result.getText());
        });

        if (!isCurrentRun(currentRun)) {
            controls?.stop();
            controls = null;
        }
    };

    const openCamera = async () => {
        const constraints = [
            { video: { facingMode: { exact: 'environment' } }, audio: false },
            { video: { facingMode: { ideal: 'environment' } }, audio: false },
            { video: true, audio: false },
        ];

        let lastError;
        for (const constraint of constraints) {
            try {
                stream = await navigator.mediaDevices.getUserMedia(constraint);
                return stream;
            } catch (error) {
                lastError = error;
                if (!['OverconstrainedError', 'NotFoundError'].includes(error.name)) break;
            }
        }

        throw lastError;
    };

    const startScanning = async () => {
        if (phase !== 'idle' || !navigator.mediaDevices?.getUserMedia) {
            if (!navigator.mediaDevices?.getUserMedia) {
                setStatus('Camera access is unavailable in this browser. Use manual entry below.');
            }
            return;
        }

        const currentRun = ++runId;
        submitted = false;
        phase = 'starting';
        setControls();

        try {
            await openCamera();
            if (!isCurrentRun(currentRun)) {
                stopResources();
                return;
            }

            video.srcObject = stream;
            await video.play();
            if (!isCurrentRun(currentRun)) {
                stopResources();
                return;
            }

            wrap.classList.remove('hidden');
            phase = 'scanning';
            setControls();
            setStatus('Point the camera at the customer QR code.');

            const detector = await nativeDetector();
            if (detector && isCurrentRun(currentRun)) {
                await scanWithNativeDetector(detector, currentRun);
            } else if (isCurrentRun(currentRun)) {
                await scanWithFallback(currentRun);
            }
        } catch (error) {
            if (!isCurrentRun(currentRun)) return;

            stopScanning();
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                setStatus('Camera permission was denied. Use manual entry below.');
            } else if (error?.name === 'NotFoundError') {
                setStatus('No camera was found. Use manual entry below.');
            } else {
                setStatus('Camera scanning is unavailable here. Use manual entry below.');
            }
        }
    };

    start?.addEventListener('click', startScanning);
    stopButton?.addEventListener('click', () => stopScanning());

    window.addEventListener('pagehide', () => stopScanning(null));
    window.addEventListener('pageshow', () => {
        if (phase === 'idle') setControls();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && (phase === 'starting' || phase === 'scanning')) stopScanning();
    });
}
