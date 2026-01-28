<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Pass - {{ $congregation->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    <div class="ticket-outer">
        <div class="ticket-container">
            @if($ticketLogo)
                <div class="mb-6">
                    <img src="{{ asset($ticketLogo) }}" alt="Logo" class="h-16 w-auto object-contain mx-auto">
                </div>
            @endif

            <div class="mb-8 text-center">
                <div class="text-zinc-500 uppercase font-bold tracking-[0.2em] text-xs mb-1">{{ $convName }}</div>
                <div class="convention-text text-zinc-900 uppercase tracking-tight">{{ $convLoc }} {{ $convYear }}</div>
            </div>

            <div class="w-full py-6 border-y-2 border-dashed border-zinc-900 mb-8"
                style="border-color: {{ $congregation->carPark->color ?? '#18181b' }}">
                <div class="text-zinc-400 uppercase font-bold tracking-[0.3em] text-[10px] mb-2">CONGREGATION</div>
                <h1 class="cong-name uppercase tracking-tighter"
                    style="color: {{ $congregation->carPark->color ?? '#4338ca' }}">
                    {{ $congregation->name }}
                </h1>
            </div>

            <div class="mb-6 p-4 bg-white border border-zinc-100 rounded-2xl inline-block mx-auto">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ route('attendant.scan', ['code' => $congregation->uuid]) }}"
                    alt="QR Code" class="h-64 w-64 object-contain">
            </div>

            <div class="text-[9px] text-zinc-400 font-mono mb-6 uppercase tracking-widest">
                ID: {{ $congregation->uuid }}
            </div>

            <div
                class="inline-block text-xl font-black text-white bg-zinc-900 px-8 py-4 rounded-xl uppercase tracking-tighter shadow-sm">
                DISPLAY ON DASHBOARD
            </div>
        </div>
    </div>
</body>

</html>