<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('parking_qr.guest_print_title') }}</title>
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
            height: 38mm;
            overflow: hidden;
            background: #312e81;
        }
        .hero-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 45%;
            display: block;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(15, 23, 42, 0.72) 0%, rgba(15, 23, 42, 0.35) 55%, rgba(15, 23, 42, 0.15) 100%);
        }
        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 4mm;
            padding: 4mm 5mm 0;
            color: #fff;
        }
        .hero-brand {
            display: flex;
            align-items: center;
            gap: 3mm;
            min-width: 0;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .handout-event {
            font-size: 3.8mm;
            font-weight: 900;
            line-height: 1.1;
            text-shadow: 0 1px 2px rgba(0,0,0,.35);
        }
        .hero-title-block { text-align: right; flex-shrink: 0; }
        .handout-title {
            margin: 0;
            font-size: 6.2mm;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.02em;
            text-shadow: 0 1px 3px rgba(0,0,0,.4);
        }
        .handout-park {
            display: inline-block;
            margin-top: 1.8mm;
            padding: 1.2mm 3.2mm;
            border-radius: 999px;
            background: rgba(255,255,255,0.92);
            color: #0f766e;
            font-size: 2.7mm;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .hero-bottom {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            padding: 0 5mm 3mm;
        }
        .hero-welcome {
            margin: 0;
            font-size: 2.8mm;
            color: #f8fafc;
            line-height: 1.3;
            text-shadow: 0 1px 2px rgba(0,0,0,.45);
        }

        .map-stage {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 3.5mm 5mm 0;
        }
        .panel-label {
            flex: 0 0 auto;
            font-size: 2.3mm;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #0f766e;
            margin-bottom: 1.5mm;
        }
        .map-frame {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d6d3d1;
            border-radius: 2.5mm;
            background: #fafaf9;
            overflow: hidden;
            padding: 1.5mm;
        }
        .map-image {
            display: block;
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            object-position: center;
            image-rendering: -webkit-optimize-contrast;
        }
        .map-placeholder {
            width: 100%;
            height: 100%;
            min-height: 80mm;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 5mm;
            border: 1.5px dashed #f59e0b;
            border-radius: 2mm;
            background: #fffbeb;
            color: #92400e;
            font-size: 3.2mm;
            font-weight: 600;
            line-height: 1.4;
        }

        .handout-footer {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: 36mm 1fr;
            gap: 4mm;
            align-items: start;
            margin-top: 3mm;
            padding: 3mm 5mm 3.5mm;
            border-top: 1px solid #99f6e4;
            background: #f8fafc;
        }
        .nav-block { text-align: center; }
        .nav-qr {
            width: 30mm;
            height: 30mm;
            margin: 0 auto 1.2mm;
            display: block;
            background: #fff;
            border: 1px solid #d6d3d1;
            border-radius: 1.5mm;
            padding: 1mm;
        }
        .nav-label {
            font-size: 2.3mm;
            font-weight: 900;
            color: #0f766e;
            line-height: 1.15;
        }
        .nav-placeholder {
            width: 30mm;
            height: 30mm;
            margin: 0 auto 1.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5mm;
            border: 1.5px dashed #f59e0b;
            border-radius: 1.5mm;
            background: #fffbeb;
            color: #92400e;
            font-size: 2mm;
            font-weight: 600;
            line-height: 1.25;
        }
        .links-block { min-width: 0; }
        .links-intro {
            margin: 0 0 2mm;
            font-size: 2.8mm;
            color: #44403c;
            line-height: 1.35;
        }
        .link-rows {
            display: grid;
            gap: 1.6mm;
        }
        .link-row {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 2mm;
            align-items: baseline;
        }
        .link-label {
            font-size: 2.3mm;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0f766e;
        }
        .link-url {
            font-size: 2.4mm;
            font-weight: 600;
            color: #1e293b;
            word-break: break-all;
            line-height: 1.25;
        }
        .meta-note {
            margin-top: 2mm;
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
            .map-frame { min-height: 50vh; }
        }
    </style>
</head>
<body>
    @php
        /** @var \App\Models\CarPark $carPark */
        $convName = \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness");
        $convYear = \App\Models\Setting::get('convention_year', date('Y'));
        $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
        $ticketLogo = \App\Models\Setting::get('ticket_logo');
        $programUrl = 'https://www.jw.org/en/library/programs/2026-convention-program/';
        $faqUrl = 'https://www.jw.org/en/jehovahs-witnesses/faq/';
        $visitUrl = 'https://hub.jw.org/request-visit/en/request';
        $heroSrc = asset('images/guest-handout-hero.png');
        $navUrl = $carPark->navigationUrl();
        $mapSrc = $carPark->map_image_path ? asset($carPark->map_image_path) : null;
        $qrUrl = $navUrl
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=1&data='.urlencode($navUrl)
            : null;
    @endphp

    <div class="no-print">
        <button type="button" onclick="window.print()">{{ __('parking_qr.guest_print_button') }}</button>
        <button type="button" onclick="window.close()">{{ __('parking_qr.close_button') }}</button>
    </div>

    <div class="sheet">
        <div class="handout">
            <header class="hero">
                <img src="{{ $heroSrc }}" alt="" class="hero-image">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="hero-brand">
                        @if($ticketLogo)
                            <img src="{{ asset($ticketLogo) }}" alt="Logo" class="handout-logo">
                        @endif
                        <div>
                            <div class="handout-org">{{ $convName }}</div>
                            <div class="handout-event">{{ $convLoc }} {{ $convYear }}</div>
                        </div>
                    </div>
                    <div class="hero-title-block">
                        <h1 class="handout-title">{{ __('parking_qr.guest_label') }}</h1>
                        <div class="handout-park">{{ $carPark->name }}</div>
                    </div>
                </div>
                <div class="hero-bottom">
                    <p class="hero-welcome">{{ __('parking_qr.guest_welcome') }}</p>
                </div>
            </header>

            <section class="map-stage">
                <div class="panel-label">{{ __('parking_qr.guest_map_heading') }}</div>
                <div class="map-frame">
                    @if($mapSrc)
                        <img
                            src="{{ $mapSrc }}"
                            alt="{{ __('parking_qr.guest_map_alt', ['park' => $carPark->name]) }}"
                            class="map-image"
                        >
                    @else
                        <div class="map-placeholder">{{ __('parking_qr.guest_map_missing_print') }}</div>
                    @endif
                </div>
            </section>

            <footer class="handout-footer">
                <div class="nav-block">
                    @if($qrUrl && $navUrl)
                        <img src="{{ $qrUrl }}" alt="{{ __('parking_qr.guest_nav_qr_alt') }}" class="nav-qr" width="500" height="500">
                        <div class="nav-label">{{ __('parking_qr.guest_nav_label') }}</div>
                    @else
                        <div class="nav-placeholder">{{ __('parking_qr.guest_coords_missing_print') }}</div>
                        <div class="nav-label">{{ __('parking_qr.guest_nav_label') }}</div>
                    @endif
                </div>
                <div class="links-block">
                    <p class="links-intro">{{ __('parking_qr.guest_links_intro') }}</p>
                    <div class="link-rows">
                        <div class="link-row">
                            <div class="link-label">{{ __('parking_qr.guest_link_program') }}</div>
                            <div class="link-url">{{ $programUrl }}</div>
                        </div>
                        <div class="link-row">
                            <div class="link-label">{{ __('parking_qr.guest_link_faq') }}</div>
                            <div class="link-url">{{ $faqUrl }}</div>
                        </div>
                        <div class="link-row">
                            <div class="link-label">{{ __('parking_qr.guest_link_visit') }}</div>
                            <div class="link-url">{{ $visitUrl }}</div>
                        </div>
                    </div>
                    <div class="meta-note">{{ __('parking_qr.guest_footer') }}</div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
