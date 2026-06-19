<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Print Pass -
        {{ isset($registration) && ($registration->is_circuit_overseer ?? false) ? __('print_pass.circuit_overseer') : ($congregation->name ?? __('print_pass.circuit_overseer')) }}
    </title>
    @unless($forPdf ?? false)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    <style>
        :root {
            --pass-park: #4338ca;
            --pass-park-dark: #312e81;
            --pass-park-text: #ffffff;
            --pass-ink: #0c0a09;
            --pass-muted: #78716c;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            height: auto;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .print-color-exact {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgb(255 255 255 / 0.92);
            border-bottom: 1px solid #e4e4e7;
        }

        .no-print button {
            border: none;
            padding: 0.65rem 1.5rem;
            border-radius: 0.65rem;
            font-weight: 700;
            cursor: pointer;
        }

        .no-print button:first-child {
            background: #18181b;
            color: #fff;
        }

        .no-print button:last-child {
            background: #f4f4f5;
            color: #52525b;
        }

        .pass-outer {
            width: 100%;
            min-height: 100vh;
            height: auto;
            padding: 4mm;
            display: flex;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pass-outer--front {
            page-break-after: always;
            break-after: page;
        }

        .pass {
            width: 100%;
            min-height: calc(100vh - 8mm);
            height: 100%;
            display: flex;
            flex-direction: row;
            border-radius: 6mm;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
            break-inside: avoid;
            box-shadow:
                0 0 0 3px var(--pass-park),
                0 20px 50px rgb(0 0 0 / 0.22);
        }

        .pass-spine {
            flex: 0 0 10mm;
            background: linear-gradient(180deg, var(--pass-park) 0%, var(--pass-park-dark) 100%);
        }

        .pass-inner {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .pass-top {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5mm;
            padding: 4mm 7mm;
            background: var(--pass-ink);
            color: #fff;
        }

        .pass-top-start {
            display: flex;
            align-items: center;
            gap: 4mm;
            min-width: 0;
        }

        .pass-top-logo {
            flex-shrink: 0;
            max-height: 13mm;
            max-width: 22mm;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .pass-top-org {
            font-size: clamp(0.68rem, 1.6vw, 0.88rem);
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .pass-top-event {
            margin-top: 1mm;
            font-size: clamp(0.58rem, 1.2vw, 0.72rem);
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #a8a29e;
        }

        .pass-top-badge {
            flex-shrink: 0;
            padding: 2mm 3.5mm;
            border: 2px solid var(--pass-park);
            border-radius: 2mm;
            background: rgb(255 255 255 / 0.06);
            font-size: clamp(0.55rem, 1.1vw, 0.68rem);
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            text-align: center;
            line-height: 1.25;
            color: #fff;
        }

        .pass-zone {
            flex-shrink: 0;
            position: relative;
            padding: 5mm 7mm 6mm;
            color: var(--pass-park-text);
            background: linear-gradient(118deg, var(--pass-park) 0%, var(--pass-park-dark) 72%);
            overflow: hidden;
        }

        .pass-zone::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -55deg,
                transparent,
                transparent 7px,
                rgb(255 255 255 / 0.07) 7px,
                rgb(255 255 255 / 0.07) 14px
            );
            pointer-events: none;
        }

        .pass-zone::after {
            content: '';
            position: absolute;
            right: -8mm;
            top: -12mm;
            width: 40mm;
            height: 40mm;
            border-radius: 50%;
            background: rgb(255 255 255 / 0.08);
            pointer-events: none;
        }

        .pass-zone-kicker {
            position: relative;
            font-size: clamp(0.55rem, 1.1vw, 0.68rem);
            font-weight: 800;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            opacity: 0.88;
        }

        .pass-zone-name {
            position: relative;
            margin-top: 1.5mm;
            font-size: clamp(2.4rem, 8vw, 4.5rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            word-break: break-word;
            hyphens: auto;
            text-shadow: 0 2px 12px rgb(0 0 0 / 0.18);
        }

        .pass-main {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: row;
        }

        .pass-main-left {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5mm 7mm;
            gap: 4mm;
            background: #fff;
        }

        .pass-identity-label {
            font-size: clamp(0.62rem, 1.3vw, 0.75rem);
            font-weight: 800;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--pass-muted);
        }

        .pass-identity-name {
            margin-top: 1.5mm;
            font-size: clamp(2rem, 6.8vw, 3.75rem);
            font-weight: 900;
            line-height: 0.98;
            letter-spacing: -0.03em;
            text-transform: uppercase;
            color: var(--pass-ink);
            word-break: break-word;
            hyphens: auto;
        }

        .pass-identity-name--long {
            font-size: clamp(1.55rem, 5vw, 2.8rem);
            letter-spacing: -0.05em;
        }

        .pass-identity-name::after {
            content: '';
            display: block;
            width: 18mm;
            height: 1.5mm;
            margin-top: 3mm;
            background: linear-gradient(90deg, var(--pass-park), var(--pass-park-dark));
            border-radius: 999px;
        }

        .pass-identity-registrant {
            margin-top: 2mm;
            font-size: clamp(0.95rem, 2.4vw, 1.25rem);
            font-weight: 700;
            color: #57534e;
        }

        .pass-number {
            display: inline-flex;
            flex-direction: column;
            align-self: flex-start;
            padding: 3mm 5mm;
            background: var(--pass-ink);
            color: #fff;
            border-radius: 3mm;
            border-left: 4px solid var(--pass-park);
            box-shadow: 0 4px 14px rgb(0 0 0 / 0.15);
        }

        .pass-number-label {
            font-size: clamp(0.55rem, 1.1vw, 0.65rem);
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #a8a29e;
        }

        .pass-number-value {
            margin-top: 1mm;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: clamp(1.75rem, 5.5vw, 3rem);
            font-weight: 900;
            letter-spacing: 0.1em;
            line-height: 1;
        }

        .pass-alerts {
            display: flex;
            flex-direction: column;
            gap: 2mm;
        }

        .pass-alert {
            padding: 2.5mm 4mm;
            font-size: clamp(0.72rem, 1.7vw, 0.92rem);
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-radius: 2mm;
            border-left: 4px solid;
        }

        .pass-alert--coach {
            background: linear-gradient(90deg, #fef3c7, #fffbeb);
            color: #92400e;
            border-left-color: #f59e0b;
        }

        .pass-alert--elderly {
            background: linear-gradient(90deg, #eef2ff, #f5f3ff);
            color: #3730a3;
            border-left-color: #6366f1;
        }

        .pass-scan {
            flex: 0 0 min(44%, 82mm);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3mm;
            padding: 6mm 5mm;
            background: var(--pass-ink);
            color: #fff;
        }

        .pass-scan-label {
            font-size: clamp(0.5rem, 1vw, 0.6rem);
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #a8a29e;
        }

        .pass-qr-frame {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            max-width: 100%;
            padding: 3mm;
            background: #fff;
            border-radius: 3mm;
            box-shadow: 0 0 0 2px var(--pass-park), 0 8px 20px rgb(0 0 0 / 0.35);
            line-height: 0;
        }

        .pass-qr-frame::before,
        .pass-qr-frame::after {
            content: '';
            position: absolute;
            width: 5mm;
            height: 5mm;
            border: 2px solid var(--pass-park);
        }

        .pass-qr-frame::before {
            top: -1.5mm;
            left: -1.5mm;
            border-right: none;
            border-bottom: none;
        }

        .pass-qr-frame::after {
            bottom: -1.5mm;
            right: -1.5mm;
            border-left: none;
            border-top: none;
        }

        .pass-qr {
            display: block;
            width: 52mm;
            height: 52mm;
            max-width: 100%;
            object-fit: contain;
            vertical-align: top;
        }

        .pass-scan-id-label {
            font-size: clamp(0.48rem, 0.9vw, 0.55rem);
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #78716c;
        }

        .pass-scan-id {
            max-width: 100%;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: clamp(0.4rem, 0.8vw, 0.48rem);
            line-height: 1.3;
            color: #a8a29e;
            text-align: center;
            word-break: break-all;
        }

        .pass-ribbon {
            flex-shrink: 0;
            position: relative;
            padding: 4.5mm 7mm;
            text-align: center;
            font-size: clamp(1rem, 2.8vw, 1.35rem);
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--pass-park-text);
            background: linear-gradient(90deg, var(--pass-park-dark) 0%, var(--pass-park) 50%, var(--pass-park-dark) 100%);
            overflow: hidden;
        }

        .pass-ribbon::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                90deg,
                transparent,
                transparent 12px,
                rgb(255 255 255 / 0.06) 12px,
                rgb(255 255 255 / 0.06) 24px
            );
            pointer-events: none;
        }

        .pass-ribbon-text {
            position: relative;
        }

        .pass-fine-print {
            flex-shrink: 0;
            padding: 2.5mm 7mm;
            background: #f5f5f4;
            border-top: 1px solid #e7e5e4;
            font-size: clamp(0.55rem, 1.2vw, 0.68rem);
            line-height: 1.35;
            font-weight: 600;
            color: #57534e;
            text-align: center;
        }

        .pass-back-outer {
            width: 100%;
            min-height: 100vh;
            height: auto;
            padding: 4mm;
            display: flex;
            page-break-before: always;
            break-before: page;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pass-back {
            width: 100%;
            min-height: calc(100vh - 8mm);
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 6mm;
            overflow: hidden;
            background: #fff;
            box-shadow:
                0 0 0 3px var(--pass-park),
                0 20px 50px rgb(0 0 0 / 0.22);
        }

        .pass-back-header {
            flex-shrink: 0;
            padding: 4mm 7mm;
            background: linear-gradient(118deg, var(--pass-park) 0%, var(--pass-park-dark) 72%);
            color: var(--pass-park-text);
        }

        .pass-back-heading {
            font-size: clamp(1rem, 2.5vw, 1.35rem);
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .pass-back-subheading {
            margin-top: 1mm;
            font-size: clamp(0.7rem, 1.5vw, 0.85rem);
            font-weight: 600;
            opacity: 0.92;
        }

        .pass-back-zone {
            margin-top: 2mm;
            font-size: clamp(0.85rem, 2vw, 1.1rem);
            font-weight: 700;
        }

        .pass-back-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 5mm 7mm;
            gap: 4mm;
        }

        .pass-back-map {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e7e5e4;
            border-radius: 3mm;
            background: #fafaf9;
            overflow: hidden;
        }

        .pass-back-map img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .pass-back-map-placeholder {
            padding: 6mm;
            text-align: center;
            color: #57534e;
            font-size: clamp(0.7rem, 1.5vw, 0.85rem);
            line-height: 1.45;
            font-weight: 600;
        }

        .pass-back-map-location {
            margin-top: 2mm;
            font-weight: 700;
            color: #292524;
        }

        .pass-back-notes {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 2.5mm;
            font-size: clamp(0.65rem, 1.3vw, 0.78rem);
            line-height: 1.4;
            color: #44403c;
        }

        .pass-back-contact {
            padding: 3mm 4mm;
            background: #f5f5f4;
            border-radius: 2mm;
            border: 1px solid #e7e5e4;
        }

        .pass-back-contact-row {
            display: flex;
            gap: 2mm;
            margin-bottom: 1mm;
        }

        .pass-back-contact-row:last-child {
            margin-bottom: 0;
        }

        .pass-back-contact-label {
            flex: 0 0 auto;
            font-weight: 700;
            color: #292524;
        }

        .pass-back-contact-value {
            font-weight: 600;
        }

        .pass-back-note {
            font-weight: 500;
        }

        .pass-back-footer {
            flex-shrink: 0;
            padding: 4mm 7mm;
            background: var(--pass-ink);
            color: #fff;
            text-align: center;
        }

        .pass-back-scripture-ref {
            font-size: clamp(0.7rem, 1.4vw, 0.82rem);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .pass-back-scripture-text {
            margin-top: 1.5mm;
            font-size: clamp(0.85rem, 1.8vw, 1rem);
            font-weight: 700;
            font-style: italic;
            line-height: 1.35;
        }

        .pass-back-closing {
            margin-top: 2.5mm;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
            font-weight: 800;
        }

        @media print {
            @page {
                margin: 0;
                size: A4 landscape;
            }

            .no-print {
                display: none !important;
            }

            html,
            body {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                background: #fff !important;
            }

            .pass-outer,
            .pass-back-outer {
                width: 100%;
                height: 210mm;
                min-height: 210mm;
                max-height: 210mm;
                padding: 3mm;
                box-sizing: border-box;
            }

            .pass-outer--front {
                page-break-after: always;
                break-after: page;
            }

            .pass-back-outer {
                page-break-before: always;
                break-before: page;
                page-break-after: auto;
                break-after: auto;
            }

            .pass,
            .pass-back {
                height: 100%;
                min-height: 0;
                border-radius: 4mm;
                box-shadow: none;
                page-break-after: auto;
                break-after: auto;
            }

            .pass-spine {
                flex-basis: 8mm;
            }

            .pass-top {
                padding: 3.5mm 7mm;
            }

            .pass-top-org {
                font-size: 3.5mm;
            }

            .pass-top-event {
                font-size: 3mm;
            }

            .pass-top-badge {
                font-size: 3mm;
            }

            .pass-zone {
                padding: 4.5mm 7mm 5mm;
            }

            .pass-zone-kicker {
                font-size: 3mm;
            }

            .pass-zone-name {
                font-size: 26mm;
            }

            .pass-identity-label {
                font-size: 3.5mm;
            }

            .pass-identity-name {
                font-size: 18mm;
            }

            .pass-identity-name--long {
                font-size: 14mm;
            }

            .pass-identity-registrant {
                font-size: 8mm;
            }

            .pass-number-label {
                font-size: 3mm;
            }

            .pass-number-value {
                font-size: 12mm;
            }

            .pass-alert {
                font-size: 5.5mm;
            }

            .pass-scan {
                flex: 0 0 82mm;
                padding: 5mm 4mm;
            }

            .pass-qr {
                width: 50mm;
                height: 50mm;
            }

            .pass-scan-label {
                font-size: 2.5mm;
            }

            .pass-scan-id-label {
                font-size: 2.2mm;
            }

            .pass-scan-id {
                font-size: 2mm;
            }

            .pass-ribbon {
                font-size: 5.5mm;
                padding: 4mm 7mm;
            }

            .pass-fine-print {
                font-size: 3.5mm;
            }
        }

        @media screen {
            body {
                background: linear-gradient(145deg, #44403c, #1c1917);
                overflow-y: auto;
            }

            .pass-outer,
            .pass-back-outer {
                min-height: 100vh;
                padding-top: 4.5rem;
            }

            .pass-back-outer {
                padding-top: 2rem;
            }
        }
    </style>
    @if($forPdf ?? false)
    <style>
        @page { margin: 0; size: A4 landscape; }
        .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; }
        .pass-outer, .pass-back-outer { width: 100%; height: 210mm; min-height: 210mm; max-height: 210mm; padding: 3mm; box-sizing: border-box; }
        .pass-outer--front { page-break-after: always; break-after: page; }
        .pass-back-outer { page-break-before: always; break-before: page; }
        .pass, .pass-back { height: 100%; min-height: 0; border-radius: 4mm; box-shadow: none; page-break-after: auto; break-after: auto; }
        .pass-spine { flex-basis: 8mm; }
        .pass-top { padding: 3.5mm 7mm; }
        .pass-top-org { font-size: 3.5mm; }
        .pass-top-event { font-size: 3mm; }
        .pass-top-badge { font-size: 3mm; }
        .pass-zone { padding: 4.5mm 7mm 5mm; }
        .pass-zone-kicker { font-size: 3mm; }
        .pass-zone-name { font-size: 26mm; }
        .pass-identity-label { font-size: 3.5mm; }
        .pass-identity-name { font-size: 18mm; }
        .pass-identity-name--long { font-size: 14mm; }
        .pass-identity-registrant { font-size: 8mm; }
        .pass-number-label { font-size: 3mm; }
        .pass-number-value { font-size: 12mm; }
        .pass-alert { font-size: 5.5mm; }
        .pass-scan { flex: 0 0 82mm; padding: 5mm 4mm; }
        .pass-qr { width: 50mm; height: 50mm; }
        .pass-scan-label { font-size: 2.5mm; }
        .pass-scan-id-label { font-size: 2.2mm; }
        .pass-scan-id { font-size: 2mm; }
        .pass-ribbon { font-size: 5.5mm; padding: 4mm 7mm; }
        .pass-fine-print { font-size: 3.5mm; }
    </style>
    @endif
</head>

<body>
    @php
        $convYear = \App\Models\Setting::get('convention_year', date('Y'));
        $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
        $ticketLogo = \App\Models\Setting::get('ticket_logo');
        $carPark = $effectiveCarPark ?? $congregation?->carPark ?? null;
        $carParkColor = $carPark?->color ?? '#4338ca';
        $carParkTextColor = $carPark?->contrastingTextColor() ?? '#ffffff';
        $heroBackground = $carPark?->color ?? '#71717a';
        $heroTextColor = $carPark ? $carParkTextColor : '#ffffff';

        $ticketShade = static function (?string $hex, int $percent = 28): string {
            $hex = ltrim((string) $hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
                return '#312e81';
            }
            $factor = 1 - ($percent / 100);
            $r = max(0, min(255, (int) round(hexdec(substr($hex, 0, 2)) * $factor)));
            $g = max(0, min(255, (int) round(hexdec(substr($hex, 2, 2)) * $factor)));
            $b = max(0, min(255, (int) round(hexdec(substr($hex, 4, 2)) * $factor)));

            return sprintf('#%02x%02x%02x', $r, $g, $b);
        };

        $parkDark = $ticketShade($heroBackground, 28);
        $hasQr = (! isset($registration) || ! ($registration->is_circuit_overseer ?? false)) && isset($congregation);
        $zoneName = $carPark?->name ?? __('print_pass.unassigned_car_park');
        $isCircuitOverseer = isset($registration) && ($registration->is_circuit_overseer ?? false);
        $heroName = $isCircuitOverseer
            ? __('print_pass.circuit_overseer')
            : ($congregation->name ?? null);
        $heroLabel = $isCircuitOverseer
            ? __('print_pass.registration_type')
            : (isset($congregation) ? __('print_pass.congregation') : null);
        $heroNameLong = $heroName && strlen($heroName) > 40;
        $showCoachAlert = isset($registration) && (($registration->vehicle_type ?? 'car') === 'coach');
        $showElderlyAlert = isset($registration) && ($registration->elderly_infirm_parking ?? false);
    @endphp

    @if(!($forPdf ?? false))
    <div class="no-print">
        <button type="button" onclick="window.print()">Print Pass</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>
    @endif

    <div class="pass-outer pass-outer--front">
        <article class="pass print-color-exact"
            style="--pass-park: {{ $carParkColor }}; --pass-park-dark: {{ $parkDark }}; --pass-park-text: {{ $heroTextColor }};">
            <div class="pass-spine print-color-exact"></div>

            <div class="pass-inner">
                <header class="pass-top print-color-exact">
                    <div class="pass-top-start">
                        @if($ticketLogo)
                            <img src="{{ ($forPdf ?? false) ? url(asset($ticketLogo)) : asset($ticketLogo) }}" alt="Logo" class="pass-top-logo">
                        @endif
                        <div>
                            <div class="pass-top-org">{{ __('print_pass.organization') }}</div>
                            <div class="pass-top-event">{{ $convLoc }} {{ $convYear }}</div>
                        </div>
                    </div>
                    <div class="pass-top-badge">{{ __('print_pass.car_park_pass') }}</div>
                </header>

                <section class="pass-zone print-color-exact"
                    style="background: linear-gradient(118deg, {{ $heroBackground }} 0%, {{ $parkDark }} 72%); color: {{ $heroTextColor }};">
                    <div class="pass-zone-kicker">{{ __('print_pass.parking_zone') }}</div>
                    <div class="pass-zone-name">{{ $zoneName }}</div>
                </section>

                <div class="pass-main">
                    <div class="pass-main-left">
                        @if($heroName)
                            @if($heroLabel)
                                <div class="pass-identity-label">{{ $heroLabel }}</div>
                            @endif
                            <div @class(['pass-identity-name', 'pass-identity-name--long' => $heroNameLong])>{{ $heroName }}</div>
                            @if($isCircuitOverseer)
                                <div class="pass-identity-registrant">{{ $registration->name }}</div>
                            @endif
                        @endif

                        @if(isset($registration))
                            <div class="pass-number print-color-exact">
                                <span class="pass-number-label">{{ __('print_pass.ticket_number') }}</span>
                                <span class="pass-number-value">{{ str_pad((string) $registration->id, 6, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        @endif

                        @if($showCoachAlert || $showElderlyAlert)
                            <div class="pass-alerts">
                                @if($showCoachAlert)
                                    <div class="pass-alert pass-alert--coach">{{ __('print_pass.ticket_for_coach_space') }}</div>
                                @endif
                                @if($showElderlyAlert)
                                    <div class="pass-alert pass-alert--elderly">{{ __('print_pass.ticket_for_elderly_infirm_space') }}</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($hasQr)
                        <aside class="pass-scan print-color-exact">
                            <div class="pass-scan-label">{{ __('print_pass.pass_id') }}</div>
                            <div class="pass-qr-frame">
                                @php
                                    $scanUrl = isset($registration)
                                        ? route('attendant.scan.ticket', $registration)
                                        : route('attendant.scan', ['code' => $congregation->uuid]);
                                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=2&data=' . urlencode($scanUrl);
                                @endphp
                                <img src="{{ $qrUrl }}" alt="QR Code" class="pass-qr" width="400" height="400">
                            </div>
                            @if(isset($registration))
                                <div class="pass-scan-id-label">{{ __('print_pass.ticket_number') }}</div>
                                <div class="pass-scan-id">{{ str_pad((string) $registration->id, 6, '0', STR_PAD_LEFT) }}</div>
                            @else
                                <div class="pass-scan-id-label">UUID</div>
                                <div class="pass-scan-id">{{ $congregation->uuid }}</div>
                            @endif
                        </aside>
                    @endif
                </div>

                <footer class="pass-ribbon print-color-exact"
                    style="background: linear-gradient(90deg, {{ $parkDark }} 0%, {{ $heroBackground }} 50%, {{ $parkDark }} 100%); color: {{ $heroTextColor }};">
                    <span class="pass-ribbon-text">{{ __('print_pass.display_on_dashboard') }}</span>
                </footer>

                @if(isset($registration))
                    <div class="pass-fine-print">
                        {{ __('print_pass.safety_notice') }}
                    </div>
                @endif
            </div>
        </article>
    </div>

    @php
        $mapImagePath = $carPark?->map_image_path;
        $mapImageSrc = $mapImagePath
            ? (($forPdf ?? false) ? url(asset($mapImagePath)) : asset($mapImagePath))
            : null;
    @endphp

    <div class="pass-back-outer">
        <article class="pass-back print-color-exact"
            style="--pass-park: {{ $carParkColor }}; --pass-park-dark: {{ $parkDark }}; --pass-park-text: {{ $heroTextColor }};">
            <header class="pass-back-header print-color-exact"
                style="background: linear-gradient(118deg, {{ $heroBackground }} 0%, {{ $parkDark }} 72%); color: {{ $heroTextColor }};">
                <div class="pass-back-heading">{{ __('print_pass.back_heading') }}</div>
                <div class="pass-back-subheading">{{ __('print_pass.organization') }} — {{ $convLoc }} {{ $convYear }}</div>
                <div class="pass-back-zone">{{ $zoneName }}</div>
            </header>

            <div class="pass-back-body">
                <div class="pass-back-map">
                    @if($mapImageSrc)
                        <img src="{{ $mapImageSrc }}" alt="{{ __('print_pass.back_heading') }} — {{ $zoneName }}">
                    @else
                        <div class="pass-back-map-placeholder">
                            <div>{{ __('print_pass.map_unavailable') }}</div>
                            @if($carPark?->location)
                                <div class="pass-back-map-location">{{ $carPark->location }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="pass-back-notes">
                    @if(isset($registration))
                        <div class="pass-back-contact">
                            <div class="pass-back-contact-row">
                                <span class="pass-back-contact-label">{{ __('print_pass.registrant_name') }}:</span>
                                <span class="pass-back-contact-value">{{ $registration->name }}</span>
                            </div>
                            @if($registration->contact_number)
                                <div class="pass-back-contact-row">
                                    <span class="pass-back-contact-label">{{ __('print_pass.contact_number') }}:</span>
                                    <span class="pass-back-contact-value">{{ $registration->contact_number }}</span>
                                </div>
                                <p class="pass-back-note">{{ __('print_pass.emergency_contact_note') }}</p>
                            @endif
                        </div>
                    @elseif(isset($congregation))
                        <p class="pass-back-note">{{ __('print_pass.congregation_pass_note') }}</p>
                    @endif

                    <p class="pass-back-note">{{ __('print_pass.footwear_note') }}</p>
                    <p class="pass-back-note">{{ __('print_pass.water_note') }}</p>
                </div>
            </div>

            <footer class="pass-back-footer print-color-exact">
                <div class="pass-back-scripture-ref">{{ __('print_pass.scripture_reference') }}</div>
                <div class="pass-back-scripture-text">{{ __('print_pass.scripture_text') }}</div>
                <div class="pass-back-closing">{{ __('print_pass.closing') }}</div>
            </footer>
        </article>
    </div>
</body>

</html>
