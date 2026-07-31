import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

let html5QrCode = null;
let isScanning = false;
let clickHandlerAttached = false;
let readyHandlerAttached = false;
let activeWire = null;
let scanBusy = false;
let lastDecodedText = null;
let lastScanAt = 0;
let camerasCache = null;

const SCAN_COOLDOWN_MS = 2000;

function getToggleButtons() {
    return Array.from(document.querySelectorAll('[data-attendant-scan-toggle]'));
}

function setToggleLabel(scanning) {
    getToggleButtons().forEach((toggleBtn) => {
        const label = toggleBtn.querySelector('[data-attendant-scan-label]');
        if (label) {
            label.textContent = scanning ? 'Stop Camera' : 'Scan with Camera';
        } else {
            toggleBtn.textContent = scanning ? 'Stop Camera' : 'Scan with Camera';
        }
    });
}

function getReaderDiv() {
    return document.getElementById('reader');
}

function getReaderShell() {
    return document.querySelector('[data-attendant-camera-card]')
        || document.querySelector('[data-attendant-reader-shell]');
}

/**
 * html5-qrcode requires qrbox functions to return { width, height }.
 * Returning a bare number leaves qrDimensions.width/height undefined.
 */
function qrboxSize(viewfinderWidth, viewfinderHeight) {
    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
    const size = Math.max(180, Math.min(Math.floor(minEdge * 0.85), 360));

    return { width: size, height: size };
}

function preferBackCameraId(devices) {
    if (!devices || devices.length === 0) {
        return null;
    }

    const back = devices.find((device) => /back|rear|environment/i.test(device.label || ''));

    return (back || devices[0]).id;
}

function releaseScanLock() {
    scanBusy = false;
}

function clearScanMemory() {
    scanBusy = false;
    lastDecodedText = null;
    lastScanAt = 0;
}

function showReaderShell() {
    const shell = getReaderShell();
    if (shell) {
        shell.style.display = '';
    }

    const readerDiv = getReaderDiv();
    if (readerDiv && isScanning) {
        readerDiv.style.display = 'block';
    }
}

function hideReaderShell() {
    const shell = getReaderShell();
    if (shell) {
        shell.style.display = 'none';
    }
}

function scrollConfirmIntoView() {
    window.requestAnimationFrame(() => {
        const confirm = document.querySelector('[data-attendant-scan-confirm]');
        if (confirm) {
            confirm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

async function pauseScannerUi() {
    hideReaderShell();

    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.pause(true);
        } catch {
            // Pause is best-effort; stream may already be idle.
        }
    }

    scrollConfirmIntoView();
}

async function resumeScannerUi() {
    showReaderShell();

    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.resume();
        } catch {
            // Resume is best-effort after Livewire morphs.
        }
    }
}

async function handleDecodedText(decodedText) {
    if (!activeWire || !decodedText) {
        return;
    }

    const now = Date.now();

    if (scanBusy) {
        return;
    }

    if (decodedText === lastDecodedText && (now - lastScanAt) < SCAN_COOLDOWN_MS) {
        return;
    }

    if ((now - lastScanAt) < SCAN_COOLDOWN_MS) {
        return;
    }

    scanBusy = true;
    lastDecodedText = decodedText;
    lastScanAt = now;

    if (navigator.vibrate) {
        navigator.vibrate(200);
    }

    try {
        // One round-trip: pass the payload directly (avoids set+scan races).
        await activeWire.call('scan', decodedText);
        await pauseScannerUi();
    } catch (err) {
        console.error(err);
    } finally {
        window.setTimeout(() => {
            releaseScanLock();
        }, SCAN_COOLDOWN_MS);
    }
}

function stopScanner() {
    const readerDiv = getReaderDiv();

    if (!html5QrCode) {
        isScanning = false;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        showReaderShell();
        setToggleLabel(false);

        return Promise.resolve();
    }

    return html5QrCode.stop().then(() => {
        isScanning = false;
        html5QrCode = null;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        showReaderShell();
        setToggleLabel(false);
    }).catch((err) => {
        console.error(err);
        isScanning = false;
        html5QrCode = null;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        showReaderShell();
        setToggleLabel(false);
    });
}

