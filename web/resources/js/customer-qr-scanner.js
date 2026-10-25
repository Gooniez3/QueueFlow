import { BrowserMultiFormatReader } from '@zxing/browser';

export const queueFlowDestination = (rawValue, currentOrigin, publicOrigin) => {
    try {
        const url = new URL(rawValue);
        const allowedOrigins = new Set([currentOrigin, publicOrigin].filter(Boolean));
        const servicePath = /^\/businesses\/\d+\/branches\/\d+\/services\/\d+\/?$/;

        if (!['http:', 'https:'].includes(url.protocol)
            || url.username || url.password || url.search || url.hash
            || !allowedOrigins.has(url.origin)
            || !servicePath.test(url.pathname)
            || url.href !== `${url.origin}${url.pathname}`) {
            return null;
        }

        return url.href;
    } catch {
        return null;
    }
};

const scanner = typeof document === 'undefined' ? null : document.querySelector('[data-scanner]');

if (scanner) {
    const enableButton = scanner.querySelector('[data-enable-camera]');
    const flashlightButton = scanner.querySelector('[data-flashlight]');
    const video = scanner.querySelector('[data-scanner-video]');
    const placeholder = scanner.querySelector('[data-scanner-placeholder]');
    const status = scanner.querySelector('[data-scanner-status]');
    let stream = null;
    let controls = null;
    let frameId = null;
    let runId = 0;
    let phase = 'idle';
    let torchEnabled = false;

    const setStatus = (message) => {
        status.textContent = message;
    };

    const stopStream = (cameraStream) => {
        cameraStream?.getTracks().forEach((track) => track.stop());
    };

    const stopResources = () => {
        if (frameId !== null) {
            window.cancelAnimationFrame(frameId);
            frameId = null;
        }

        controls?.stop();
        controls = null;
        stopStream(stream);
        stream = null;
        video.srcObject = null;
        placeholder.hidden = false;
        flashlightButton.setAttribute('aria-disabled', 'true');
        flashlightButton.setAttribute('aria-pressed', 'false');
        flashlightButton.classList.add('text-white/45');
        torchEnabled = false;
    };

    const stopScanning = (message = 'Camera stopped. Enable it to scan again.') => {
        runId += 1;
        phase = 'idle';
        stopResources();
        enableButton.disabled = false;
        if (message) setStatus(message);
    };

    const isCurrentRun = (currentRun) => currentRun === runId
        && (phase === 'starting' || phase === 'scanning');

    const openDestination = (rawValue, currentRun) => {
        if (!isCurrentRun(currentRun)) return false;

        const destination = queueFlowDestination(
            rawValue,
            window.location.origin,
            scanner.dataset.queueflowOrigin,
        );

        if (!destination) {
            setStatus('That is not a valid QueueFlow queue code');
            return false;
        }

        phase = 'navigating';
        runId += 1;
        setStatus('QueueFlow code found — opening preview');
        stopResources();
        window.location.assign(destination);

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

    const scanWithFallback = async (currentRun) => {
        if (!isCurrentRun(currentRun)) return;

        const reader = new BrowserMultiFormatReader();
        const fallbackControls = await reader.decodeFromVideoElement(video, (result) => {
            if (result) openDestination(result.getText(), currentRun);
        });

        if (!isCurrentRun(currentRun)) {
            fallbackControls.stop();
            return;
        }

        controls = fallbackControls;
    };

    const scanWithNativeDetector = async (detector, currentRun) => {
        let consecutiveErrors = 0;

        const scanFrame = async () => {
            if (!isCurrentRun(currentRun)) return;

            try {
                const barcodes = await detector.detect(video);
                consecutiveErrors = 0;
                if (barcodes[0]?.rawValue && openDestination(barcodes[0].rawValue, currentRun)) return;
            } catch {
                consecutiveErrors += 1;
                if (consecutiveErrors >= 5 && isCurrentRun(currentRun)) {
                    await scanWithFallback(currentRun);
                    return;
                }
            }

            if (isCurrentRun(currentRun)) frameId = window.requestAnimationFrame(scanFrame);
        };

        frameId = window.requestAnimationFrame(scanFrame);
    };

    const openCamera = async (currentRun) => {
        const constraints = [
            { video: { facingMode: { exact: 'environment' } }, audio: false },
            { video: { facingMode: { ideal: 'environment' } }, audio: false },
            { video: true, audio: false },
        ];
        let lastError;

        for (const constraint of constraints) {
            if (!isCurrentRun(currentRun)) return null;

            try {
                return await navigator.mediaDevices.getUserMedia(constraint);
            } catch (error) {
                lastError = error;
                if (!isCurrentRun(currentRun)) return null;
                if (!['OverconstrainedError', 'NotFoundError'].includes(error.name)) break;
            }
        }

        throw lastError;
    };

    const startScanning = async () => {
        if (phase !== 'idle') return;

        if (!window.isSecureContext) {
            setStatus('Camera scanning requires HTTPS. Use the code below.');
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('This browser cannot access a camera. Use the code below.');
            return;
        }

        const currentRun = ++runId;
        phase = 'starting';
        enableButton.disabled = true;
        setStatus('Starting camera…');

        let cameraStream;

        try {
            cameraStream = await openCamera(currentRun);
            if (!isCurrentRun(currentRun)) {
                stopStream(cameraStream);
                return;
            }

            stream = cameraStream;
            video.srcObject = cameraStream;
            await video.play();
            if (!isCurrentRun(currentRun)) {
                stopStream(cameraStream);
                if (video.srcObject === cameraStream) video.srcObject = null;
                return;
            }

            placeholder.hidden = true;
            phase = 'scanning';
            setStatus('Looking for a QR code');

            const track = cameraStream.getVideoTracks()[0];
            const capabilities = track?.getCapabilities?.() ?? {};
            if (capabilities.torch) {
                flashlightButton.setAttribute('aria-disabled', 'false');
                flashlightButton.classList.remove('text-white/45');
            }

            const detector = await nativeDetector();
            if (!isCurrentRun(currentRun)) return;

            if (detector) {
                await scanWithNativeDetector(detector, currentRun);
            } else {
                await scanWithFallback(currentRun);
            }
        } catch (error) {
            if (!isCurrentRun(currentRun)) {
                stopStream(cameraStream);
                return;
            }

            stopScanning(null);
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                setStatus('Camera permission was denied. Allow access or use the code below.');
            } else if (error?.name === 'NotFoundError' || error?.name === 'OverconstrainedError') {
                setStatus('No suitable camera was found. Use the code below.');
            } else if (error?.name === 'NotReadableError') {
                setStatus('The camera is busy or unavailable. Close other camera apps and try again.');
            } else {
                setStatus('The camera could not start. Use the code below.');
            }
        }
    };

    flashlightButton.addEventListener('click', async () => {
        const track = stream?.getVideoTracks()[0];
        if (!track || flashlightButton.getAttribute('aria-disabled') === 'true') return;

        try {
            torchEnabled = !torchEnabled;
            await track.applyConstraints({ advanced: [{ torch: torchEnabled }] });
            flashlightButton.setAttribute('aria-pressed', String(torchEnabled));
        } catch {
            torchEnabled = !torchEnabled;
            setStatus('Flashlight control is unavailable on this camera');
        }
    });

    enableButton.addEventListener('click', startScanning);
    window.addEventListener('pagehide', () => stopScanning(null));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && (phase === 'starting' || phase === 'scanning')) stopScanning();
    });
}
