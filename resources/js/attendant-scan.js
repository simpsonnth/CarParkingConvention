import { Html5Qrcode } from 'html5-qrcode';

let html5QrCode = null;
let isScanning = false;
let clickHandlerAttached = false;
let activeWire = null;

function setToggleLabel(toggleBtn, scanning) {
    if (!toggleBtn) {
        return;
    }

    toggleBtn.textContent = scanning ? 'Stop Camera' : 'Scan with Camera';
}

function stopScanner(readerDiv, toggleBtn) {
    if (!html5QrCode) {
        isScanning = false;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        setToggleLabel(toggleBtn, false);

        return Promise.resolve();
    }

    return html5QrCode.stop().then(() => {
        isScanning = false;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        setToggleLabel(toggleBtn, false);
    }).catch((err) => {
        console.error(err);
        isScanning = false;
        if (readerDiv) {
            readerDiv.style.display = 'none';
        }
        setToggleLabel(toggleBtn, false);
    });
}

function startScanner(wire, readerDiv, toggleBtn) {
    html5QrCode = new Html5Qrcode('reader');

    return html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: 250 },
        (decodedText) => {
            wire.set('uuid', decodedText);

            if (navigator.vibrate) {
                navigator.vibrate(200);
            }

            stopScanner(readerDiv, toggleBtn).then(() => {
                wire.scan();
            });
        },
        () => {
            // Ignore continuous scan errors while searching for a code.
        },
    ).then(() => {
        isScanning = true;
        setToggleLabel(toggleBtn, true);
    }).catch((err) => {
        console.error(err);

        if (err.name === 'NotAllowedError') {
            alert('Camera access was denied. Please allow camera permissions in your browser settings.');
        } else if (err.name === 'NotFoundError') {
            alert('No camera found on this device.');
        } else if (err.name === 'NotReadableError') {
            alert('Camera is improperly configured, in use, or blocked by system settings.');
        } else if (err.name === 'OverconstrainedError') {
            alert('Camera constraints failed. Retrying with default settings...');
        } else {
            alert('Camera Start Error: ' + err);
        }

        readerDiv.style.display = 'none';
        isScanning = false;
        setToggleLabel(toggleBtn, false);
    });
}

function handleToggleCamera(wire) {
    const toggleBtn = document.getElementById('toggle-camera');
    const readerDiv = document.getElementById('reader');

    if (!toggleBtn || !readerDiv) {
        return;
    }

    if (isScanning) {
        stopScanner(readerDiv, toggleBtn);

        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Your browser does not support camera access. Use HTTPS and a modern browser (e.g. Chrome or Safari on your phone).');

        return;
    }

    readerDiv.style.display = 'block';

    Html5Qrcode.getCameras().then((devices) => {
        if (!devices || !devices.length) {
            alert('No cameras found on your device.');
            readerDiv.style.display = 'none';

            return;
        }

        if (html5QrCode) {
            html5QrCode.stop().catch(() => {}).then(() => {
                html5QrCode = null;
                startScanner(wire, readerDiv, toggleBtn);
            });

            return;
        }

        startScanner(wire, readerDiv, toggleBtn);
    }).catch((err) => {
        console.error('Error fetching cameras', err);
        alert('Error accessing camera information: ' + err);
        readerDiv.style.display = 'none';
    });
}

export function initAttendantScan(wire) {
    activeWire = wire;

    if (!document.getElementById('toggle-camera')) {
        return;
    }

    if (!clickHandlerAttached) {
        document.addEventListener('click', (event) => {
            if (!event.target.closest('#toggle-camera')) {
                return;
            }

            if (!activeWire) {
                return;
            }

            handleToggleCamera(activeWire);
        });

        clickHandlerAttached = true;
    }
}

window.initAttendantScan = initAttendantScan;
document.dispatchEvent(new CustomEvent('attendant-scan:ready'));
