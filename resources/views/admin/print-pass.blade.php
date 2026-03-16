<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Pass - {{ $congregation->name }}{{ isset($registration) && $registration->car_park_id ? ' (Coach / Elderly & Infirm)' : '' }}</title>
    @unless($forPdf ?? false)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    @if($forPdf ?? false)
    <style>
        /* PDF-only: replicate Tailwind + keep entire ticket on one page */
        body { font-family: ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; margin: 0; padding: 0; }
        .ticket-outer { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 0.5cm; page-break-inside: avoid; }
        .ticket-container { width: 170mm; border: 4px solid #000; padding: 1cm; border-radius: 12mm; text-align: center; background: white; margin: 0 auto; page-break-inside: avoid; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .text-zinc-500 { color: #71717a; }
        .text-zinc-400 { color: #a1a1aa; }
        .text-zinc-900 { color: #18181b; }
        .text-xs { font-size: 0.75rem; line-height: 1rem; }
        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
        .text-\[9px\] { font-size: 9px; }
        .text-\[10px\] { font-size: 10px; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }
        .tracking-tight { letter-spacing: -0.025em; }
        .tracking-tighter { letter-spacing: -0.05em; }
        .tracking-\[0\.2em\] { letter-spacing: 0.2em; }
        .tracking-\[0\.3em\] { letter-spacing: 0.3em; }
        .tracking-widest { letter-spacing: 0.1em; }
        .w-full { width: 100%; }
        .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .p-4 { padding: 1rem; }
        .px-8 { padding-left: 2rem; padding-right: 2rem; }
        .border-y-2 { border-top-width: 2px; border-bottom-width: 2px; border-style: solid; }
        .border-dashed { border-style: dashed; }
        .border-2 { border-width: 2px; border-style: solid; }
        .border-zinc-900 { border-color: #18181b; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .inline-block { display: inline-block; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .text-center { text-align: center; }
        .object-contain { object-fit: contain; }
        .h-16 { height: 4rem; }
        .w-auto { width: auto; }
        .h-64 { height: 16rem; }
        .w-64 { width: 16rem; }
        .bg-white { background-color: #fff; }
        .border-zinc-100 { border-color: #f4f4f5; }
        .border-amber-500 { border-color: #f59e0b; }
        .bg-amber-50 { background-color: #fffbeb; }
        .text-amber-800 { color: #92400e; }
        .text-amber-900 { color: #78350f; }
        .text-amber-100 { color: #fef3c7; }
        .text-white { color: #fff; }
        .bg-zinc-900 { background-color: #18181b; }
        .font-mono { font-family: ui-monospace, monospace; }
        .shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
    </style>
    @endif
    <style>
        @media screen {
            body {
                background-color: #f4f4f5;
                padding: 40px 20px;
            }

            .ticket-container {
                background: white;
                margin: 0 auto;
                width: 100%;
                max-width: 500px;
                border: 2px solid #18181b;
                border-radius: 24px;
                padding: 40px;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            }
        }

        @media print {
            @page {
                margin: 0;
                size: portrait;
            }

            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0;
                padding: 0;
            }

            .ticket-outer {
                width: 100%;
                height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                padding-top: 2cm;
            }

            .ticket-container {
                width: 170mm;
                border: 4px solid #000;
                padding: 1.5cm;
                border-radius: 12mm;
                text-align: center;
            }
        }

        .cong-name {
            font-size: 3.5rem;
            line-height: 1.1;
            margin: 0.2rem 0;
            font-weight: 900;
            word-break: break-word;
        }

        .convention-text {
            font-size: 1.25rem;
            line-height: 1.2;
            font-weight: 700;
        }
    </style>
</head>

<body class="antialiased font-sans">
    @php
        $convName = \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness");
        $convYear = \App\Models\Setting::get('convention_year', date('Y'));
        $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
        $ticketLogo = \App\Models\Setting::get('ticket_logo');
    @endphp

    @if(!($forPdf ?? false))
    <div class="no-print mb-8 flex justify-center gap-3">
        <button onclick="window.print()"
            class="bg-zinc-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition">
            Print Master Pass
        </button>
        <button onclick="window.close()"
            class="bg-zinc-100 text-zinc-600 px-8 py-3 rounded-xl font-bold hover:bg-zinc-200 transition">
            Close
        </button>
    </div>
    @endif

    <div class="ticket-outer">
        <div class="ticket-container">
            @if($ticketLogo)
                <div class="mb-6">
                    <img src="{{ ($forPdf ?? false) ? url(asset($ticketLogo)) : asset($ticketLogo) }}" alt="Logo" class="h-16 w-auto object-contain mx-auto">
                </div>
            @endif

            <div class="mb-8 text-center">
                <div class="text-zinc-500 uppercase font-bold tracking-[0.2em] text-xs mb-1">{{ $convName }}</div>
                <div class="convention-text text-zinc-900 uppercase tracking-tight">{{ $convLoc }} {{ $convYear }}</div>
            </div>

            <div class="w-full py-6 border-y-2 border-dashed border-zinc-900 mb-6"
                style="border-color: {{ $congregation->carPark?->color ?? '#18181b' }}">
                <div class="text-zinc-400 uppercase font-bold tracking-[0.3em] text-[10px] mb-2">{{ __('print_pass.congregation') }}</div>
                <h1 class="cong-name uppercase tracking-tighter"
                    style="color: {{ $congregation->carPark?->color ?? '#4338ca' }}">
                    {{ $congregation->name }}
                </h1>
            </div>

            @if(isset($registration) && $registration->car_park_id && $registration->carPark)
            <div class="w-full py-4 px-4 mb-6 rounded-2xl border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-600">
                @if(($registration->vehicle_type ?? 'car') === 'coach')
                <div class="text-amber-800 dark:text-amber-200 font-bold text-sm uppercase tracking-wide mb-1">{{ __('print_pass.ticket_for_coach_space') }}</div>
                @endif
                @if($registration->elderly_infirm_parking ?? false)
                <div class="text-amber-800 dark:text-amber-200 font-bold text-sm uppercase tracking-wide mb-1">{{ __('print_pass.ticket_for_elderly_infirm_space') }}</div>
                @endif
                <div class="text-xl font-black text-amber-900 dark:text-amber-100 mt-2" style="color: {{ $registration->carPark->color ?? '#b45309' }}">{{ $registration->carPark->name }}</div>
            </div>
            @endif

            <div class="mb-6 p-4 bg-white border border-zinc-100 rounded-2xl inline-block mx-auto">
                @php
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode(route('attendant.scan', ['code' => $congregation->uuid]));
                @endphp
                <img src="{{ $qrUrl }}" alt="QR Code" class="h-64 w-64 object-contain" width="250" height="250">
            </div>

            <div class="text-[9px] text-zinc-400 font-mono mb-6 uppercase tracking-widest">
                {{ __('print_pass.pass_id') }}: {{ $congregation->uuid }}
            </div>

            <div
                class="inline-block text-xl font-black text-white bg-zinc-900 px-8 py-4 rounded-xl uppercase tracking-tighter shadow-sm">
                {{ __('print_pass.display_on_dashboard') }}
            </div>
        </div>
    </div>
</body>

</html>