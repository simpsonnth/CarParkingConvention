import { Html5Qrcode } from 'html5-qrcode';

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

function qrboxSize(viewfinderWidth, viewfinderHeight) {
    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
    const size = Math.floor(minEdge * 0.7);

    return Math.max(160, Math.min(size, 300));
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
        await activeWire.set('uuid', decodedText);
        await activeWire.scan();
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
        setToggleLabel(false);

        return Promise.resolve();
    }

    return html5QrCode.stop().then(() => {
        isScanning = false;
        html5QrCode = null;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        setToggleLabel(false);
    }).catch((err) => {
        console.error(err);
        isScanning = false;
        html5QrCode = null;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        setToggleLabel(false);
    });
}

function cameraConfigs(devices) {
    const configs = [{ facingMode: 'environment' }];
    const backId = preferBackCameraId(devices);

    if (backId) {
        configs.push({ deviceId: { exact: backId } });
        configs.push({ deviceId: backId });
    }

    if (devices[0]?.id && devices[0].id !== backId) {
        configs.push({ deviceId: devices[0].id });
    }

    configs.push({});

    return configs;
}

async function startScannerWithFallback(devices) {
    const readerDiv = getReaderDiv();
    const configs = cameraConfigs(devices);
    let lastError = null;

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

            html5QrCode = new Html5Qrcode('reader');
            await html5QrCode.start(
                config,
                {
                    fps: 12,
                    qrbox: qrboxSize,
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
            releaseScanLock();
        });

        readyHandlerAttached = true;
    }

    setToggleLabel(isScanning);
}

window.initAttendantScan = initAttendantScan;
window.releaseAttendantScanLock = releaseScanLock;
window.clearAttendantScanMemory = clearScanMemory;
document.dispatchEvent(new CustomEvent('attendant-scan:ready'));
