<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
            size: 960pt 540pt;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #f8fafc;
            background: #0f172a;
        }

        .slide {
            width: 960pt;
            height: 540pt;
            page-break-after: always;
            position: relative;
            overflow: hidden;
            background: #0f172a;
        }

        .slide:last-child {
            page-break-after: auto;
        }

        .slide-cover {
            background-color: #0f172a;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
        }

        .cover-scrim {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.78));
        }

        .cover-inner {
            position: relative;
            z-index: 1;
            height: 100%;
            padding: 56pt 64pt;
            text-align: center;
        }

        .cover-kicker {
            margin-top: 90pt;
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #5eead4;
        }

        .cover-title {
            margin: 18pt 0 0;
            font-size: 36pt;
            font-weight: bold;
            line-height: 1.15;
            color: #ffffff;
        }

        .cover-body {
            margin: 18pt auto 0;
            max-width: 720pt;
            font-size: 16pt;
            line-height: 1.4;
            color: #ccfbf1;
        }

        .content-inner {
            padding: 36pt 48pt 32pt;
            height: 100%;
        }

        .chip {
            display: inline-block;
            margin: 0 0 14pt;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #5eead4;
        }

        .content-title {
            margin: 0 0 16pt;
            font-size: 26pt;
            font-weight: bold;
            line-height: 1.2;
            color: #ffffff;
        }

        .intro {
            margin: 0 0 12pt;
            font-size: 15pt;
            line-height: 1.35;
            color: #f8fafc;
        }

        ul.bullets {
            margin: 0;
            padding: 0 0 0 22pt;
        }

        ul.bullets li {
            margin: 0 0 8pt;
            font-size: 14pt;
            line-height: 1.35;
            color: #e2e8f0;
        }
    </style>
</head>
<body>
@foreach($slides as $slide)
    @php
        $isCover = in_array($slide['type'], ['cover', 'title'], true);
        $bg = $slide['background'] ?? null;
    @endphp
    <div class="slide {{ $isCover ? 'slide-cover' : '' }}" @if($isCover && $bg) style="background-image: url('{{ $bg }}');" @endif>
        @if($isCover)
            <div class="cover-scrim"></div>
            <div class="cover-inner">
                <div class="cover-kicker">
                    {{ $slide['type'] === 'title' ? $slide['section_label'] : __('toolbox_talks.park_cover_kicker') }}
                </div>
                <h1 class="cover-title">{{ $slide['title'] }}</h1>
                @if(($slide['body'] ?? '') !== '')
                    <p class="cover-body">{{ $slide['body'] }}</p>
                @endif
            </div>
        @else
            <div class="content-inner">
                @if(($slide['section_label'] ?? '') !== '')
                    <div class="chip">{{ $slide['section_label'] }}</div>
                @endif
                <h1 class="content-title">{{ $slide['title'] }}</h1>
                @if(($slide['lines'] ?? []) !== [])
                    @php $openList = false; @endphp
                    @foreach($slide['lines'] as $line)
                        @if($line['bullet'])
                            @if(! $openList)
                                <ul class="bullets">
                                @php $openList = true; @endphp
                            @endif
                            <li>{{ $line['text'] }}</li>
                        @else
                            @if($openList)
                                </ul>
                                @php $openList = false; @endphp
                            @endif
                            <p class="intro">{{ $line['text'] }}</p>
                        @endif
                    @endforeach
                    @if($openList)
                        </ul>
                    @endif
                @elseif(($slide['body'] ?? '') !== '')
                    <p class="intro">{{ $slide['body'] }}</p>
                @endif
            </div>
        @endif
    </div>
@endforeach
</body>
</html>
