<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Refreshing…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ url('/') }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        .box {
            background: #fff;
            padding: 24px 28px;
            border-radius: 8px;
            box-shadow: 0 8px 28px rgba(0,0,0,.12);
            text-align: center;
            max-width: 400px;
        }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { margin: 0 0 16px; font-size: 14px; color: #4b5563; }
        button, a.btn {
            display: inline-block;
            margin: 0 4px;
            border: none;
            border-radius: 4px;
            padding: 9px 16px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        button { background: #e6a60b; color: #000435; }
        a.btn { background: #e5e7eb; color: #111827; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Session expired</h1>
        <p>Refreshing so you can continue — no error page.</p>
        <button type="button" id="refreshBtn">Refresh</button>
        <a class="btn" href="{{ url('/') }}">Home</a>
    </div>
    <script>
        (function () {
            var ref = document.referrer || @json(url('/'));
            try {
                var u = new URL(ref, window.location.origin);
                if (u.origin === window.location.origin) {
                    ref = u.href;
                } else {
                    ref = @json(url('/'));
                }
            } catch (e) {
                ref = @json(url('/'));
            }
            function go() {
                window.location.replace(ref);
            }
            document.getElementById('refreshBtn').addEventListener('click', go);
            go();
        })();
    </script>
</body>
</html>
