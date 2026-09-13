<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->name }}</title>
    <style>
        html, body { margin:0; height:100%; background:#fff; color:#222; font-family:sans-serif; }
        .box { max-width:28rem; margin:12vh auto; padding:1.5rem; text-align:center; }
        a { display:inline-block; margin-top:1rem; background:#f8961f; color:#1e1e1e; text-decoration:none; font-weight:700; padding:8px 14px; border-radius:4px; }
    </style>
</head>
<body>
    <div class="box">
        <p>This file could not be loaded inside the portal.</p>
        @if(empty($asDownload))
            <p><a href="{{ $downloadUrl }}">Download</a></p>
        @endif
    </div>
</body>
</html>
