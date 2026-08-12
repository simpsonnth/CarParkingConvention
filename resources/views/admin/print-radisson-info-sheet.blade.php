<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('parking_qr.radisson_print_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #18181b;
            background: #fff;
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
            background: rgb(255 255 255 / 0.95);
            border-bottom: 1px solid #e4e4e7;
        }
        .no-print button {
            border: none;
            padding: 0.65rem 1.5rem;
            border-radius: 0.65rem;
            font-weight: 700;
            cursor: pointer;
        }
        .no-print button:first-child { background: #18181b; color: #fff; }
        .no-print button:last-child { background: #f4f4f5; color: #52525b; }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 12mm auto;
            padding: 6mm;
            background: #fff;
        }
        .handout {
            width: 100%;
            height: 285mm;
            display: flex;
            flex-direction: column;
            border: 2px solid #0f766e;
            border-radius: 3.5mm;
            overflow: hidden;
            background: #fff;
        }

        .hero {
            flex: 0 0 auto;
            position: relative;
            height: 58mm;
            overflow: hidden;
            background: #312e81;
        }
        .hero-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 40%;
            display: block;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(15, 23, 42, 0.78) 0%, rgba(15, 23, 42, 0.4) 55%, rgba(15, 23, 42, 0.2) 100%);
        }
        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            height: 100%;
            padding: 5mm 6mm 5mm;
            color: #fff;
        }
        .hero-brand {
            display: flex;
            align-items: center;
            gap: 3mm;
            margin-bottom: auto;
        }
        .handout-logo {
            height: 10mm;
            width: auto;
            display: block;
            flex-shrink: 0;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
        }
        .handout-org {
            font-size: 2.3mm;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.9;
        }
        .welcome-kicker {
            margin: 0 0 1.5mm;
            font-size: 2.8mm;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #99f6e4;
        }
        .welcome-title {
            margin: 0;
            font-size: 7.2mm;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.02em;
            text-shadow: 0 1px 3px rgba(0,0,0,.4);
        }
        .welcome-sub {
            margin: 2.5mm 0 0;
            max-width: 150mm;
            font-size: 3.4mm;
            font-weight: 600;
            line-height: 1.35;
            color: #f8fafc;
            text-shadow: 0 1px 2px rgba(0,0,0,.45);
        }

        .body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 6mm 7mm 0;
        }
        .intro {
            margin: 0 0 5mm;
            font-size: 3.6mm;
            line-height: 1.45;
            color: #334155;
        }
        .steps {
            display: grid;
            gap: 3mm;
            margin-bottom: 5mm;
        }
        .step {
            display: grid;
            grid-template-columns: 9mm 1fr;
            gap: 3mm;
            align-items: start;
            padding: 3mm;
            border: 1px solid #ccfbf1;
            border-radius: 2.5mm;
            background: #f0fdfa;
        }
        .step-num {
            width: 9mm;
            height: 9mm;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #0f766e;
            color: #fff;
            font-size: 3.6mm;
            font-weight: 900;
        }
        .step-title {
            margin: 0;
            font-size: 3.4mm;
            font-weight: 800;
            color: #0f766e;
        }
        .step-copy {
            margin: 1mm 0 0;
            font-size: 3mm;
            line-height: 1.35;
            color: #475569;
        }

        .qr-panel {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4mm;
            border: 1.5px solid #99f6e4;
            border-radius: 3mm;
            background: linear-gradient(180deg, #f8fafc 0%, #f0fdfa 100%);
        }
        .qr-label {
            margin: 0 0 3mm;
            font-size: 2.6mm;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #0f766e;
        }
        .qr-image {
            width: 55mm;
            height: 55mm;
            display: block;
            background: #fff;
            border: 1px solid #d6d3d1;
            border-radius: 2mm;
            padding: 2mm;
        }
        .qr-hint {
            margin: 3mm 0 0;
            max-width: 120mm;
            font-size: 3.1mm;
            font-weight: 600;
            line-height: 1.35;
            color: #334155;
        }
        .qr-url {
            margin: 2mm 0 0;
            font-size: 2.3mm;
            font-weight: 600;
            color: #64748b;
            word-break: break-all;
        }

        .footer {
            flex: 0 0 auto;
            margin-top: 4mm;
            padding: 4mm 7mm 5mm;
            border-top: 1px solid #99f6e4;
            background: #f8fafc;
        }
        .footer-note {
            margin: 0;
            font-size: 3mm;
            line-height: 1.4;
            color: #475569;
        }
        .footer-url {
            margin: 1.5mm 0 0;
            font-size: 2.6mm;
            font-weight: 700;
            color: #0f766e;
            word-break: break-all;
        }
        .meta-note {
            margin-top: 2.5mm;
            font-size: 2.1mm;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                overflow: hidden;
            }
            .no-print { display: none !important; }
            .sheet {
                width: 210mm;
                height: 297mm;
                min-height: 297mm;
                margin: 0;
                padding: 6mm;
            }
            .handout {
                height: 285mm;
                max-height: 285mm;
            }
        }

        @media screen and (max-width: 900px) {
            .sheet {
                width: auto;
                min-height: auto;
                margin: 4mm;
                padding: 0;
            }
            .handout { height: auto; min-height: 90vh; }
        }
    </style>
