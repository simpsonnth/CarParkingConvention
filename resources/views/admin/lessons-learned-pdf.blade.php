<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 28pt 32pt;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #18181b;
            line-height: 1.45;
        }

        h1 {
            margin: 0 0 4pt;
            font-size: 18pt;
            color: #18181b;
        }

        .meta {
            margin: 0 0 18pt;
            font-size: 9pt;
            color: #71717a;
        }

        .lesson {
            border: 1pt solid #e4e4e7;
            border-radius: 6pt;
            padding: 12pt 14pt;
            margin: 0 0 14pt;
            page-break-inside: avoid;
        }

        .lesson-header {
            margin: 0 0 8pt;
            padding-bottom: 8pt;
            border-bottom: 1pt solid #f4f4f5;
        }

        .lesson-title {
            margin: 0 0 4pt;
            font-size: 12pt;
            font-weight: bold;
        }

        .badges {
            margin: 0;
            font-size: 8.5pt;
            color: #52525b;
        }

        .badge {
            display: inline-block;
            background: #f4f4f5;
            border-radius: 3pt;
            padding: 1pt 6pt;
            margin-right: 4pt;
        }

        .section {
            margin: 8pt 0 0;
        }

        .section-label {
            margin: 0 0 2pt;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            color: #71717a;
        }

        .section-body {
            margin: 0;
            white-space: pre-wrap;
        }

        .empty {
            color: #a1a1aa;
            font-style: italic;
        }

        .images {
            margin-top: 6pt;
        }

        .image-item {
            display: inline-block;
            vertical-align: top;
            width: 160pt;
            margin: 0 8pt 8pt 0;
            text-align: center;
        }

        .image-item img {
            max-width: 160pt;
            max-height: 110pt;
            border: 1pt solid #e4e4e7;
            border-radius: 4pt;
        }

        .image-caption {
            margin-top: 3pt;
            font-size: 8pt;
            color: #52525b;
            word-wrap: break-word;
        }

        .links {
            margin: 4pt 0 0;
            padding: 0;
            list-style: none;
        }

        .links li {
            margin: 0 0 3pt;
        }

        a {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .none {
            margin-top: 24pt;
            text-align: center;
            color: #71717a;
        }
    </style>
</head>
<body>
    <h1>Lessons learned</h1>
    <p class="meta">Exported {{ $exportedAt }} · {{ $total }} lesson(s)</p>

    @forelse($lessons as $lesson)
        <div class="lesson">
            <div class="lesson-header">
                <div class="lesson-title">
                    {{ $lesson['title'] !== '' ? $lesson['title'] : 'Untitled lesson' }}
                </div>
                <div class="badges">
                    <span class="badge">{{ $lesson['submitted'] }}</span>
                    <span class="badge">{{ $lesson['source'] }}</span>
                    <span class="badge">{{ $lesson['category'] }}</span>
                    <span class="badge">{{ $lesson['day'] }}</span>
                    <span class="badge">{{ $lesson['reporter'] }}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-label">What worked well</div>
                @if($lesson['worked_well'] !== '')
                    <div class="section-body">{{ $lesson['worked_well'] }}</div>
                @else
                    <div class="section-body empty">—</div>
                @endif
            </div>

            <div class="section">
                <div class="section-label">What did not work well</div>
                @if($lesson['didnt_work_well'] !== '')
                    <div class="section-body">{{ $lesson['didnt_work_well'] }}</div>
                @else
                    <div class="section-body empty">—</div>
                @endif
            </div>

            @if($lesson['images'] !== [])
                <div class="section">
                    <div class="section-label">Images</div>
                    <div class="images">
                        @foreach($lesson['images'] as $image)
                            <div class="image-item">
                                @if($image['data_uri'])
                                    <a href="{{ $image['url'] }}">
                                        <img src="{{ $image['data_uri'] }}" alt="{{ $image['name'] }}">
                                    </a>
                                @else
                                    <a href="{{ $image['url'] }}">{{ $image['name'] }}</a>
                                @endif
                                <div class="image-caption">{{ $image['name'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($lesson['voice_notes'] !== [])
                <div class="section">
                    <div class="section-label">Voice notes</div>
                    <ul class="links">
                        @foreach($lesson['voice_notes'] as $voice)
                            <li>
                                <a href="{{ $voice['url'] }}">Listen: {{ $voice['name'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($lesson['other_files'] !== [])
                <div class="section">
                    <div class="section-label">Other files</div>
                    <ul class="links">
                        @foreach($lesson['other_files'] as $file)
                            <li>
                                <a href="{{ $file['url'] }}">{{ $file['name'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @empty
        <p class="none">No lessons learned on record.</p>
    @endforelse
</body>
</html>