function cameraConfigs(devices) {
    const configs = [];
    const backId = preferBackCameraId(devices);

    // Prefer explicit rear deviceId first — facingMode-only often fails or
    // picks a weak lens on multi-camera phones.
    if (backId) {
        configs.push({ deviceId: { exact: backId } });
        configs.push({ deviceId: backId });
    }

    configs.push({ facingMode: 'environment' });

    if (devices[0]?.id && devices[0].id !== backId) {
        configs.push({ deviceId: devices[0].id });
    }

    configs.push({});

    return configs;
}

function waitForLayout() {
    return new Promise((resolve) => {
        window.requestAnimationFrame(() => {
            window.setTimeout(resolve, 50);
        });
    });
}

async function startScannerWithFallback(devices) {
    const configs = cameraConfigs(devices);
    let lastError = null;

    await waitForLayout();

    for (const config of configs) {
        try {
            if (html5QrCode) {
                try {
                    await html5QrCode.stop();
                } catch {
                    // Ignore stop failures while retrying.
                }
                html5QrCode = null;
            }

            const readerDiv = getReaderDiv();
            if (readerDiv) {
                readerDiv.innerHTML = '';
                readerDiv.style.display = 'block';
            }

            html5QrCode = new Html5Qrcode('reader', {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                verbose: false,
            });

            await html5QrCode.start(
                config,
                {
                    fps: 15,
                    qrbox: qrboxSize,
                    aspectRatio: 1.333,
                    disableFlip: false,
                },
                (decodedText) => {
                    handleDecodedText(decodedText);
                },
                () => {
                    // Ignore continuous frame failures while searching.
                },
            );

            isScanning = true;
            setToggleLabel(true);
            showReaderShell();

            return;
        } catch (err) {
            lastError = err;
            console.error('Camera start attempt failed', config, err);
            html5QrCode = null;
        }
    }

    throw lastError || new Error('Unable to start camera');
}

function explainCameraError(err) {
    if (!err) {
        alert('Camera Start Error: unknown failure');

        return;
    }

    if (err.name === 'NotAllowedError') {
        alert('Camera access was denied. Please allow camera permissions in your browser settings.');
    } else if (err.name === 'NotFoundError') {
        alert('No camera found on this device.');
    } else if (err.name === 'NotReadableError') {
        alert('Camera is improperly configured, in use, or blocked by system settings.');
    } else if (err.name === 'OverconstrainedError') {
        alert('Could not open the rear camera. Please check permissions and try again.');
    } else {
        alert('Camera Start Error: ' + err);
    }
}

async function handleToggleCamera() {
    const readerDiv = getReaderDiv();

    if (!readerDiv || getToggleButtons().length === 0) {
        return;
    }

    if (isScanning) {
        await stopScanner();

        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Your browser does not support camera access. Use HTTPS and a modern browser (e.g. Chrome or Safari on your phone).');

        return;
    }

    showReaderShell();
    readerDiv.style.display = 'block';

    try {
        if (!camerasCache) {
            camerasCache = await Html5Qrcode.getCameras();
        }

        if (!camerasCache || camerasCache.length === 0) {
            alert('No cameras found on your device.');
            readerDiv.style.display = 'none';

            return;
        }

        await startScannerWithFallback(camerasCache);
    } catch (err) {
        console.error('Error accessing camera', err);
        explainCameraError(err);
        readerDiv.style.display = 'none';
        isScanning = false;
        setToggleLabel(false);
    }
}

export function initAttendantScan(wire) {
    activeWire = wire;

    if (!document.getElementById('reader') || getToggleButtons().length === 0) {
        return;
    }

    if (!clickHandlerAttached) {
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-attendant-scan-toggle]')) {
                return;
            }

            event.preventDefault();
            handleToggleCamera();
        });

        clickHandlerAttached = true;
    }

    if (!readyHandlerAttached) {
        document.addEventListener('attendant-scan:ready-for-next', () => {
            clearScanMemory();
            resumeScannerUi();
        });

        readyHandlerAttached = true;
    }

    setToggleLabel(isScanning);
}

window.initAttendantScan = initAttendantScan;
window.releaseAttendantScanLock = releaseScanLock;
window.clearAttendantScanMemory = clearScanMemory;
window.resumeAttendantScannerUi = resumeScannerUi;
window.pauseAttendantScannerUi = pauseScannerUi;
document.dispatchEvent(new CustomEvent('attendant-scan:ready'));
