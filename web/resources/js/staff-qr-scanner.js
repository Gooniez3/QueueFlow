import { BrowserMultiFormatReader } from '@zxing/browser';

const scannerRoot = document.querySelector('[data-qr-scanner]');

if (scannerRoot) {
    const start = scannerRoot.querySelector('[data-qr-start]');
    const video = scannerRoot.querySelector('[data-qr-camera]');
    const wrap = scannerRoot.querySelector('[data-qr-camera-wrap]');
    const status = scannerRoot.querySelector('[data-qr-camera-status]');
    const credential = document.querySelector('#credential');
    let stream = null;
    let controls = null;
    let submitted = false;

    const setStatus = (message) => {
        if (status) status.textContent = message;
    };

    const stop = () => {
        controls?.stop();
        controls = null;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        if (video) video.srcObject = null;
    };

    const submitCredential = (value) => {
        const normalized = value?.trim();

        if (!normalized) {
            setStatus('No ticket credential was found. Try again or use manual entry.');
            return false;
        }

        if (submitted) return true;

        submitted = true;
        credential.value = normalized;
        stop();
        credential.form.requestSubmit();
        return true;
    };

    const scanWithNativeDetector = async () => {
        const detector = new window.BarcodeDetector({ formats: ['qr_code'] });

        const scan = async () => {
            if (submitted || !stream) return;

            try {
                const codes = await detector.detect(video);
                if (codes[0]?.rawValue && submitCredential(codes[0].rawValue)) return;
            } catch {
                // The next animation frame may succeed while the camera warms up.
            }

            window.requestAnimationFrame(scan);
        };

        scan();
    };

    const scanWithFallback = async () => {
        const reader = new BrowserMultiFormatReader();

        controls = await reader.decodeFromVideoElement(video, (result) => {
            if (result) submitCredential(result.getText());
        });
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
                return;
            } catch (error) {
                lastError = error;
                if (!['OverconstrainedError', 'NotFoundError'].includes(error.name)) break;
            }
        }

        throw lastError;
    };

    start?.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Camera access is unavailable in this browser. Use manual entry below.');
            return;
        }

        submitted = false;
        start.disabled = true;

        try {
            await openCamera();
            video.srcObject = stream;
            await video.play();
            wrap.classList.remove('hidden');
            setStatus('Point the camera at the customer QR code.');

            if ('BarcodeDetector' in window) {
                await scanWithNativeDetector();
            } else {
                await scanWithFallback();
            }
        } catch (error) {
            stop();
            start.disabled = false;

            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                setStatus('Camera permission was denied. Use manual entry below.');
            } else if (error?.name === 'NotFoundError') {
                setStatus('No camera was found. Use manual entry below.');
            } else {
                setStatus('Camera scanning is unavailable here. Use manual entry below.');
            }
        }
    });

    window.addEventListener('pagehide', stop, { once: true });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop();
    });
}
