<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
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
            margin: 0;
            padding: 0;
            overflow: hidden;
            page-break-inside: avoid;
            page-break-after: always;
            background: #0f172a;
            position: relative;
        }

        .slide.last {
            page-break-after: auto;
        }

        .cover-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 960pt;
            height: 540pt;
        }

        .cover-copy {
            position: absolute;
            top: 0;
            left: 0;
            width: 960pt;
            height: 540pt;
        }

        .cover-copy-table {
            width: 960pt;
            height: 540pt;
            border-collapse: collapse;
        }

        .cover-copy-table td {
            vertical-align: middle;
            text-align: center;
            padding: 48pt 64pt;
        }

        .cover-kicker {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 2pt;
            text-transform: uppercase;
            color: #5eead4;
            margin: 0 0 12pt;
        }

        .cover-title {
            font-size: 36pt;
            font-weight: bold;
            line-height: 1.15;
            color: #ffffff;
            margin: 0;
        }

        .cover-body {
            margin: 16pt 0 0;
            font-size: 15pt;
            line-height: 1.35;
            color: #ccfbf1;
        }

        .content-inner {
            padding: 32pt 44pt 28pt;
        }

        .chip {
            display: inline-block;
            margin: 0 0 12pt;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            color: #5eead4;
        }

        .content-title {
            margin: 0 0 14pt;
            font-size: 24pt;
            font-weight: bold;
            line-height: 1.2;
            color: #ffffff;
        }

        .intro {
            margin: 0 0 10pt;
            font-size: 14pt;
            line-height: 1.35;
            color: #f8fafc;
        }

        ul.bullets {
            margin: 0;
            padding: 0 0 0 20pt;
        }

        ul.bullets li {
            margin: 0 0 7pt;
            font-size: 13pt;
            line-height: 1.35;
            color: #e2e8f0;
        }

        /* Classic dense JHA briefing slides (match source hazard decks). */
        .slide.jha-content {
            background: #fffbeb;
            color: #1c1917;
        }

        .slide.jha-content .content-inner {
            padding: 16pt 28pt 14pt;
        }

        .slide.jha-content .chip {
            margin: 0 0 6pt;
            font-size: 9pt;
            letter-spacing: 1pt;
            color: #92400e;
        }

        .slide.jha-content .content-title {
            margin: 0 0 6pt;
            font-size: 14pt;
            line-height: 1.15;
            color: #1c1917;
        }

        .slide.jha-content .intro {
            margin: 0 0 3pt;
            font-size: 10pt;
            line-height: 1.2;
            color: #1c1917;
        }

        .slide.jha-content ul.bullets {
            padding: 0 0 0 12pt;
        }

        .slide.jha-content ul.bullets li {
            margin: 0 0 2pt;
            font-size: 10pt;
            line-height: 1.2;
            color: #292524;
        }
    </style>
</head>
<body>
@foreach($slides as $index => $slide)
    @php
        $isCover = in_array($slide['type'], ['cover', 'title'], true);
        $bg = $slide['background'] ?? null;
        $isLast = $index === array_key_last($slides);
        $isJhaContent = ! empty($slide['is_jha_content']);
    @endphp
    <div class="slide{{ $isJhaContent ? ' jha-content' : '' }}{{ $isLast ? ' last' : '' }}">
        @if($isCover)
            @if($bg)
                <img class="cover-bg" src="{{ $bg }}" alt="">
            @endif
            <div class="cover-copy">
                <table class="cover-copy-table">
                    <tr>
                        <td>
                        <div class="cover-kicker">
                            @if($slide['type'] === 'title')
                                {{ $slide['section_label'] }}
                            @elseif(! empty($slide['is_jha_cover']))
                                {{ __('toolbox_talks.jha_cover_kicker') }}
                            @else
                                {{ __('toolbox_talks.park_cover_kicker') }}
                            @endif
                        </div>
                            <h1 class="cover-title">{{ $slide['title'] }}</h1>
                            @if(($slide['body'] ?? '') !== '')
                                <p class="cover-body">{{ $slide['body'] }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
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
