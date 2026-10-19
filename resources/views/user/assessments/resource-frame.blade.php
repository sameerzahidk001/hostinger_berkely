<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $name ?? 'File' }}</title>
    <style>
        html, body { margin: 0; height: 100%; background: #fff; font-family: Arial, sans-serif; }
        .bar { padding: 10px 14px; background: #0d3b66; color: #fff; font-size: 13px; }
        .bar a { color: #f0ad4e; font-weight: 700; }
        iframe { width: 100%; height: calc(100% - 42px); border: 0; }
    </style>
</head>
<body>
    <div class="bar">
        {{ $name ?? 'File' }}
        —
        <a href="{{ $openUrl }}" target="_blank" rel="noopener">Open file in a new tab</a>
    </div>
    @if(!empty($embedUrl))
        <iframe src="{{ $embedUrl }}" title="{{ $name ?? 'File' }}"></iframe>
    @endif
</body>
</html>
