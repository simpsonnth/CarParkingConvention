<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('parking_qr.print_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
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
        .no-print button:first-child { background: #18181b; color: #fff; }
        .no-print button:last-child { background: #f4f4f5; color: #52525b; }
        .poster {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12mm;
            background: #fff;
        }
        .poster-inner {
            width: 100%;
            max-width: 160mm;
            text-align: center;
            border: 3px solid #312e81;
            border-radius: 6mm;
            padding: 10mm;
        }
        .poster-logo { height: 14mm; width: auto; margin: 0 auto 4mm; display: block; }
        .poster-org { font-size: 3mm; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #78716c; }
        .poster-event { font-size: 7mm; font-weight: 900; color: #18181b; margin: 2mm 0 6mm; }
        .poster-title { font-size: 8mm; font-weight: 900; color: #312e81; margin-bottom: 3mm; line-height: 1.1; }
        .poster-subtitle { font-size: 4mm; color: #57534e; margin-bottom: 8mm; }
        .poster-qr { width: 80mm; height: 80mm; margin: 0 auto 6mm; display: block; }
        .poster-instructions { font-size: 3.5mm; color: #44403c; line-height: 1.5; margin-bottom: 4mm; }
        .poster-footer { font-size: 3mm; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #312e81; border-top: 1px dashed #d6d3d1; padding-top: 4mm; }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            .no-print { display: none !important; }
            .poster { min-height: auto; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $convName = \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness");
        $convYear = \App\Models\Setting::get('convention_year', date('Y'));
        $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
        $ticketLogo = \App\Models\Setting::get('ticket_logo');
        $walkInScanUrl = route('attendant.scan.walk-in');
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($walkInScanUrl);
    @endphp

    <div class="no-print">
        <button type="button" onclick="window.print()">{{ __('parking_qr.print_button') }}</button>
        <button type="button" onclick="window.close()">{{ __('parking_qr.close_button') }}</button>
    </div>

    <div class="poster">
        <div class="poster-inner">
            @if($ticketLogo)
                <img src="{{ asset($ticketLogo) }}" alt="Logo" class="poster-logo">
            @endif
            <div class="poster-org">{{ $convName }}</div>
            <div class="poster-event">{{ $convLoc }} {{ $convYear }}</div>
            <div class="poster-title">{{ __('parking_qr.poster_title') }}</div>
            <div class="poster-subtitle">{{ __('parking_qr.poster_subtitle') }}</div>
            <img src="{{ $qrUrl }}" alt="{{ __('parking_qr.walk_in_qr_alt') }}" class="poster-qr" width="500" height="500">
            <p class="poster-instructions">{{ __('parking_qr.poster_instructions') }}</p>
            <div class="poster-footer">{{ __('parking_qr.poster_footer') }}</div>
        </div>
    </div>
</body>
</html>
