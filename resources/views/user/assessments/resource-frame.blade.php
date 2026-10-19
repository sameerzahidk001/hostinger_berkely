<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name ?? 'File' }}</title>
    <style>
        html, body { margin: 0; height: 100%; background: #111; }
        .wrap { width: 100%; height: 100%; }
        iframe, embed, object, img { width: 100%; height: 100%; border: 0; display: block; background: #111; }
        img { object-fit: contain; background: #000; }
    </style>
</head>
<body>
    <div class="wrap">
        @if(($kind ?? '') === 'image')
            <img src="{{ $rawUrl }}" alt="{{ $name ?? 'File' }}" draggable="false">
        @elseif(($kind ?? '') === 'office' && !empty($officeEmbed))
            <iframe src="{{ $officeEmbed }}" title="{{ $name ?? 'File' }}" allow="fullscreen"></iframe>
        @else
            <embed src="{{ $rawUrl }}" type="{{ $mime ?? 'application/pdf' }}" title="{{ $name ?? 'File' }}">
        @endif
    </div>
</body>
</html>