</head>
<body>
    @php
        $convName = \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness");
        $convYear = \App\Models\Setting::get('convention_year', date('Y'));
        $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
        $ticketLogo = \App\Models\Setting::get('ticket_logo');
        $heroSrc = asset('images/guest-handout-hero.png');
        $checkUrl = route('management.radisson-parking-check', absolute: true);
        $requestUrl = route('management.radisson-guest-parking', absolute: true);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=1&data='.urlencode($checkUrl);
    @endphp

    <div class="no-print">
        <button type="button" onclick="window.print()">{{ __('parking_qr.print_button') }}</button>
        <button type="button" onclick="window.close()">{{ __('parking_qr.close_button') }}</button>
    </div>

    <div class="sheet">
        <div class="handout">
            <div class="hero">
                <img class="hero-image" src="{{ $heroSrc }}" alt="">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="hero-brand">
                        @if($ticketLogo)
                            <img class="handout-logo" src="{{ asset($ticketLogo) }}" alt="">
                        @endif
                        <div class="handout-org">{{ $convName }} · {{ $convLoc }} {{ $convYear }}</div>
                    </div>
                    <p class="welcome-kicker">{{ __('parking_qr.radisson_welcome_kicker') }}</p>
                    <h1 class="welcome-title">{{ __('parking_qr.radisson_welcome_title') }}</h1>
                    <p class="welcome-sub">{{ __('parking_qr.radisson_welcome_body') }}</p>
                </div>
            </div>

            <div class="body">
                <p class="intro">{{ __('parking_qr.radisson_intro') }}</p>

                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div>
                            <p class="step-title">{{ __('parking_qr.radisson_step1_title') }}</p>
                            <p class="step-copy">{{ __('parking_qr.radisson_step1_body') }}</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div>
                            <p class="step-title">{{ __('parking_qr.radisson_step2_title') }}</p>
                            <p class="step-copy">{{ __('parking_qr.radisson_step2_body') }}</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div>
                            <p class="step-title">{{ __('parking_qr.radisson_step3_title') }}</p>
                            <p class="step-copy">{{ __('parking_qr.radisson_step3_body') }}</p>
                        </div>
                    </div>
                </div>

                <div class="qr-panel">
                    <p class="qr-label">{{ __('parking_qr.radisson_qr_label') }}</p>
                    <img class="qr-image" src="{{ $qrUrl }}" alt="{{ __('parking_qr.radisson_qr_alt') }}">
                    <p class="qr-hint">{{ __('parking_qr.radisson_qr_hint') }}</p>
                    <p class="qr-url">{{ $checkUrl }}</p>
                </div>
            </div>

            <div class="footer">
                <p class="footer-note">{{ __('parking_qr.radisson_no_ticket_note') }}</p>
                <p class="footer-url">{{ $requestUrl }}</p>
                <p class="meta-note">{{ $convLoc }} {{ $convYear }} · {{ __('parking_qr.radisson_footer') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
