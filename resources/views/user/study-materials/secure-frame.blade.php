<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->name }}</title>
    <style>
        html, body { margin:0; height:100%; background:#111; color:#eee; font-family:sans-serif; }
        .bar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 14px; background:#000435; color:#fff; }
        .bar strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .bar a { background:#f8961f; color:#1e1e1e; text-decoration:none; font-weight:700; padding:6px 12px; border-radius:4px; white-space:nowrap; }
        .wrap { height:calc(100% - 48px); display:flex; align-items:center; justify-content:center; background:#000; }
        iframe, embed, object, img, video, audio { width:100%; height:100%; border:0; background:#000; }
        img { object-fit:contain; }
    </style>
</head>
<body>
    <div class="bar">
        <strong>{{ $item->name }}</strong>
        @if($item->allowsDownload())
            <a href="{{ $downloadUrl }}">Download</a>
        @endif
    </div>
    @php $kind = $item->portalKind(); @endphp
    <div class="wrap">
        @if($kind === 'video')
            <video src="{{ $rawUrl }}" controls playsinline @if($item->portalPosterUrl()) poster="{{ $item->portalPosterUrl() }}" @endif></video>
        @elseif($kind === 'image')
            <img src="{{ $rawUrl }}" alt="{{ $item->name }}" draggable="false">
        @elseif($kind === 'audio')
            <audio src="{{ $rawUrl }}" controls></audio>
        @else
            <iframe src="{{ $rawUrl }}" title="{{ $item->name }}" allow="fullscreen"></iframe>
        @endif
    </div>
</body>
</html>
